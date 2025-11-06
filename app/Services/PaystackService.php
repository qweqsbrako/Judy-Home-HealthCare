<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PaystackService
{
    protected $secretKey;
    protected $publicKey;
    protected $baseUrl;

    public function __construct()
    {
        $this->secretKey = config('paystack.secretKey');
        $this->publicKey = config('paystack.publicKey');
        $this->baseUrl = config('paystack.paymentUrl');
    }

    /**
     * Initialize a payment transaction
     * 
     * @param array $data
     * @return array
     */
    public function initializeTransaction(array $data): array
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->secretKey,
                'Content-Type' => 'application/json',
            ])->post($this->baseUrl . '/transaction/initialize', [
                'email' => $data['email'],
                'amount' => $data['amount'], // Amount in kobo/pesewas (multiply by 100)
                'reference' => $data['reference'],
                'callback_url' => $data['callback_url'] ?? null,
                'channels' => $data['channels'] ?? config('paystack.paymentChannels'),
                'metadata' => $data['metadata'] ?? [],
                'currency' => $data['currency'] ?? 'GHS', // Ghana Cedis
            ]);

            $result = $response->json();

            if ($response->successful() && $result['status']) {
                return [
                    'success' => true,
                    'message' => 'Transaction initialized successfully',
                    'data' => [
                        'authorization_url' => $result['data']['authorization_url'],
                        'access_code' => $result['data']['access_code'],
                        'reference' => $result['data']['reference'],
                    ],
                ];
            }

            Log::error('Paystack initialization failed', ['response' => $result]);

            return [
                'success' => false,
                'message' => $result['message'] ?? 'Failed to initialize transaction',
            ];

        } catch (\Exception $e) {
            Log::error('Paystack initialization error: ' . $e->getMessage());

            return [
                'success' => false,
                'message' => 'Payment initialization failed. Please try again.',
            ];
        }
    }

    /**
     * Verify a transaction
     * 
     * @param string $reference
     * @return array
     */
    public function verifyTransaction(string $reference): array
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->secretKey,
            ])->get($this->baseUrl . '/transaction/verify/' . $reference);

            $result = $response->json();

            if ($response->successful() && $result['status']) {
                $data = $result['data'];

                return [
                    'success' => true,
                    'message' => 'Transaction verified successfully',
                    'data' => [
                        'reference' => $data['reference'],
                        'amount' => $data['amount'] / 100, // Convert back to main currency
                        'currency' => $data['currency'],
                        'status' => $data['status'],
                        'paid_at' => $data['paid_at'] ?? null,
                        'channel' => $data['channel'],
                        'transaction_id' => $data['id'],
                        'customer' => [
                            'email' => $data['customer']['email'],
                            'customer_code' => $data['customer']['customer_code'],
                        ],
                        'metadata' => $data['metadata'] ?? [],
                    ],
                ];
            }

            Log::warning('Paystack verification failed', ['response' => $result]);

            return [
                'success' => false,
                'message' => $result['message'] ?? 'Transaction verification failed',
            ];

        } catch (\Exception $e) {
            Log::error('Paystack verification error: ' . $e->getMessage());

            return [
                'success' => false,
                'message' => 'Payment verification failed. Please contact support.',
            ];
        }
    }

    /**
     * Get public key for client-side usage
     * 
     * @return string
     */
    public function getPublicKey(): string
    {
        return $this->publicKey;
    }

    /**
     * Get supported payment channels
     * 
     * @return array
     */
    public function getSupportedChannels(): array
    {
        return config('paystack.paymentChannels');
    }
}