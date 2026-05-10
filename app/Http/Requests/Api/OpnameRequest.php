<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class OpnameRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'opname' => 'required|array|min:1',
            'opname.*.product_id' => 'required|exists:products,id',
            'opname.*.real_quantity' => 'required|integer|min:0',
            'notes' => 'nullable|string|max:500',
        ];
    }


    public function messages(): array
    {
        return [
            'opname.required' => 'Pilih minimal 1 produk untuk diopname!',
        ];
    }
}