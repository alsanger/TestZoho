<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Request to handle the OAuth callback from Zoho.
 *
 * Verifies the presence and validity of the authorization code and Zoho region.
 */
class ZohoAuthCallbackRequest extends FormRequest
{
    /**
     * Determines if the user is authorized to perform this request.
     *
     * @return bool Always returns true, as authorization is handled by middleware
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Validation rules for the request data.
     *
     * @return array<string, string> Array of validation rules
     */
    public function rules(): array
    {
        return [
            'code' => 'nullable|string',
            'location' => 'nullable|string|in:com,eu,in,com.au,jp,cn'
        ];
    }

    /**
     * Custom validation error messages.
     *
     * @return array<string, string> Array of error messages
     */
    public function messages(): array
    {
        return [
            'code.required' => 'Authorization code is required',
            'location.in' => 'Invalid Zoho region specified'
        ];
    }
}
