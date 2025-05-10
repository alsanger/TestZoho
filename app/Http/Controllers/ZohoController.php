<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateAccountDealRequest;
use App\Http\Requests\ZohoAuthCallbackRequest;
use App\Services\ZohoService;
use Illuminate\Http\JsonResponse;
use Inertia\Inertia;
use Inertia\Response;

class ZohoController extends Controller
{
    protected ZohoService $zohoService;

    public function __construct(ZohoService $zohoService)
    {
        $this->zohoService = $zohoService;
    }

    public function showAuth(): Response
    {
        $authUrl = $this->zohoService->getAuthUrl();
        return Inertia::render('ZohoAuth', [
            'authUrl' => $authUrl
        ]);
    }

    public function callback(ZohoAuthCallbackRequest $request)
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

    public function getDealStages(): JsonResponse
    {
        $result = $this->zohoService->getDealStages();

        if ($result['success']) {
            return response()->json(['stages' => $result['stages']]);
        } else {
            return response()->json(['error' => $result['error']], 400);
        }
    }

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
