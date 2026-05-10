<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class DepositToMainRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // kasir boleh setor
    }

    public function rules(): array
    {
        return [
            'amount' => 'required|integer|min:1000',
        ];
    }

    public function messages(): array
    {
        return [
            'amount.required' => 'Masukkan jumlah yang ingin disetor!',
            'amount.integer'  => 'Jumlah harus berupa angka!',
            'amount.min'      => 'Minimal setor Rp 1.000',
        ];
    }
}