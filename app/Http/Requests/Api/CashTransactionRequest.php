<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class CashTransactionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'type'        => 'required|in:DEPOSIT,EXPENSE',
            'amount'      => 'required|integer|min:1',
            'description' => 'required|string|max:500',
        ];
    }
}