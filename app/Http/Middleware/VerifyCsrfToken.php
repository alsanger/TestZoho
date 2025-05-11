<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;

/**
 * Middleware for CSRF token validation.
 *
 * Protects the application from CSRF attacks by verifying the token's validity
 * for each POST, PUT, DELETE, and PATCH request.
 */
class VerifyCsrfToken extends Middleware
{
    /**
     * URIs that do not require CSRF token verification.
     *
     * @var array<int, string>
     */
    protected $except = [
        'api/zoho/create',
    ];
}
