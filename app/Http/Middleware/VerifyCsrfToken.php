<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;

/**
 * Middleware для проверки CSRF-токена.
 *
 * Защищает приложение от CSRF-атак, проверяя валидность токена
 * для каждого POST, PUT, DELETE и PATCH запроса.
 */
class VerifyCsrfToken extends Middleware
{
    /**
     * URI, которые не требуют проверки CSRF-токена.
     *
     * @var array<int, string>
     */
    protected $except = [
        'api/zoho/create',
    ];
}
