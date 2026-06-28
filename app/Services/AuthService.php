<?php

namespace App\Services;

use App\Models\User;
use App\Models\Wallet;
use Illuminate\Support\Facades\Hash;

class AuthService
{
    public function register(array $data): array
    {
        $user = User::create([
            'first_name'     => $data['first_name'],
            'last_name'      => $data['last_name'],
            'email'          => $data['email'],
            'phone'          => $data['phone'],
            'password'       => Hash::make($data['password']),
            'account_number' => $this->generateAccountNumber(),
            'address'        => $data['address'] ?? null,
            'state'          => $data['state'] ?? null,
            'role'           => 'user',
        ]);

        // Auto-create wallet
        Wallet::create([
            'user_id' => $user->id,
            'balance' => 0.00,
        ]);

        $token = $user->createToken('ajo-auth-token')->plainTextToken;

        return [
            'user'  => $user->load('wallet'),
            'token' => $token,
        ];
    }

    public function login(array $data): array|false
    {
        $user = User::where('email', $data['email'])->first();

        if (!$user || !Hash::check($data['password'], $user->password)) {
            return false;
        }

        if (!$user->is_active) {
            throw new \Exception('Your account has been suspended. Please contact support.');
        }

        // Revoke old tokens and issue fresh one
        $user->tokens()->delete();
        $token = $user->createToken('ajo-auth-token')->plainTextToken;

        return [
            'user'  => $user->load('wallet'),
            'token' => $token,
        ];
    }

    public function logout(User $user): void
    {
        $user->currentAccessToken()->delete();
    }

    private function generateAccountNumber(): string
    {
        do {
            // Nigerian-style 10-digit account number
            $number = '7' . str_pad(mt_rand(0, 999999999), 9, '0', STR_PAD_LEFT);
        } while (User::where('account_number', $number)->exists());

        return $number;
    }
}
