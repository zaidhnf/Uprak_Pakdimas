<?php

namespace App\Http\Controllers;

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
            ->latest()
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

            /*
             * Validasi stok & hitung total
             */
            foreach ($validated['items'] as $item) {

                $product = Product::findOrFail(
                    $item['product_id']
                );

                if ($product->stock < $item['quantity']) {
                    throw new \Exception(
                        "Stok produk {$product->name} tidak mencukupi"
                    );
                }

                $subtotal =
                    $product->price *
                    $item['quantity'];

                $totalPrice += $subtotal;

                $orderItems[] = [
                    'product_id' => $product->id,
                    'quantity' => $item['quantity'],
                    'unit_price' => $product->price,
                ];
            }

            /*
             * Buat order
             */
            $order = Order::create([
                'user_id' => auth()->id(),
                'total_price' => $totalPrice,
                'status' => 'pending',
                'notes' => $validated['notes'] ?? null,
            ]);

            /*
             * Buat order item
             * dan kurangi stok
             */
            foreach ($orderItems as $item) {

                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                ]);

                Product::find($item['product_id'])
                    ->decrement(
                        'stock',
                        $item['quantity']
                    );
            }

            DB::commit();

            $order = $order
                ->fresh()
                ->load('items.product');

            return response()->json([
                'success' => true,
                'message' => 'Pesanan berhasil dibuat',
                'data' => $order
            ], 201);

        } catch (\Exception $e) {

            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Menampilkan detail pesanan
     * GET /api/orders/{order}
     */
    public function show(Order $order)
    {
        if ($order->user_id !== auth()->id()) {

            return response()->json([
                'success' => false,
                'message' => 'Akses ditolak'
            ], 403);
        }

        return response()->json([
            'success' => true,
            'message' => 'Detail pesanan berhasil diambil',
            'data' => $order->load('items.product')
        ]);
    }

    /**
     * Mengubah status pesanan
     * PATCH /api/orders/{order}/status
     */
    public function updateStatus(
        Request $request,
        Order $order
    ) {
        if ($order->user_id !== auth()->id()) {

            return response()->json([
                'success' => false,
                'message' => 'Akses ditolak'
            ], 403);
        }

        $validated = $request->validate([
            'status' =>
                'required|in:pending,processing,done,cancelled'
        ]);

        $order->update([
            'status' => $validated['status']
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Status pesanan berhasil diperbarui',
            'data' => $order
                ->fresh()
                ->load('items.product')
        ]);
    }
}
