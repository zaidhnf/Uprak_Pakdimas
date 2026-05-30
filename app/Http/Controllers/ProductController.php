<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Models\Product;
use App\Models\Category;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    /**
     * Menampilkan semua produk aktif (dengan pagination)
     * GET /api/products
     */
    public function index(Request $request)
    {
        $query = Product::with('category')
            ->where('is_active', true);
        
        // Filter berdasarkan kategori (bonus)
        if ($request->has('category_id') && $request->category_id) {
            $query->where('category_id', $request->category_id);
        }
        
        // Pencarian produk (bonus)
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }
        
        // Pagination (10 produk per halaman)
        $products = $query->paginate(10);
        
        return response()->json([
            'success' => true,
            'message' => 'Daftar produk berhasil diambil',
            'data' => $products
        ]);
    }

    /**
     * Membuat produk baru
     * POST /api/products
     */
    public function store(StoreProductRequest $request)
    {
        $validated = $request->validated();
        
        // Generate slug dari name
        $validated['slug'] = \Str::slug($validated['name']);
        
        // Set default is_active jika tidak ada
        $validated['is_active'] = $validated['is_active'] ?? true;
        
        $product = Product::create($validated);
        
        // Load relasi category
        $product->load('category');
        
        return response()->json([
            'success' => true,
            'message' => 'Produk berhasil dibuat',
            'data' => $product
        ], 201);
    }

    /**
     * Menampilkan detail produk beserta kategori
     * GET /api/products/{id}
     */
    public function show($id)
    {
        $product = Product::with('category')->find($id);
        
        if (!$product) {
            return response()->json([
                'success' => false,
                'message' => 'Produk tidak ditemukan'
            ], 404);
        }
        
        return response()->json([
            'success' => true,
            'message' => 'Detail produk berhasil diambil',
            'data' => $product
        ]);
    }

    /**
     * Memperbarui data produk
     * PUT /api/products/{id}
     */
    public function update(UpdateProductRequest $request, $id)
    {
        $product = Product::find($id);
        
        if (!$product) {
            return response()->json([
                'success' => false,
                'message' => 'Produk tidak ditemukan'
            ], 404);
        }
        
        $validated = $request->validated();
        
        // Update slug jika name berubah
        if (isset($validated['name']) && $validated['name'] !== $product->name) {
            $validated['slug'] = \Str::slug($validated['name']);
        }
        
        $product->update($validated);
        
        // Load relasi category
        $product->load('category');
        
        return response()->json([
            'success' => true,
            'message' => 'Produk berhasil diperbarui',
            'data' => $product
        ]);
    }

    /**
     * Mengaktifkan / menonaktifkan produk (toggle is_active)
     * PATCH /api/products/{id}/toggle
     */
    public function toggle($id)
    {
        $product = Product::find($id);
        
        if (!$product) {
            return response()->json([
                'success' => false,
                'message' => 'Produk tidak ditemukan'
            ], 404);
        }
        
        $product->is_active = !$product->is_active;
        $product->save();
        
        $status = $product->is_active ? 'diaktifkan' : 'dinonaktifkan';
        
        return response()->json([
            'success' => true,
            'message' => "Produk berhasil {$status}",
            'data' => $product->load('category')
        ]);
    }

    /**
     * Menghapus produk
     * DELETE /api/products/{id}
     */
    public function destroy($id)
    {
        $product = Product::find($id);
        
        if (!$product) {
            return response()->json([
                'success' => false,
                'message' => 'Produk tidak ditemukan'
            ], 404);
        }
        
        // Cek apakah produk sedang digunakan di order_items
        if ($product->orderItems()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Produk tidak dapat dihapus karena sudah ada dalam pesanan'
            ], 400);
        }
        
        $product->delete();
        
        return response()->json([
            'success' => true,
            'message' => 'Produk berhasil dihapus'
        ]);
    }
}