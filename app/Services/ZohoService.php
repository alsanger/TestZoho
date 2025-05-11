<?php

namespace App\Services;

use App\Models\ZohoToken;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Service for working with Zoho CRM API.
 *
 * Provides methods for authorization, token management
 * and interaction with Zoho CRM API.
 */
class ZohoService
{
    /**
     * Zoho OAuth client identifier.
     *
     * @var string
     */
    private $clientId;

    /**
     * Zoho OAuth client secret.
     *
     * @var string
     */
    private $clientSecret;
    /**
     * OAuth authorization redirect URI.
     *
     * @var string
     */
    private $redirectUri;

    /**
     * Zoho API base domain.
     *
     * @var string
     */
    private $zohoApiDomain;

    /**
     * Base domain for Zoho accounts.
     *
     * @var string
     */
    private $zohoAccountsDomain;

    /**
     * Initializes a new instance of the Zoho service.
     *
     * Loads configuration from environment variables.
     */
    public function __construct()
    {
        $this->clientId = env('ZOHO_CLIENT_ID');
        $this->clientSecret = env('ZOHO_CLIENT_SECRET');
        $this->redirectUri = env('ZOHO_REDIRECT_URI');
        $this->zohoApiDomain = 'https://www.zohoapis.eu';
        $this->zohoAccountsDomain = 'https://accounts.zoho.eu';
    }

    /**
     * Returns the authorization URL for Zoho CRM.
     *
     * @return string Authorization URL
     */
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

    /**
     * Processes the response from Zoho OAuth authorization.
     *
     * @param string $code Authorization code
     * @param string $location Zoho API location (default: 'eu')
     * @return array Authorization processing result
     */
    public function processCallback(string $code, string $location = 'eu'): array
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

    /**
     * Retrieves a valid access token.
     *
     * Checks token availability and validity, refreshes it if necessary.
     *
     * @return string|null Access token or null if failed to get token
     */
    public function getToken(): ?string
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

    /**
     * Refreshes Zoho access token.
     *
     * @param ZohoToken $token Current token that needs refreshing
     * @return string|null New access token or null on error
     */
    public function refreshToken($token): ?string
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

    /**
     * Retrieves deal stages list from Zoho CRM.
     *
     * @return array Request result with deal stages list or error message
     */
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

    /**
     * Creates an account and a deal in Zoho CRM.
     *
     * @param array $accountData Account data
     * @param array $dealData Deal data
     * @return array Result of record creation
     */
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
