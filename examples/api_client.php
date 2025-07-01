<?php

/**
 * Example API Client for Fund Transfer API
 * 
 * This script demonstrates how to interact with the Fund Transfer API
 * using PHP cURL functions.
 */

class FundTransferApiClient
{
    private string $baseUrl;
    private string $apiKey;

    public function __construct(string $baseUrl, string $apiKey = '')
    {
        $this->baseUrl = rtrim($baseUrl, '/');
        $this->apiKey = $apiKey;
    }

    public function setApiKey(string $apiKey): void
    {
        $this->apiKey = $apiKey;
    }

    public function generateApiKey(string $username, string $institutionName): array
    {
        $data = [
            'username' => $username,
            'institution_name' => $institutionName
        ];

        return $this->makeRequest('POST', '/api/generate-key', $data);
    }

    public function initiateTransfer(array $transferData): array
    {
        return $this->makeRequest('POST', '/api/transfer/initiate', $transferData);
    }

    public function getTransferStatus(string $transferRequestId): array
    {
        return $this->makeRequest('GET', "/api/transfer/{$transferRequestId}/status");
    }

    public function getAllTransfers(): array
    {
        return $this->makeRequest('GET', '/api/transfers');
    }

    public function confirmFundCredit(string $transferRequestId, array $payoutData): array
    {
        return $this->makeRequest('POST', "/api/transfer/{$transferRequestId}/confirm-credit", $payoutData);
    }

    public function validateCredentials(): array
    {
        return $this->makeRequest('GET', '/api/validate-credentials');
    }

    public function healthCheck(): array
    {
        return $this->makeRequest('GET', '/health');
    }

    private function makeRequest(string $method, string $endpoint, array $data = []): array
    {
        $url = $this->baseUrl . $endpoint;
        
        $ch = curl_init();
        
        $headers = ['Content-Type: application/json'];
        if (!empty($this->apiKey)) {
            $headers[] = 'x-api-key: ' . $this->apiKey;
        }

        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_CUSTOMREQUEST => $method
        ]);

        if ($method === 'POST' && !empty($data)) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        
        curl_close($ch);

        if ($error) {
            return [
                'success' => false,
                'error' => 'cURL Error: ' . $error
            ];
        }

        $result = json_decode($response, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return [
                'success' => false,
                'error' => 'Invalid JSON response: ' . $response
            ];
        }

        return $result;
    }
}

// Example usage
if (php_sapi_name() === 'cli') {
    echo "🔧 Fund Transfer API Client Example\n";
    echo "==================================\n\n";

    $client = new FundTransferApiClient('http://localhost:8000');

    // 1. Health check
    echo "1. Health Check:\n";
    $health = $client->healthCheck();
    print_r($health);
    echo "\n";

    // 2. Generate API key
    echo "2. Generate API Key:\n";
    $apiKeyResult = $client->generateApiKey('deutsche_bank', 'DEUTSCHE BANK AG');
    print_r($apiKeyResult);
    echo "\n";

    if ($apiKeyResult['success']) {
        $apiKey = $apiKeyResult['data']['api_key'];
        $client->setApiKey($apiKey);
        echo "API Key generated: {$apiKey}\n\n";

        // 3. Initiate transfer
        echo "3. Initiate Transfer:\n";
        $transferData = [
            'sending_name' => 'DEUTSCHE BANK AG',
            'sending_account' => '1234567890',
            'receiving_name' => 'PT JUMINDO INDAH PERKASA',
            'receiving_account' => '007501002373309',
            'amount' => 500000000,
            'sending_currency' => 'EUR',
            'receiving_currency' => 'EUR',
            'description' => 'Your transaction is successful'
        ];

        $transferResult = $client->initiateTransfer($transferData);
        print_r($transferResult);
        echo "\n";

        if ($transferResult['success']) {
            $transferRequestId = $transferResult['data']['transfer_request_id'];
            echo "Transfer initiated: {$transferRequestId}\n\n";

            // 4. Get transfer status
            echo "4. Get Transfer Status:\n";
            $statusResult = $client->getTransferStatus($transferRequestId);
            print_r($statusResult);
            echo "\n";

            // 5. Confirm fund credit
            echo "5. Confirm Fund Credit:\n";
            $payoutData = [
                'sender_account' => '1234567890',
                'paymaster_account' => '0987654321',
                'payout_type' => 'crypto'
            ];

            $confirmResult = $client->confirmFundCredit($transferRequestId, $payoutData);
            print_r($confirmResult);
            echo "\n";

            // 6. Get all transfers
            echo "6. Get All Transfers:\n";
            $allTransfers = $client->getAllTransfers();
            print_r($allTransfers);
            echo "\n";
        }
    }

    // 7. Validate external credentials
    echo "7. Validate External Credentials:\n";
    $validateResult = $client->validateCredentials();
    print_r($validateResult);
    echo "\n";
} 