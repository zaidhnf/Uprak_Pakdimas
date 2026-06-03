<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CategoryController extends Controller
{
    /**
     * GET /categories
     * Menampilkan semua kategori
     */
    public function index()
    {
        $categories = Category::latest()->get();

        return response()->json([
            'success' => true,
            'message' => 'Daftar kategori',
            'data' => $categories
        ]);
    }

    /**
     * POST /categories
     * Membuat kategori baru
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string'
        ]);

        $category = Category::create([
            'name' => $validated['name'],
            'slug' => Str::slug($validated['name']),
            'description' => $validated['description'] ?? null
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Kategori berhasil dibuat',
            'data' => $category
        ], 201);
    }

    /**
     * GET /categories/{id}
     * Detail kategori + produk
     */
    public function show(Category $category)
    {
        $category->load('products');

        return response()->json([
            'success' => true,
            'message' => 'Detail kategori',
            'data' => $category
        ]);
    }

    /**
     * PUT /categories/{id}
     * Update kategori
     */
    public function update(Request $request, Category $category)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string'
        ]);

        $category->update([
            'name' => $validated['name'],
            'slug' => Str::slug($validated['name']),
            'description' => $validated['description']
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Kategori berhasil diperbarui',
            'data' => $category
        ]);
    }

    /**
     * DELETE /categories/{id}
     * Hapus kategori jika tidak ada produk
     */
    public function destroy(Category $category)
    {
        if ($category->products()->exists()) {

            return response()->json([
                'success' => false,
                'message' => 'Kategori masih memiliki produk'
            ], 400);
        }

        $category->delete();

        return response()->json([
            'success' => true,
            'message' => 'Kategori berhasil dihapus'
        ]);
    }
}
