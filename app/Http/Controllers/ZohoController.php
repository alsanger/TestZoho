<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateAccountDealRequest;
use App\Http\Requests\ZohoAuthCallbackRequest;
use App\Services\ZohoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Контроллер для работы с Zoho CRM.
 *
 * Обрабатывает запросы авторизации, обратные вызовы OAuth,
 * получение данных и создание записей в Zoho CRM.
 */
class ZohoController extends Controller
{
    /**
     * Сервис для работы с Zoho CRM API.
     *
     * @var ZohoService
     */
    protected ZohoService $zohoService;

    /**
     * Создает новый экземпляр контроллера.
     *
     * @param ZohoService $zohoService Сервис для работы с Zoho CRM
     */
    public function __construct(ZohoService $zohoService)
    {
        $this->zohoService = $zohoService;
    }

    /**
     * Отображает страницу авторизации в Zoho CRM.
     *
     * @return Response Страница авторизации с URL для OAuth
     */
    public function showAuth(): Response
    {
        $authUrl = $this->zohoService->getAuthUrl();
        return Inertia::render('ZohoAuth', [
            'authUrl' => $authUrl
        ]);
    }

    /**
     * Обрабатывает обратный вызов OAuth после авторизации в Zoho CRM.
     *
     * @param ZohoAuthCallbackRequest $request Запрос с кодом авторизации
     * @return RedirectResponse Редирект с сообщением о результате
     */
    public function callback(ZohoAuthCallbackRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $result = $this->zohoService->processCallback(
            $validated['code'],
            $validated['location'] ?? 'eu'
        );

        if ($result['success']) {
            return redirect('/')->with('success', $result['message']);
        } else {
            return redirect('/')->with('error', $result['message']);
        }
    }

    /**
     * Получает список доступных этапов сделок из Zoho CRM.
     *
     * @return JsonResponse JSON-ответ со списком этапов или сообщением об ошибке
     */
    public function getDealStages(): JsonResponse
    {
        $result = $this->zohoService->getDealStages();

        if ($result['success']) {
            return response()->json(['stages' => $result['stages']]);
        } else {
            return response()->json(['error' => $result['error']], 400);
        }
    }

    /**
     * Создает новый аккаунт и сделку в Zoho CRM.
     *
     * @param CreateAccountDealRequest $request Запрос с данными аккаунта и сделки
     * @return JsonResponse JSON-ответ с результатами создания или сообщением об ошибке
     */
    public function createRecords(CreateAccountDealRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $accountData = [
            'account_name' => $validated['account_name'],
            'account_website' => $validated['account_website'] ?? null,
            'account_phone' => $validated['account_phone'] ?? null,
        ];

        $dealData = [
            'deal_name' => $validated['deal_name'],
            'deal_stage' => $validated['deal_stage'],
        ];

        $result = $this->zohoService->createAccountAndDeal($accountData, $dealData);

        if ($result['success']) {
            return response()->json([
                'success' => true,
                'account' => $result['account'],
                'deal' => $result['deal']
            ]);
        } else {
            return response()->json([
                'error' => $result['error'],
                'details' => $result['details'] ?? null
            ], 400);
        }
    }
}
