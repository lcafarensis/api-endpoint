<?php

/**
 * Example Hambit API Client
 * 
 * This script demonstrates how to interact with the Hambit crypto-fiat exchange API
 * using PHP cURL functions.
 */

class HambitApiClient
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

    public function createFiatToCryptoOrder(array $orderData): array
    {
        return $this->makeRequest('POST', '/api/hambit/fiat-to-crypto', $orderData);
    }

    public function createCryptoToFiatOrder(array $orderData): array
    {
        return $this->makeRequest('POST', '/api/hambit/crypto-to-fiat', $orderData);
    }

    public function getOrderDetails(string $orderId): array
    {
        return $this->makeRequest('GET', "/api/hambit/order/{$orderId}");
    }

    public function getOrderList(array $filters = []): array
    {
        $queryString = http_build_query($filters);
        return $this->makeRequest('GET', "/api/hambit/orders?{$queryString}");
    }

    public function getQuotes(array $quoteData): array
    {
        return $this->makeRequest('POST', '/api/hambit/quotes', $quoteData);
    }

    public function getSupportedCurrencies(): array
    {
        return $this->makeRequest('GET', '/api/hambit/currencies');
    }

    public function validateCredentials(): array
    {
        return $this->makeRequest('GET', '/api/hambit/validate-credentials');
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
    echo "🔧 Hambit API Client Example\n";
    echo "============================\n\n";

    $client = new HambitApiClient('http://localhost:8000');

    // 1. Get supported currencies
    echo "1. Get Supported Currencies:\n";
    $currencies = $client->getSupportedCurrencies();
    print_r($currencies);
    echo "\n";

    // 2. Get quotes for INR to USDT
    echo "2. Get Quotes (INR to USDT):\n";
    $quoteData = [
        'chainType' => 'BSC',
        'tokenType' => 'USDT',
        'currencyType' => 'INR',
        'tokenAmount' => '1'
    ];
    $quotes = $client->getQuotes($quoteData);
    print_r($quotes);
    echo "\n";

    // 3. Create fiat-to-crypto order (INR to USDT)
    echo "3. Create Fiat-to-Crypto Order (INR to USDT):\n";
    $fiatToCryptoData = [
        'externalOrderId' => 'TEST_' . time(),
        'chainType' => 'BSC',
        'tokenType' => 'USDT',
        'addressTo' => '0xa8666442fA7583F783a169CC9F3333333360295E8',
        'tokenAmount' => '1',
        'currencyType' => 'INR',
        'payType' => 'BANK',
        'remark' => 'Test fiat-to-crypto order',
        'notifyUrl' => 'https://your-callback-url.com/webhook'
    ];
    $fiatToCryptoResult = $client->createFiatToCryptoOrder($fiatToCryptoData);
    print_r($fiatToCryptoResult);
    echo "\n";

    if ($fiatToCryptoResult['success']) {
        $orderId = $fiatToCryptoResult['data']['orderId'] ?? null;
        echo "Order created: {$orderId}\n\n";

        // 4. Get order details
        echo "4. Get Order Details:\n";
        $orderDetails = $client->getOrderDetails($orderId);
        print_r($orderDetails);
        echo "\n";
    }

    // 5. Create crypto-to-fiat order (USDT to INR)
    echo "5. Create Crypto-to-Fiat Order (USDT to INR):\n";
    $cryptoToFiatData = [
        'externalOrderId' => 'TEST_' . (time() + 1),
        'chainType' => 'BSC',
        'tokenType' => 'USDT',
        'addressFrom' => '0xa8666442fA7583F783a169CC9F3333333360295E8',
        'tokenAmount' => '1',
        'currencyType' => 'INR',
        'payType' => 'BANK',
        'remark' => 'Test crypto-to-fiat order',
        'notifyUrl' => 'https://your-callback-url.com/webhook'
    ];
    $cryptoToFiatResult = $client->createCryptoToFiatOrder($cryptoToFiatData);
    print_r($cryptoToFiatResult);
    echo "\n";

    // 6. Get order list
    echo "6. Get Order List:\n";
    $orderList = $client->getOrderList(['page' => 1, 'size' => 5]);
    print_r($orderList);
    echo "\n";

    // 7. Validate credentials
    echo "7. Validate Credentials:\n";
    $validateResult = $client->validateCredentials();
    print_r($validateResult);
    echo "\n";

    // 8. Example for different currencies
    echo "8. Example for BRL (Brazil):\n";
    $brlQuoteData = [
        'chainType' => 'BSC',
        'tokenType' => 'USDT',
        'currencyType' => 'BRL',
        'tokenAmount' => '10'
    ];
    $brlQuotes = $client->getQuotes($brlQuoteData);
    print_r($brlQuotes);
    echo "\n";

    echo "9. Example for VND (Vietnam):\n";
    $vndQuoteData = [
        'chainType' => 'BSC',
        'tokenType' => 'USDT',
        'currencyType' => 'VND',
        'tokenAmount' => '1'
    ];
    $vndQuotes = $client->getQuotes($vndQuoteData);
    print_r($vndQuotes);
    echo "\n";
} 