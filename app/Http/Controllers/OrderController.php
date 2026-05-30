<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreOrderRequest;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    /**
     * Menampilkan semua pesanan milik user yang login
     * GET /api/orders
     */
    public function index()
    {
        $orders = Order::with('items.product')
            ->where('user_id', auth()->id())
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Daftar pesanan berhasil diambil',
            'data' => $orders
        ]);
    }

    /**
     * Membuat pesanan baru
     * POST /api/orders
     */
    public function store(StoreOrderRequest $request)
    {
        $validated = $request->validated();
        
        DB::beginTransaction();
        
        try {
            $totalPrice = 0;
            $orderItems = [];
            
            // Hitung total harga dan validasi stok
            foreach ($validated['items'] as $item) {
                $product = Product::findOrFail($item['product_id']);
                
                // Cek stok
                if ($product->stock < $item['quantity']) {
                    return response()->json([
                        'success' => false,
                        'message' => "Stok produk {$product->name} tidak mencukupi. Stok tersedia: {$product->stock}"
                    ], 400);
                }
                
                $subtotal = $product->price * $item['quantity'];
                $totalPrice += $subtotal;
                
                $orderItems[] = [
                    'product_id' => $product->id,
                    'quantity' => $item['quantity'],
                    'unit_price' => $product->price
                ];
                
                // Kurangi stok
                $product->decrement('stock', $item['quantity']);
            }
            
            // Buat order
            $order = Order::create([
                'user_id' => auth()->id(),
                'total_price' => $totalPrice,
                'status' => 'pending',
                'notes' => $validated['notes'] ?? null
            ]);
            
            // Buat order items
            foreach ($orderItems as $item) {
                $item['order_id'] = $order->id;
                OrderItem::create($item);
            }
            
            DB::commit();
            
            // Load relasi items dan product untuk response
            $order->load('items.product');
            
            return response()->json([
                'success' => true,
                'message' => 'Pesanan berhasil dibuat',
                'data' => $order
            ], 201);
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'Gagal membuat pesanan: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Menampilkan detail pesanan beserta item-itemnya
     * GET /api/orders/{id}
     */
    public function show($id)
    {
        $order = Order::with('items.product')
            ->where('user_id', auth()->id())
            ->find($id);
        
        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'Pesanan tidak ditemukan'
            ], 404);
        }
        
        // Cek kepemilikan pesanan (sudah dilakukan oleh where user_id)
        return response()->json([
            'success' => true,
            'message' => 'Detail pesanan berhasil diambil',
            'data' => $order
        ]);
    }

    /**
     * Memperbarui status pesanan
     * PATCH /api/orders/{id}/status
     */
    public function updateStatus(Request $request, $id)
    {
        $order = Order::where('user_id', auth()->id())->find($id);
        
        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'Pesanan tidak ditemukan'
            ], 404);
        }
        
        $validated = $request->validate([
            'status' => 'required|in:pending,processing,done,cancelled'
        ]);
        
        $order->update([
            'status' => $validated['status']
        ]);
        
        return response()->json([
            'success' => true,
            'message' => 'Status pesanan berhasil diperbarui',
            'data' => $order->load('items.product')
        ]);
    }
}