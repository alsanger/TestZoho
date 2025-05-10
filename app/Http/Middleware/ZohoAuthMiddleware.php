<?php

namespace App\Http\Middleware;

use App\Http\Controllers\ZohoController;
use App\Models\ZohoToken;
use Carbon\Carbon;
use Closure;
use Illuminate\Http\Request;

class ZohoAuthMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $token = ZohoToken::first();
        $isAuthenticated = !is_null($token);

        if ($isAuthenticated && $token->expires_at->lt(Carbon::now())) {
            // Пытаемся обновить токен
            $zohoController = app(ZohoController::class);
            $newToken = $zohoController->refreshToken($token);

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
