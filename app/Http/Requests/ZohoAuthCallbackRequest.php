<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Запрос для обработки обратного вызова OAuth от Zoho.
 *
 * Проверяет наличие и корректность кода авторизации и региона Zoho.
 */
class ZohoAuthCallbackRequest extends FormRequest
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
            'code' => 'nullable|string',
            'location' => 'nullable|string|in:com,eu,in,com.au,jp,cn'
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
            'code.required' => 'Authorization code is required',
            'location.in' => 'Invalid Zoho region specified'
        ];
    }
}
