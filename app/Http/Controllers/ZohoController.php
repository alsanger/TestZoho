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
 * Controller for interacting with Zoho CRM.
 *
 * Handles authorization requests, OAuth callbacks,
 * data retrieval, and record creation in Zoho CRM.
 */
class ZohoController extends Controller
{
    /**
     * Service for working with the Zoho CRM API.
     *
     * @var ZohoService
     */
    protected ZohoService $zohoService;

    /**
     * Creates a new instance of the controller.
     *
     * @param ZohoService $zohoService Service for interacting with Zoho CRM
     */
    public function __construct(ZohoService $zohoService)
    {
        $this->zohoService = $zohoService;
    }

    /**
     * Displays the Zoho CRM authorization page.
     *
     * @return Response Authorization page with OAuth URL
     */
    public function showAuth(): Response
    {
        $authUrl = $this->zohoService->getAuthUrl();
        return Inertia::render('ZohoAuth', [
            'authUrl' => $authUrl
        ]);
    }

    /**
     * Handles the OAuth callback after authorization in Zoho CRM.
     *
     * @param ZohoAuthCallbackRequest $request Request containing the authorization code
     * @return RedirectResponse Redirect with a result message
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
     * Retrieves the list of available deal stages from Zoho CRM.
     *
     * @return JsonResponse JSON response with the list of stages or an error message
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
     * Creates a new account and deal in Zoho CRM.
     *
     * @param CreateAccountDealRequest $request Request containing account and deal data
     * @return JsonResponse JSON response with the creation results or an error message
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
