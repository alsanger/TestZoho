<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ZohoAuthCallbackRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Авторизация обрабатывается middleware
    }

    public function rules(): array
    {
        return [
            'code' => 'nullable|string',
            'location' => 'nullable|string|in:com,eu,in,com.au,jp,cn'
        ];
    }

    public function messages(): array
    {
        return [
            'code.required' => 'Authorization code is required',
            'location.in' => 'Invalid Zoho region specified'
        ];
    }
}
