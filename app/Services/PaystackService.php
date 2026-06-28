<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\Response;

class PaystackService
{
    private string $baseUrl  = 'https://api.paystack.co';
    private string $secretKey;

    public function __construct()
    {
        $this->secretKey = config('services.paystack.secret_key');
    }

    /**
     * Initialize a payment transaction (returns authorization_url)
     */
    public function initializeTransaction(string $email, float $amount, string $reference, array $metadata = []): array
    {
        $response = Http::withToken($this->secretKey)
            ->post("{$this->baseUrl}/transaction/initialize", [
                'email'     => $email,
                'amount'    => (int) ($amount * 100), // Paystack uses kobo
                'reference' => $reference,
                'currency'  => 'NGN',
                'callback_url' => config('services.paystack.callback_url'),
                'metadata'  => $metadata,
            ]);

        return $this->handleResponse($response);
    }

    /**
     * Verify a transaction using reference
     */
    public function verifyTransaction(string $reference): array
    {
        $response = Http::withToken($this->secretKey)
            ->get("{$this->baseUrl}/transaction/verify/{$reference}");

        return $this->handleResponse($response);
    }

    /**
     * Handle HTTP response from Paystack
     */
    private function handleResponse(Response $response): array
    {
        $data = $response->json();

        if (!$response->successful() || !$data['status']) {
            throw new \Exception($data['message'] ?? 'Paystack request failed');
        }

        return $data;
    }
}
