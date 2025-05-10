<?php

namespace App\Http\Middleware;

use App\Models\ZohoToken;
use App\Services\ZohoService;
use Carbon\Carbon;
use Closure;
use Illuminate\Http\Request;

/**
 * Middleware для проверки авторизации пользователя в Zoho CRM.
 *
 * Проверяет наличие и действительность токена авторизации.
 * При необходимости выполняет обновление токена или перенаправляет на страницу авторизации.
 */
class ZohoAuthMiddleware
{
    /**
     * Обрабатывает входящий запрос.
     *
     * Проверяет наличие действительного токена авторизации Zoho.
     * Если токен доступа просрочен, то обновляет его.
     * Если токен отсутствует, то перенаправляет на страницу авторизации.
     *
     * @param Request $request Входящий HTTP-запрос
     * @param Closure $next Следующий обработчик в цепочке middleware
     * @return mixed Ответ приложения или редирект на страницу авторизации
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
