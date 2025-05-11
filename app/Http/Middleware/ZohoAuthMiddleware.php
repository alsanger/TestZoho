<?php

namespace App\Http\Middleware;

use App\Models\ZohoToken;
use App\Services\ZohoService;
use Carbon\Carbon;
use Closure;
use Illuminate\Http\Request;

/**
 * Middleware for Zoho CRM user authorization check.
 *
 * Verifies the presence and validity of the auth token.
 * If needed, performs token refresh or redirects to the auth page.
 */
class ZohoAuthMiddleware
{
    /**
     * Processes the incoming request.
     *
     * Checks for a valid Zoho authorization token.
     * If the access token is expired, refreshes it.
     * If the token is missing, redirects to the authorization page.
     *
     * @param Request $request Incoming HTTP request
     * @param Closure $next Next handler in the middleware chain
     * @return mixed Application response or redirect to authorization page
     */
    public function handle(Request $request, Closure $next): mixed
    {
        $token = ZohoToken::first();
        $isAuthenticated = !is_null($token);

        if ($isAuthenticated && $token->expires_at->lt(Carbon::now())) {
            // Пытаемся обновить токен
            $zohoService = app(ZohoService::class);
            $newToken = $zohoService->refreshToken($token);

            // Если не удалось обновить токен, перенаправляем на авторизацию
            if (!$newToken) {
                $isAuthenticated = false;
            }
        }

        if (!$isAuthenticated) {
            if ($request->wantsJson()) {
                return response()->json(['error' => 'Unauthorized', 'authenticated' => false], 401);
            }
            return redirect('/auth/zoho');
        }

        return $next($request);
    }
}
