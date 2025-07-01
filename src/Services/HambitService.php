<?php

namespace App\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Monolog\Logger;
use Ramsey\Uuid\Uuid;

class HambitService
{
    private Client $httpClient;
    private Logger $logger;
    private string $baseUrl;
    private string $accessKey;
    private string $secretKey;

    public function __construct(Logger $logger)
    {
        $this->httpClient = new Client([
            'timeout' => 30,
            'connect_timeout' => 10,
        ]);
        $this->logger = $logger;
        $this->baseUrl = $_ENV['HAMBIT_API_BASE_URL'] ?? 'https://api.hambit.co';
        $this->accessKey = $_ENV['HAMBIT_ACCESS_KEY'] ?? '';
        $this->secretKey = $_ENV['HAMBIT_SECRET_KEY'] ?? '';
    }

    /**
     * Create a fiat-to-crypto exchange order
     */
    public function createFiatToCryptoOrder(array $orderData): array
    {
        try {
            $this->logger->info('Creating Hambit fiat-to-crypto order', [
                'external_order_id' => $orderData['externalOrderId'] ?? 'unknown'
            ]);

            $payload = $this->formatFiatToCryptoPayload($orderData);
            $headers = $this->generateAuthHeaders($payload);

            $response = $this->httpClient->post($this->baseUrl . '/api/v1/exchange/express/trade/buy', [
                'headers' => $headers,
                'json' => $payload
            ]);

            $responseData = json_decode($response->getBody()->getContents(), true);
            
            $this->logger->info('Hambit order created successfully', [
                'external_order_id' => $orderData['externalOrderId'] ?? 'unknown',
                'order_id' => $responseData['data']['orderId'] ?? 'unknown',
                'status_code' => $response->getStatusCode()
            ]);

            return [
                'success' => $responseData['success'] ?? false,
                'code' => $responseData['code'] ?? '',
                'message' => $responseData['msg'] ?? '',
                'data' => $responseData['data'] ?? []
            ];

        } catch (GuzzleException $e) {
            $this->logger->error('Hambit order creation failed', [
                'external_order_id' => $orderData['externalOrderId'] ?? 'unknown',
                'error' => $e->getMessage(),
                'code' => $e->getCode()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'status_code' => $e->getCode()
            ];
        }
    }

    /**
     * Create a crypto-to-fiat exchange order
     */
    public function createCryptoToFiatOrder(array $orderData): array
    {
        try {
            $this->logger->info('Creating Hambit crypto-to-fiat order', [
                'external_order_id' => $orderData['externalOrderId'] ?? 'unknown'
            ]);

            $payload = $this->formatCryptoToFiatPayload($orderData);
            $headers = $this->generateAuthHeaders($payload);

            $response = $this->httpClient->post($this->baseUrl . '/api/v1/exchange/express/trade/sell', [
                'headers' => $headers,
                'json' => $payload
            ]);

            $responseData = json_decode($response->getBody()->getContents(), true);
            
            $this->logger->info('Hambit crypto-to-fiat order created successfully', [
                'external_order_id' => $orderData['externalOrderId'] ?? 'unknown',
                'order_id' => $responseData['data']['orderId'] ?? 'unknown',
                'status_code' => $response->getStatusCode()
            ]);

            return [
                'success' => $responseData['success'] ?? false,
                'code' => $responseData['code'] ?? '',
                'message' => $responseData['msg'] ?? '',
                'data' => $responseData['data'] ?? []
            ];

        } catch (GuzzleException $e) {
            $this->logger->error('Hambit crypto-to-fiat order creation failed', [
                'external_order_id' => $orderData['externalOrderId'] ?? 'unknown',
                'error' => $e->getMessage(),
                'code' => $e->getCode()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'status_code' => $e->getCode()
            ];
        }
    }

    /**
     * Get order details
     */
    public function getOrderDetails(string $orderId): array
    {
        try {
            $headers = $this->generateAuthHeaders([]);

            $response = $this->httpClient->get($this->baseUrl . "/api/v1/exchange/express/trade/order/{$orderId}", [
                'headers' => $headers
            ]);

            $responseData = json_decode($response->getBody()->getContents(), true);

            return [
                'success' => $responseData['success'] ?? false,
                'code' => $responseData['code'] ?? '',
                'message' => $responseData['msg'] ?? '',
                'data' => $responseData['data'] ?? []
            ];

        } catch (GuzzleException $e) {
            $this->logger->error('Failed to get Hambit order details', [
                'order_id' => $orderId,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Get order list
     */
    public function getOrderList(array $filters = []): array
    {
        try {
            $headers = $this->generateAuthHeaders($filters);
            $queryString = http_build_query($filters);

            $response = $this->httpClient->get($this->baseUrl . "/api/v1/exchange/express/trade/orders?{$queryString}", [
                'headers' => $headers
            ]);

            $responseData = json_decode($response->getBody()->getContents(), true);

            return [
                'success' => $responseData['success'] ?? false,
                'code' => $responseData['code'] ?? '',
                'message' => $responseData['msg'] ?? '',
                'data' => $responseData['data'] ?? []
            ];

        } catch (GuzzleException $e) {
            $this->logger->error('Failed to get Hambit order list', [
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Get quotes for fiat-to-crypto
     */
    public function getQuotes(array $quoteData): array
    {
        try {
            $headers = $this->generateAuthHeaders($quoteData);

            $response = $this->httpClient->post($this->baseUrl . '/api/v1/exchange/express/trade/quote', [
                'headers' => $headers,
                'json' => $quoteData
            ]);

            $responseData = json_decode($response->getBody()->getContents(), true);

            return [
                'success' => $responseData['success'] ?? false,
                'code' => $responseData['code'] ?? '',
                'message' => $responseData['msg'] ?? '',
                'data' => $responseData['data'] ?? []
            ];

        } catch (GuzzleException $e) {
            $this->logger->error('Failed to get Hambit quotes', [
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    private function formatFiatToCryptoPayload(array $orderData): array
    {
        return [
            'externalOrderId' => $orderData['externalOrderId'] ?? Uuid::uuid4()->toString(),
            'chainType' => $orderData['chainType'] ?? 'BSC',
            'tokenType' => $orderData['tokenType'] ?? 'USDT',
            'addressTo' => $orderData['addressTo'] ?? '',
            'tokenAmount' => $orderData['tokenAmount'] ?? '',
            'currencyType' => $orderData['currencyType'] ?? 'INR',
            'payType' => $orderData['payType'] ?? 'BANK',
            'remark' => $orderData['remark'] ?? '',
            'notifyUrl' => $orderData['notifyUrl'] ?? '',
            'reviewQuote' => $orderData['reviewQuote'] ?? '0'
        ];
    }

    private function formatCryptoToFiatPayload(array $orderData): array
    {
        return [
            'externalOrderId' => $orderData['externalOrderId'] ?? Uuid::uuid4()->toString(),
            'chainType' => $orderData['chainType'] ?? 'BSC',
            'tokenType' => $orderData['tokenType'] ?? 'USDT',
            'addressFrom' => $orderData['addressFrom'] ?? '',
            'tokenAmount' => $orderData['tokenAmount'] ?? '',
            'currencyType' => $orderData['currencyType'] ?? 'INR',
            'payType' => $orderData['payType'] ?? 'BANK',
            'remark' => $orderData['remark'] ?? '',
            'notifyUrl' => $orderData['notifyUrl'] ?? ''
        ];
    }

    private function generateAuthHeaders(array $payload): array
    {
        $timestamp = round(microtime(true) * 1000);
        $nonce = Uuid::uuid4()->toString();
        
        // Create signature string
        $signatureString = $this->accessKey . $timestamp . $nonce . json_encode($payload);
        $signature = base64_encode(hash_hmac('sha256', $signatureString, $this->secretKey, true));

        return [
            'Content-Type' => 'application/json; charset=utf-8',
            'access_key' => $this->accessKey,
            'timestamp' => $timestamp,
            'nonce' => $nonce,
            'sign' => $signature
        ];
    }

    /**
     * Validate API credentials
     */
    public function validateCredentials(): array
    {
        try {
            $headers = $this->generateAuthHeaders([]);
            
            $response = $this->httpClient->get($this->baseUrl . '/api/v1/exchange/express/trade/orders', [
                'headers' => $headers,
                'query' => ['page' => 1, 'size' => 1]
            ]);

            return [
                'success' => $response->getStatusCode() === 200,
                'message' => 'API credentials are valid'
            ];

        } catch (GuzzleException $e) {
            return [
                'success' => false,
                'error' => 'Invalid API credentials: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Get supported currencies and payment methods
     */
    public function getSupportedCurrencies(): array
    {
        return [
            'INR' => [
                'payType' => ['BANK'],
                'chainType' => ['BSC'],
                'tokenType' => ['USDT']
            ],
            'BRL' => [
                'payType' => ['PIX'],
                'chainType' => ['BSC'],
                'tokenType' => ['USDT']
            ],
            'MXN' => [
                'payType' => ['CASH', 'PAYCASHRECURRENT', 'QRIS'],
                'chainType' => ['BSC'],
                'tokenType' => ['USDT']
            ],
            'VND' => [
                'payType' => ['BANK', 'BANK_SCAN_CODE', 'CARD_TO_CARD', 'MOMO', 'ZALO_PAY', 'VIETTEL_MONEY'],
                'chainType' => ['BSC'],
                'tokenType' => ['USDT']
            ]
        ];
    }
} 