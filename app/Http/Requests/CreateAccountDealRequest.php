<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class CreateAccountDealRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Авторизация обрабатывается middleware
    }

    public function rules(): array
    {
        return [
            'account_name' => 'required|string|max:255',
            'account_website' => 'required|url|max:255',
            'account_phone' => 'required|string|max:20',
            'deal_name' => 'required|string|max:255',
            'deal_stage' => 'required|string|in:Qualification,Needs Analysis,Value Proposition,Closed Won,Closed Lost',
        ];
    }

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
