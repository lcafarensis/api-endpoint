<?php

/**
 * Bison Bank API Client Example
 * 
 * This example demonstrates how to use the Bison Bank API endpoints
 * for account management and fund transfers.
 */

require_once __DIR__ . '/../vendor/autoload.php';

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

class BisonBankClient
{
    private Client $httpClient;
    private string $baseUrl;
    private string $apiKey;

    public function __construct(string $baseUrl, string $apiKey)
    {
        $this->httpClient = new Client([
            'timeout' => 30,
            'connect_timeout' => 10,
        ]);
        $this->baseUrl = $baseUrl;
        $this->apiKey = $apiKey;
    }

    /**
     * Get supported features
     */
    public function getSupportedFeatures(): array
    {
        try {
            $response = $this->httpClient->get($this->baseUrl . '/api/bison-bank/features', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'Content-Type' => 'application/json'
                ]
            ]);

            return json_decode($response->getBody()->getContents(), true);
        } catch (GuzzleException $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Get account balance
     */
    public function getAccountBalance(string $accountId): array
    {
        try {
            $response = $this->httpClient->get($this->baseUrl . "/api/bison-bank/accounts/{$accountId}/balance", [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'Content-Type' => 'application/json'
                ]
            ]);

            return json_decode($response->getBody()->getContents(), true);
        } catch (GuzzleException $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Get account details
     */
    public function getAccountDetails(string $accountId): array
    {
        try {
            $response = $this->httpClient->get($this->baseUrl . "/api/bison-bank/accounts/{$accountId}/details", [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'Content-Type' => 'application/json'
                ]
            ]);

            return json_decode($response->getBody()->getContents(), true);
        } catch (GuzzleException $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Get account transactions
     */
    public function getAccountTransactions(string $accountId, array $filters = []): array
    {
        try {
            $queryString = http_build_query($filters);
            $response = $this->httpClient->get($this->baseUrl . "/api/bison-bank/accounts/{$accountId}/transactions?{$queryString}", [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'Content-Type' => 'application/json'
                ]
            ]);

            return json_decode($response->getBody()->getContents(), true);
        } catch (GuzzleException $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Create domestic transfer
     */
    public function createDomesticTransfer(array $transferData): array
    {
        try {
            $response = $this->httpClient->post($this->baseUrl . '/api/bison-bank/transfers/domestic', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'Content-Type' => 'application/json'
                ],
                'json' => $transferData
            ]);

            return json_decode($response->getBody()->getContents(), true);
        } catch (GuzzleException $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Create international transfer
     */
    public function createInternationalTransfer(array $transferData): array
    {
        try {
            $response = $this->httpClient->post($this->baseUrl . '/api/bison-bank/transfers/international', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'Content-Type' => 'application/json'
                ],
                'json' => $transferData
            ]);

            return json_decode($response->getBody()->getContents(), true);
        } catch (GuzzleException $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Get transfer status
     */
    public function getTransferStatus(string $transferId): array
    {
        try {
            $response = $this->httpClient->get($this->baseUrl . "/api/bison-bank/transfers/{$transferId}", [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'Content-Type' => 'application/json'
                ]
            ]);

            return json_decode($response->getBody()->getContents(), true);
        } catch (GuzzleException $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Get transfer list
     */
    public function getTransferList(array $filters = []): array
    {
        try {
            $queryString = http_build_query($filters);
            $response = $this->httpClient->get($this->baseUrl . "/api/bison-bank/transfers?{$queryString}", [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'Content-Type' => 'application/json'
                ]
            ]);

            return json_decode($response->getBody()->getContents(), true);
        } catch (GuzzleException $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
}

// Example usage
if (php_sapi_name() === 'cli') {
    echo "=== Bison Bank API Client Example ===\n\n";

    // Initialize client
    $client = new BisonBankClient('http://localhost:8000', 'your_api_key_here');

    // Get supported features
    echo "1. Getting supported features...\n";
    $features = $client->getSupportedFeatures();
    if ($features['success']) {
        echo "Supported currencies: " . implode(', ', $features['data']['currencies']) . "\n";
        echo "Transfer types: " . implode(', ', $features['data']['transfer_types']) . "\n";
        echo "Priorities: " . implode(', ', $features['data']['priorities']) . "\n";
        echo "Charges: " . implode(', ', $features['data']['charges']) . "\n\n";
    } else {
        echo "Error: " . $features['error'] . "\n\n";
    }

    // Get account balance
    echo "2. Getting account balance...\n";
    $balance = $client->getAccountBalance('ACCOUNT123');
    if ($balance['success']) {
        echo "Account balance retrieved successfully\n";
        print_r($balance['data']);
        echo "\n";
    } else {
        echo "Error: " . $balance['error'] . "\n\n";
    }

    // Create domestic transfer
    echo "3. Creating domestic transfer...\n";
    $domesticTransfer = $client->createDomesticTransfer([
        'sourceAccount' => 'ACCOUNT123',
        'destinationIban' => 'PT50003506510000000000033',
        'destinationName' => 'John Doe',
        'amount' => 100.00,
        'currency' => 'EUR',
        'description' => 'Payment for services',
        'reference' => 'TRANSFER-' . time(),
        'priority' => 'NORMAL'
    ]);

    if ($domesticTransfer['success']) {
        echo "Domestic transfer created successfully\n";
        echo "Transfer ID: " . $domesticTransfer['data']['transferId'] . "\n\n";
    } else {
        echo "Error: " . $domesticTransfer['error'] . "\n\n";
    }

    // Create international transfer
    echo "4. Creating international transfer...\n";
    $internationalTransfer = $client->createInternationalTransfer([
        'sourceAccount' => 'ACCOUNT123',
        'swiftCode' => 'CHASUS33',
        'destinationIban' => 'US12345678901234567890',
        'destinationName' => 'Jane Smith',
        'destinationAddress' => '123 Main St',
        'destinationCity' => 'New York',
        'destinationCountry' => 'US',
        'bankName' => 'Chase Bank',
        'bankAddress' => '456 Wall Street',
        'amount' => 500.00,
        'currency' => 'USD',
        'reference' => 'INT-TRANSFER-' . time(),
        'priority' => 'NORMAL',
        'charges' => 'SHA'
    ]);

    if ($internationalTransfer['success']) {
        echo "International transfer created successfully\n";
        echo "Transfer ID: " . $internationalTransfer['data']['transferId'] . "\n\n";
    } else {
        echo "Error: " . $internationalTransfer['error'] . "\n\n";
    }

    // Get transfer list
    echo "5. Getting transfer list...\n";
    $transfers = $client->getTransferList([
        'page' => 1,
        'size' => 10,
        'status' => 'PENDING'
    ]);

    if ($transfers['success']) {
        echo "Transfer list retrieved successfully\n";
        echo "Total transfers: " . count($transfers['data']['transfers']) . "\n\n";
    } else {
        echo "Error: " . $transfers['error'] . "\n\n";
    }

    // Get account transactions
    echo "6. Getting account transactions...\n";
    $transactions = $client->getAccountTransactions('ACCOUNT123', [
        'page' => 1,
        'size' => 5,
        'startDate' => '2024-01-01',
        'endDate' => '2024-12-31'
    ]);

    if ($transactions['success']) {
        echo "Account transactions retrieved successfully\n";
        echo "Total transactions: " . count($transactions['data']['transactions']) . "\n\n";
    } else {
        echo "Error: " . $transactions['error'] . "\n\n";
    }

    echo "=== Example completed ===\n";
} 