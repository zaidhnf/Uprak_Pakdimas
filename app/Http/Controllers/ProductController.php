<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProductRequest;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

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

        // Filter kategori (bonus)
        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        // Search produk (bonus)
        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('description', 'like', '%' . $request->search . '%');
            });
        }

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

        $validated['slug'] = Str::slug($validated['name']);
        $validated['is_active'] = true;

        $product = Product::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Produk berhasil dibuat',
            'data' => $product->load('category')
        ], 201);
    }

    /**
     * Menampilkan detail produk beserta kategori
     * GET /api/products/{id}
     */
    public function show(Product $product)
    {
        return response()->json([
            'success' => true,
            'message' => 'Detail produk berhasil diambil',
            'data' => $product->load('category')
        ]);
    }

    /**
     * Memperbarui produk
     * PUT /api/products/{id}
     */
    public function update(StoreProductRequest $request, Product $product)
    {
        $validated = $request->validated();

        if (
            isset($validated['name']) &&
            $validated['name'] !== $product->name
        ) {
            $validated['slug'] = Str::slug($validated['name']);
        }

        $product->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Produk berhasil diperbarui',
            'data' => $product->load('category')
        ]);
    }

    /**
     * Toggle status aktif produk
     * PATCH /api/products/{id}/toggle
     */
    public function toggle(Product $product)
    {
        $product->update([
            'is_active' => !$product->is_active
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Status produk berhasil diubah',
            'data' => $product->load('category')
        ]);
    }

    /**
     * Menghapus produk
     * DELETE /api/products/{id}
     */
    public function destroy(Product $product)
    {
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
