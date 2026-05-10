<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class CustomerSearchOrCreateRequest extends FormRequest
{
   // app/Http/Requests/Api/CustomerSearchOrCreateRequest.php
    public function authorize(): bool
    {
        return true; // kasir boleh
    }

    public function rules(): array
    {
        return [
            'phone_number' => 'required|string|regex:/^08[0-9]{8,11}$/',
            'name'         => 'required_if:is_new,true|string|max:255', // wajib kalau baru
            'address'      => 'nullable|string',
            'latitude'     => 'nullable|numeric',
            'longitude'    => 'nullable|numeric',
        ];
    }
}
