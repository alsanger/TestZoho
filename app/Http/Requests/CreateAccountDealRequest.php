<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

/**
 * Запрос для создания аккаунта и сделки в Zoho CRM.
 *
 * Обрабатывает валидацию данных и форматирование ответа в случае ошибок.
 */
class CreateAccountDealRequest extends FormRequest
{
    /**
     * Определяет, авторизован ли пользователь для выполнения этого запроса.
     *
     * @return bool Всегда возвращает true, так как авторизация обрабатывается middleware
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Правила валидации для данных запроса.
     *
     * @return array<string, string> Массив правил валидации
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
     * Пользовательские сообщения об ошибках валидации.
     *
     * @return array<string, string> Массив сообщений об ошибках
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
     * Обрабатывает сбой валидации и возвращает JSON-ответ с ошибками.
     *
     * @param Validator $validator Экземпляр валидатора с ошибками
     * @throws HttpResponseException JSON-ответ с ошибками валидации
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
