<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\TransactionResource;
use App\Http\Resources\WalletResource;
use App\Services\WalletService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class WalletController extends Controller
{
    use ApiResponse;

    public function __construct(private WalletService $walletService) {}

    /**
     * GET /api/wallet
     * Get authenticated user's wallet details
     */
    public function show(Request $request): JsonResponse
    {
        $wallet = $request->user()->wallet;

        return $this->successResponse(new WalletResource($wallet));
    }

    /**
     * POST /api/wallet/fund/initiate
     * Initiate a wallet funding via Paystack
     */
    public function initiateFunding(Request $request): JsonResponse
    {
        $request->validate([
            'amount' => ['required', 'numeric', 'min:100'], // min ₦100
        ]);

        $result = $this->walletService->initiateFunding($request->user(), $request->amount);

        return $this->successResponse($result, 'Payment initiated. Complete payment via the provided URL.');
    }

    /**
     * POST /api/wallet/fund/verify
     * Verify funding transaction after Paystack payment
     */
    public function verifyFunding(Request $request): JsonResponse
    {
        $request->validate([
            'reference' => ['required', 'string'],
        ]);

        $result = $this->walletService->verifyFunding($request->user(), $request->reference);

        return $this->successResponse($result, $result['message']);
    }

    /**
     * GET /api/wallet/transactions
     * Get paginated transaction history
     */
    public function transactions(Request $request): JsonResponse
    {
        $transactions = $this->walletService->getTransactionHistory(
            $request->user(),
            $request->get('per_page', 15)
        );

        return $this->paginatedResponse($transactions, 'Transactions retrieved successfully');
    }
}
