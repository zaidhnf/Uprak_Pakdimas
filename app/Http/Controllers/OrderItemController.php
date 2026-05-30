<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Http\Request;

class OrderItemController extends Controller
{
    /**
     * Menampilkan semua item dari pesanan tertentu
     * GET /api/orders/{orderId}/items
     */
    public function index($orderId)
    {
        $order = Order::where('user_id', auth()->id())->find($orderId);
        
        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'Pesanan tidak ditemukan'
            ], 404);
        }
        
        $items = $order->items()->with('product')->get();
        
        return response()->json([
            'success' => true,
            'message' => 'Daftar item pesanan berhasil diambil',
            'data' => $items
        ]);
    }
    
    /**
     * Menampilkan detail item pesanan tertentu
     * GET /api/orders/{orderId}/items/{itemId}
     */
    public function show($orderId, $itemId)
    {
        $order = Order::where('user_id', auth()->id())->find($orderId);
        
        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'Pesanan tidak ditemukan'
            ], 404);
        }
        
        $item = $order->items()->with('product')->find($itemId);
        
        if (!$item) {
            return response()->json([
                'success' => false,
                'message' => 'Item pesanan tidak ditemukan'
            ], 404);
        }
        
        return response()->json([
            'success' => true,
            'message' => 'Detail item pesanan berhasil diambil',
            'data' => $item
        ]);
    }
}