<?php

namespace App\Services;

use Razorpay\Api\Api;
use Exception;
use Illuminate\Support\Facades\Log;

class RazorpayPayoutService
{
    protected $api;
    protected $key;
    protected $secret;
    protected $accountNumber;
    protected $baseUrl;

    public function __construct()
    {
        $this->key = config('services.razorpay.key_id');
        $this->secret = config('services.razorpay.key_secret');
        $this->accountNumber = config('services.razorpay.account_number');
        $this->baseUrl = config('services.razorpay.base_url', 'https://api.razorpay.com/v1/');

        if ($this->key && $this->secret) {
            $this->api = new Api($this->key, $this->secret);
        }
    }

    /**
     * Make a direct API call via CURL (used for features missing in SDK like RazorpayX)
     */
    protected function callApi($endpoint, $method = 'POST', $data = [])
    {
        $url = rtrim($this->baseUrl, '/') . '/' . ltrim($endpoint, '/');
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_USERPWD, $this->key . ':' . $this->secret);

        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        }

        $result = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            throw new Exception("CURL Error: " . $error);
        }

        $response = json_decode($result);

        if ($httpCode >= 400) {
            $message = $response->error->description ?? "API Error ($httpCode)";
            throw new Exception($message);
        }

        return $response;
    }

    /**
     * Create a Contact in Razorpay
     */
    public function createContact($name, $email, $phone, $referenceId)
    {
        try {
            $contactData = [
                'name' => $name,
                'email' => $email,
                'contact' => $phone,
                'type' => 'vendor',
                'reference_id' => (string) $referenceId,
            ];

            return $this->callApi('contacts', 'POST', $contactData);
        } catch (Exception $e) {
            Log::error('Razorpay Create Contact Error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Create a Fund Account (Bank Account)
     */
    public function createFundAccount($contactId, $accountName, $accountNumber, $ifsc)
    {
        try {
            $fundAccountData = [
                'contact_id' => $contactId,
                'account_type' => 'bank_account',
                'bank_account' => [
                    'name' => $accountName,
                    'account_number' => $accountNumber,
                    'ifsc' => $ifsc,
                ],
            ];

            return $this->callApi('fund_accounts', 'POST', $fundAccountData);
        } catch (Exception $e) {
            Log::error('Razorpay Create Fund Account Error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Initiate a Payout
     */
    public function createPayout($fundAccountId, $amount, $currency = 'INR', $mode = 'IMPS', $purpose = 'payout', $narrative = '')
    {
        try {
            // Amount should be in paise
            $amountInPaise = $amount * 100;

            $payoutData = [
                'account_number' => $this->accountNumber,
                'fund_account_id' => $fundAccountId,
                'amount' => $amountInPaise,
                'currency' => $currency,
                'mode' => $mode,
                'purpose' => $purpose,
                'queue_if_low_balance' => true,
                'narration' => $narrative ?: 'Payout from Matrimony',
            ];

            return $this->callApi('payouts', 'POST', $payoutData);
        } catch (Exception $e) {
            Log::error('Razorpay Create Payout Error: ' . $e->getMessage());
            throw $e;
        }
    }
}
