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
            'message' => 'Data kategori',
            'data' => $categories
        ]);
    }

    /**
     * POST /categories
     * Membuat kategori baru
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:categories,name',
            'description' => 'nullable|string'
        ]);

        $category = Category::create([
            'name' => $request->name,
            'slug' => Str::slug($request->name),
            'description' => $request->description
        ]);

        return response()->json([
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
        $request->validate([
            'name' => 'required|string|max:255|unique:categories,name,' . $category->id,
            'description' => 'nullable|string'
        ]);

        $category->update([
            'name' => $request->name,
            'slug' => Str::slug($request->name),
            'description' => $request->description
        ]);

        return response()->json([
            'message' => 'Kategori berhasil diupdate',
            'data' => $category
        ]);
    }

    /**
     * DELETE /categories/{id}
     * Hapus kategori jika tidak ada produk
     */
    public function destroy(Category $category)
    {
        if ($category->products()->count() > 0) {
            return response()->json([
                'message' => 'Kategori tidak bisa dihapus karena masih memiliki produk'
            ], 400);
        }

        $category->delete();

        return response()->json([
            'message' => 'Kategori berhasil dihapus'
        ]);
    }
}
