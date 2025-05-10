<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateAccountDealRequest;
use App\Models\ZohoToken;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Inertia\Inertia;

class ZohoController extends Controller
{
    private $clientId;
    private $clientSecret;
    private $redirectUri;
    private $zohoApiDomain;
    private $zohoAccountsDomain;

    public function __construct()
    {
        $this->clientId = env('ZOHO_CLIENT_ID');
        $this->clientSecret = env('ZOHO_CLIENT_SECRET');
        $this->redirectUri = env('ZOHO_REDIRECT_URI');
        $this->zohoApiDomain = 'https://www.zohoapis.eu';
        $this->zohoAccountsDomain = 'https://accounts.zoho.eu';
    }

    public function showAuth()
    {
        $authUrl = $this->zohoAccountsDomain . '/oauth/v2/auth?' . http_build_query([
                'scope' => 'ZohoCRM.modules.ALL',
                'client_id' => $this->clientId,
                'response_type' => 'code',
                'access_type' => 'offline',
                'redirect_uri' => $this->redirectUri,
                'prompt' => 'consent'
            ]);

        return Inertia::render('ZohoAuth', [
            'authUrl' => $authUrl
        ]);
    }

    public function callback(Request $request)
    {
        $code = $request->get('code');
        $location = $request->get('location', 'eu');

        if (!$code) {
            return redirect('/')->with('error', 'Код авторизации не найден');
        }

        try {
            $accountsDomain = 'https://accounts.zoho.' . $location;

            $params = [
                'grant_type' => 'authorization_code',
                'client_id' => $this->clientId,
                'client_secret' => $this->clientSecret,
                'redirect_uri' => $this->redirectUri,
                'code' => $code,
            ];

            $response = Http::asForm()->post($accountsDomain . '/oauth/v2/token', $params);

            //Log::info('Zoho Response Status: ' . $response->status());
            //Log::info('Zoho Response Body: ' . $response->body());

            $data = $response->json();

            if (!$data || isset($data['error']) || !isset($data['access_token'])) {
                $errorMessage = isset($data['error']) ? $data['error'] : 'Ошибка при получении токена';
                return redirect('/')->with('error', 'Ошибка авторизации: ' . $errorMessage);
            }

            // Удаляем старые токены и создаем новый
            ZohoToken::truncate();

            // Создаем новый токен
            ZohoToken::create([
                'access_token' => $data['access_token'],
                'refresh_token' => $data['refresh_token'],
                'expires_at' => Carbon::now()->addSeconds($data['expires_in']),
            ]);

            return redirect('/')->with('success', 'Авторизация в Zoho CRM успешна');
        } catch (\Exception $e) {
            Log::error('Ошибка авторизации Zoho', ['error' => $e->getMessage()]);
            return redirect('/')->with('error', 'Ошибка авторизации: ' . $e->getMessage());
        }
    }

    public function getToken()
    {
        $token = ZohoToken::first();

        if (!$token) {
            return null;
        }

        // Проверяем и при необходимости обновляем токен
        if ($token->expires_at->lt(Carbon::now())) {
            return $this->refreshToken($token);
        }

        return $token->access_token;
    }

    public function refreshToken($token)
    {
        try {
            $response = Http::asForm()->post($this->zohoAccountsDomain . '/oauth/v2/token', [
                'grant_type' => 'refresh_token',
                'client_id' => $this->clientId,
                'client_secret' => $this->clientSecret,
                'refresh_token' => $token->refresh_token,
            ]);

            $data = $response->json();

            if (!$data || isset($data['error'])) {
                Log::error('Ошибка обновления токена', ['error' => $data['error'] ?? 'Неизвестная ошибка']);
                return null;
            }

            // Обновляем токен в базе данных
            $token->access_token = $data['access_token'];
            $token->expires_at = Carbon::now()->addSeconds($data['expires_in'] - 30); // Устанавливаем время истечения на 30 секунд раньше, чтобы избежать проблем с синхронизацией
            $token->save();

            return $token->access_token;
        } catch (\Exception $e) {
            Log::error('Исключение при обновлении токена', ['error' => $e->getMessage()]);
            return null;
        }
    }

    public function createRecords(CreateAccountDealRequest $request)
    {
        $token = $this->getToken();

        if (!$token) {
            return response()->json(['error' => 'Токен авторизации отсутствует или недействителен. Пожалуйста, авторизуйтесь заново.'], 401);
        }

        try {
            // Создаем аккаунт
            $accountResponse = Http::withHeaders([
                'Authorization' => 'Zoho-oauthtoken ' . $token,
                'Content-Type' => 'application/json',
            ])->post($this->zohoApiDomain . '/crm/v2/Accounts', [
                'data' => [
                    [
                        'Account_Name' => $request->account_name,
                        'Website' => $request->account_website,
                        'Phone' => $request->account_phone,
                    ]
                ]
            ]);

            $accountData = $accountResponse->json();

            if (
                !isset($accountData['data']) ||
                !isset($accountData['data'][0]['details']['id'])
            ) {
                return response()->json(['error' => 'Не удалось создать аккаунт', 'details' => $accountData], 400);
            }

            // Получаем ID аккаунта
            $accountId = $accountData['data'][0]['details']['id'];

            // Создаем сделку
            $dealResponse = Http::withHeaders([
                'Authorization' => 'Zoho-oauthtoken ' . $token,
                'Content-Type' => 'application/json',
            ])->post($this->zohoApiDomain . '/crm/v2/Deals', [
                'data' => [
                    [
                        'Deal_Name' => $request->deal_name,
                        'Stage' => $request->deal_stage,
                        'Account_Name' => [
                            'id' => $accountId
                        ]
                    ]
                ]
            ]);

            $dealData = $dealResponse->json();

            if (!isset($dealData['data'])) {
                return response()->json([
                    'error' => 'Не удалось создать сделку',
                    'details' => $dealData,
                ], 400);
            }

            return response()->json([
                'success' => true,
                'account' => $accountData['data'][0],
                'deal' => $dealData['data'][0]
            ]);
        } catch (\Exception $e) {
            Log::error('Ошибка создания записей в Zoho', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Произошла ошибка: ' . $e->getMessage()], 500);
        }
    }
}
