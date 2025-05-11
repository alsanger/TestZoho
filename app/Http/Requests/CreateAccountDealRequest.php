<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

/**
 * Request for creating an account and deal in Zoho CRM.
 *
 * Handles data validation and response formatting in case of errors.
 */
class CreateAccountDealRequest extends FormRequest
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
            'account_name' => 'required|string|max:255',
            'account_website' => 'required|url|max:255',
            'account_phone' => 'required|string|max:20',
            'deal_name' => 'required|string|max:255',
            'deal_stage' => 'required|string|max:100',
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
            'account_name.required' => 'Account name is required',
            'account_website.required' => 'Website is required',
            'account_website.url' => 'Website must be a valid URL',
            'account_phone.required' => 'Phone number is required',
            'deal_name.required' => 'Deal name is required',
            'deal_stage.required' => 'Deal stage is required',
            'deal_stage.in' => 'Invalid deal stage value',
        ];
    }

    /**
     * Handles validation failure and returns a JSON response with errors.
     *
     * @param Validator $validator Validator instance with errors
     * @throws HttpResponseException JSON response with validation errors
     * @return void
     */
    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(
            response()->json([
                'error' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422)
        );
    }
}
