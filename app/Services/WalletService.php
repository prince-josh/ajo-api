<?php

namespace App\Services;

use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Support\Facades\DB;

class WalletService
{
    public function __construct(private PaystackService $paystackService) {}

    /**
     * Initiate wallet funding — returns Paystack payment link
     */
    public function initiateFunding(User $user, float $amount): array
    {
        $reference = Transaction::generateReference();

        // Create a pending transaction record
        Transaction::create([
            'wallet_id'      => $user->wallet->id,
            'user_id'        => $user->id,
            'reference'      => $reference,
            'type'           => 'credit',
            'category'       => 'wallet_funding',
            'amount'         => $amount,
            'balance_before' => $user->wallet->balance,
            'balance_after'  => $user->wallet->balance + $amount,
            'status'         => 'pending',
            'description'    => "Wallet funding of ₦" . number_format($amount, 2),
        ]);

        $paystack = $this->paystackService->initializeTransaction(
            email: $user->email,
            amount: $amount,
            reference: $reference,
            metadata: [
                'user_id'    => $user->id,
                'wallet_id'  => $user->wallet->id,
                'category'   => 'wallet_funding',
            ]
        );

        return [
            'reference'         => $reference,
            'authorization_url' => $paystack['data']['authorization_url'],
            'access_code'       => $paystack['data']['access_code'],
            'amount'            => $amount,
        ];
    }

    /**
     * Verify and complete wallet funding after Paystack callback
     */
    public function verifyFunding(User $user, string $reference): array
    {
        $transaction = Transaction::where('reference', $reference)
            ->where('user_id', $user->id)
            ->where('category', 'wallet_funding')
            ->firstOrFail();

        if ($transaction->status === 'successful') {
            return ['message' => 'Transaction already verified', 'transaction' => $transaction];
        }

        $paystack = $this->paystackService->verifyTransaction($reference);
        $paystackData = $paystack['data'];

        if ($paystackData['status'] !== 'success') {
            $transaction->update([
                'status'              => 'failed',
                'paystack_reference'  => $paystackData['reference'],
            ]);
            throw new \Exception('Payment verification failed. Transaction was not successful.');
        }

        DB::transaction(function () use ($user, $transaction, $paystackData) {
            $wallet = $user->wallet;

            // Credit wallet
            $wallet->credit($transaction->amount);

            // Update transaction
            $transaction->update([
                'status'             => 'successful',
                'paystack_reference' => $paystackData['reference'],
                'balance_after'      => $wallet->balance,
            ]);
        });

        return [
            'message'     => 'Wallet funded successfully',
            'amount'      => $transaction->amount,
            'new_balance' => $user->wallet->fresh()->balance,
        ];
    }

    /**
     * Get paginated transaction history for a user
     */
    public function getTransactionHistory(User $user, int $perPage = 15)
    {
        return Transaction::where('user_id', $user->id)
            ->latest()
            ->paginate($perPage);
    }

    /**
     * Internal wallet debit (used by contribution service)
     */
    public function debitWallet(User $user, float $amount, string $category, string $description, array $metadata = []): Transaction
    {
        $wallet = $user->wallet;

        if (!$wallet->hasSufficientBalance($amount)) {
            throw new \Exception('Insufficient wallet balance');
        }

        if ($wallet->is_locked) {
            throw new \Exception('Your wallet is currently locked. Please contact support.');
        }

        return DB::transaction(function () use ($user, $wallet, $amount, $category, $description, $metadata) {
            $balanceBefore = $wallet->balance;
            $wallet->debit($amount);

            return Transaction::create([
                'wallet_id'      => $wallet->id,
                'user_id'        => $user->id,
                'reference'      => Transaction::generateReference(),
                'type'           => 'debit',
                'category'       => $category,
                'amount'         => $amount,
                'balance_before' => $balanceBefore,
                'balance_after'  => $wallet->fresh()->balance,
                'status'         => 'successful',
                'description'    => $description,
                'metadata'       => $metadata,
            ]);
        });
    }

    /**
     * Internal wallet credit (used by payout service)
     */
    public function creditWallet(User $user, float $amount, string $category, string $description, array $metadata = []): Transaction
    {
        $wallet = $user->wallet;

        return DB::transaction(function () use ($user, $wallet, $amount, $category, $description, $metadata) {
            $balanceBefore = $wallet->balance;
            $wallet->credit($amount);

            return Transaction::create([
                'wallet_id'      => $wallet->id,
                'user_id'        => $user->id,
                'reference'      => Transaction::generateReference(),
                'type'           => 'credit',
                'category'       => $category,
                'amount'         => $amount,
                'balance_before' => $balanceBefore,
                'balance_after'  => $wallet->fresh()->balance,
                'status'         => 'successful',
                'description'    => $description,
                'metadata'       => $metadata,
            ]);
        });
    }
}
