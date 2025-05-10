<?php

namespace App\Services;

use App\Models\ZohoToken;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ZohoService
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

    public function getAuthUrl(): string
    {
        return $this->zohoAccountsDomain . '/oauth/v2/auth?' . http_build_query([
                'scope' => 'ZohoCRM.modules.ALL,ZohoCRM.settings.ALL',
                'client_id' => $this->clientId,
                'response_type' => 'code',
                'access_type' => 'offline',
                'redirect_uri' => $this->redirectUri,
                'prompt' => 'consent'
            ]);
    }

    public function processCallback($code, $location = 'eu'): array
    {
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
                $errorMessage = $data['error'] ?? 'Error getting token';
                return ['success' => false, 'message' => 'Authorization error: ' . $errorMessage];
            }

            // Удаляем старые токены и создаем новый
            ZohoToken::truncate();

            // Создаем новый токен
            ZohoToken::create([
                'access_token' => $data['access_token'],
                'refresh_token' => $data['refresh_token'],
                'expires_at' => Carbon::now()->addSeconds($data['expires_in']),
            ]);

            return ['success' => true, 'message' => 'Authorization in Zoho CRM successful'];
        } catch (\Exception $e) {
            Log::error('Zoho authorization error', ['error' => $e->getMessage()]);
            return ['success' => false, 'message' => 'Authorization error: ' . $e->getMessage()];
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
                Log::error('Token update error', ['error' => $data['error'] ?? 'Unknown error']);
                return null;
            }

            // Обновляем токен в базе данных
            $token->access_token = $data['access_token'];
            $token->expires_at = Carbon::now()->addSeconds($data['expires_in'] - 30); // Устанавливаем время истечения на 30 секунд раньше, чтобы избежать проблем с синхронизацией
            $token->save();

            return $token->access_token;
        } catch (\Exception $e) {
            Log::error('Exception while updating token', ['error' => $e->getMessage()]);
            return null;
        }
    }

    public function getDealStages(): array
    {
        $token = $this->getToken();

        if (!$token) {
            return ['success' => false, 'error' => 'Authorization token is missing or invalid'];
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Zoho-oauthtoken ' . $token,
                'Content-Type' => 'application/json',
            ])->get($this->zohoApiDomain . '/crm/v2/settings/fields', [
                'module' => 'Deals'
            ]);

            $data = $response->json();

            if (isset($data['fields'])) {
                // Ищем поле Stage и его возможные значения
                foreach ($data['fields'] as $field) {
                    if ($field['api_name'] === 'Stage') {
                        $stageOptions = array_map(function($option) {
                            return [
                                'value' => $option['actual_value'],
                                'label' => $option['display_value']
                            ];
                        }, $field['pick_list_values']);

                        return ['success' => true, 'stages' => $stageOptions];
                    }
                }
            }

            return ['success' => false, 'error' => 'Failed to retrieve deal stages'];
        } catch (\Exception $e) {
            Log::error('Error retrieving deal stages', ['error' => $e->getMessage()]);
            return ['success' => false, 'error' => 'An error occurred: ' . $e->getMessage()];
        }
    }

    public function createAccountAndDeal($accountData, $dealData): array
    {
        $token = $this->getToken();

        if (!$token) {
            return ['success' => false, 'error' => 'The authorization token is missing or invalid. Please log in again.'];
        }

        try {
            // Создаем аккаунт
            $accountResponse = Http::withHeaders([
                'Authorization' => 'Zoho-oauthtoken ' . $token,
                'Content-Type' => 'application/json',
            ])->post($this->zohoApiDomain . '/crm/v2/Accounts', [
                'data' => [
                    [
                        'Account_Name' => $accountData['account_name'],
                        'Website' => $accountData['account_website'] ?? null,
                        'Phone' => $accountData['account_phone'] ?? null,
                    ]
                ]
            ]);

            $accountResult = $accountResponse->json();

            if (
                !isset($accountResult['data']) ||
                !isset($accountResult['data'][0]['details']['id'])
            ) {
                return ['success' => false, 'error' => 'Failed to create account', 'details' => $accountResult];
            }

            // Получаем ID аккаунта
            $accountId = $accountResult['data'][0]['details']['id'];

            // Создаем сделку
            $dealResponse = Http::withHeaders([
                'Authorization' => 'Zoho-oauthtoken ' . $token,
                'Content-Type' => 'application/json',
            ])->post($this->zohoApiDomain . '/crm/v2/Deals', [
                'data' => [
                    [
                        'Deal_Name' => $dealData['deal_name'],
                        'Stage' => $dealData['deal_stage'],
                        'Account_Name' => [
                            'id' => $accountId
                        ]
                    ]
                ]
            ]);

            $dealResult = $dealResponse->json();

            if (!isset($dealResult['data'])) {
                return [
                    'success' => false,
                    'error' => 'Failed to create deal',
                    'details' => $dealResult,
                ];
            }

            return [
                'success' => true,
                'account' => $accountResult['data'][0],
                'deal' => $dealResult['data'][0]
            ];
        } catch (\Exception $e) {
            Log::error('Error creating records in Zoho', ['error' => $e->getMessage()]);
            return ['success' => false, 'error' => 'An error occurred: ' . $e->getMessage()];
        }
    }
}
