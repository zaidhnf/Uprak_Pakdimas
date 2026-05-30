<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreOrderRequest extends FormRequest
{
    public function authorize()
    {
        return auth()->check();
    }
    
    public function rules()
    {
        return [
            'notes' => 'nullable|string|max:1000',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1'
        ];
    }
    
    public function messages()
    {
        return [
            'items.required' => 'Minimal harus ada satu produk dalam pesanan',
            'items.min' => 'Minimal harus ada satu produk dalam pesanan',
            'items.*.product_id.required' => 'ID produk wajib diisi',
            'items.*.product_id.exists' => 'Produk tidak ditemukan',
            'items.*.quantity.required' => 'Jumlah produk wajib diisi',
            'items.*.quantity.min' => 'Jumlah produk minimal 1'
        ];
    }
}