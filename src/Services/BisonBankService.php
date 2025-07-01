<?php

namespace App\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Monolog\Logger;
use Ramsey\Uuid\Uuid;

class BisonBankService
{
    private Client $httpClient;
    private Logger $logger;
    private string $baseUrl;
    private string $clientId;
    private string $clientSecret;
    private string $accessToken;

    public function __construct(Logger $logger)
    {
        $this->httpClient = new Client([
            'timeout' => 30,
            'connect_timeout' => 10,
        ]);
        $this->logger = $logger;
        $this->baseUrl = $_ENV['BISON_BANK_API_BASE_URL'] ?? 'https://api.bisonbank.com';
        $this->clientId = $_ENV['BISON_BANK_CLIENT_ID'] ?? '';
        $this->clientSecret = $_ENV['BISON_BANK_CLIENT_SECRET'] ?? '';
        $this->accessToken = '';
    }

    /**
     * Authenticate with Bison Bank API
     */
    public function authenticate(): array
    {
        try {
            $this->logger->info('Authenticating with Bison Bank API');

            $response = $this->httpClient->post($this->baseUrl . '/oauth/token', [
                'headers' => [
                    'Content-Type' => 'application/x-www-form-urlencoded',
                    'Authorization' => 'Basic ' . base64_encode($this->clientId . ':' . $this->clientSecret)
                ],
                'form_params' => [
                    'grant_type' => 'client_credentials',
                    'scope' => 'payment transfer account'
                ]
            ]);

            $responseData = json_decode($response->getBody()->getContents(), true);
            
            if (isset($responseData['access_token'])) {
                $this->accessToken = $responseData['access_token'];
                $this->logger->info('Bison Bank authentication successful');
                
                return [
                    'success' => true,
                    'access_token' => $responseData['access_token'],
                    'expires_in' => $responseData['expires_in'] ?? 3600
                ];
            } else {
                $this->logger->error('Bison Bank authentication failed', [
                    'response' => $responseData
                ]);
                
                return [
                    'success' => false,
                    'error' => 'Authentication failed',
                    'response' => $responseData
                ];
            }

        } catch (GuzzleException $e) {
            $this->logger->error('Bison Bank authentication error', [
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
     * Get account balance
     */
    public function getAccountBalance(string $accountId): array
    {
        try {
            if (empty($this->accessToken)) {
                $authResult = $this->authenticate();
                if (!$authResult['success']) {
                    return $authResult;
                }
            }

            $this->logger->info('Getting Bison Bank account balance', [
                'account_id' => $accountId
            ]);

            $response = $this->httpClient->get($this->baseUrl . "/accounts/{$accountId}/balance", [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->accessToken,
                    'Content-Type' => 'application/json'
                ]
            ]);

            $responseData = json_decode($response->getBody()->getContents(), true);
            
            $this->logger->info('Bison Bank balance retrieved successfully', [
                'account_id' => $accountId,
                'status_code' => $response->getStatusCode()
            ]);

            return [
                'success' => $response->getStatusCode() === 200,
                'data' => $responseData
            ];

        } catch (GuzzleException $e) {
            $this->logger->error('Failed to get Bison Bank account balance', [
                'account_id' => $accountId,
                'error' => $e->getMessage()
            ]);

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
            if (empty($this->accessToken)) {
                $authResult = $this->authenticate();
                if (!$authResult['success']) {
                    return $authResult;
                }
            }

            $this->logger->info('Getting Bison Bank account details', [
                'account_id' => $accountId
            ]);

            $response = $this->httpClient->get($this->baseUrl . "/accounts/{$accountId}", [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->accessToken,
                    'Content-Type' => 'application/json'
                ]
            ]);

            $responseData = json_decode($response->getBody()->getContents(), true);
            
            $this->logger->info('Bison Bank account details retrieved successfully', [
                'account_id' => $accountId,
                'status_code' => $response->getStatusCode()
            ]);

            return [
                'success' => $response->getStatusCode() === 200,
                'data' => $responseData
            ];

        } catch (GuzzleException $e) {
            $this->logger->error('Failed to get Bison Bank account details', [
                'account_id' => $accountId,
                'error' => $e->getMessage()
            ]);

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
            if (empty($this->accessToken)) {
                $authResult = $this->authenticate();
                if (!$authResult['success']) {
                    return $authResult;
                }
            }

            $this->logger->info('Creating Bison Bank domestic transfer', [
                'reference' => $transferData['reference'] ?? 'unknown'
            ]);

            $payload = $this->formatDomesticTransferPayload($transferData);

            $response = $this->httpClient->post($this->baseUrl . '/transfers/domestic', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->accessToken,
                    'Content-Type' => 'application/json'
                ],
                'json' => $payload
            ]);

            $responseData = json_decode($response->getBody()->getContents(), true);
            
            $this->logger->info('Bison Bank domestic transfer created successfully', [
                'reference' => $transferData['reference'] ?? 'unknown',
                'transfer_id' => $responseData['transferId'] ?? 'unknown',
                'status_code' => $response->getStatusCode()
            ]);

            return [
                'success' => $response->getStatusCode() === 200 || $response->getStatusCode() === 201,
                'data' => $responseData
            ];

        } catch (GuzzleException $e) {
            $this->logger->error('Bison Bank domestic transfer failed', [
                'reference' => $transferData['reference'] ?? 'unknown',
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
     * Create international transfer (SWIFT)
     */
    public function createInternationalTransfer(array $transferData): array
    {
        try {
            if (empty($this->accessToken)) {
                $authResult = $this->authenticate();
                if (!$authResult['success']) {
                    return $authResult;
                }
            }

            $this->logger->info('Creating Bison Bank international transfer', [
                'reference' => $transferData['reference'] ?? 'unknown'
            ]);

            $payload = $this->formatInternationalTransferPayload($transferData);

            $response = $this->httpClient->post($this->baseUrl . '/transfers/international', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->accessToken,
                    'Content-Type' => 'application/json'
                ],
                'json' => $payload
            ]);

            $responseData = json_decode($response->getBody()->getContents(), true);
            
            $this->logger->info('Bison Bank international transfer created successfully', [
                'reference' => $transferData['reference'] ?? 'unknown',
                'transfer_id' => $responseData['transferId'] ?? 'unknown',
                'status_code' => $response->getStatusCode()
            ]);

            return [
                'success' => $response->getStatusCode() === 200 || $response->getStatusCode() === 201,
                'data' => $responseData
            ];

        } catch (GuzzleException $e) {
            $this->logger->error('Bison Bank international transfer failed', [
                'reference' => $transferData['reference'] ?? 'unknown',
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
     * Get transfer status
     */
    public function getTransferStatus(string $transferId): array
    {
        try {
            if (empty($this->accessToken)) {
                $authResult = $this->authenticate();
                if (!$authResult['success']) {
                    return $authResult;
                }
            }

            $this->logger->info('Getting Bison Bank transfer status', [
                'transfer_id' => $transferId
            ]);

            $response = $this->httpClient->get($this->baseUrl . "/transfers/{$transferId}", [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->accessToken,
                    'Content-Type' => 'application/json'
                ]
            ]);

            $responseData = json_decode($response->getBody()->getContents(), true);
            
            $this->logger->info('Bison Bank transfer status retrieved successfully', [
                'transfer_id' => $transferId,
                'status_code' => $response->getStatusCode()
            ]);

            return [
                'success' => $response->getStatusCode() === 200,
                'data' => $responseData
            ];

        } catch (GuzzleException $e) {
            $this->logger->error('Failed to get Bison Bank transfer status', [
                'transfer_id' => $transferId,
                'error' => $e->getMessage()
            ]);

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
            if (empty($this->accessToken)) {
                $authResult = $this->authenticate();
                if (!$authResult['success']) {
                    return $authResult;
                }
            }

            $queryString = http_build_query($filters);

            $this->logger->info('Getting Bison Bank transfer list', [
                'filters' => $filters
            ]);

            $response = $this->httpClient->get($this->baseUrl . "/transfers?{$queryString}", [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->accessToken,
                    'Content-Type' => 'application/json'
                ]
            ]);

            $responseData = json_decode($response->getBody()->getContents(), true);
            
            $this->logger->info('Bison Bank transfer list retrieved successfully', [
                'status_code' => $response->getStatusCode()
            ]);

            return [
                'success' => $response->getStatusCode() === 200,
                'data' => $responseData
            ];

        } catch (GuzzleException $e) {
            $this->logger->error('Failed to get Bison Bank transfer list', [
                'error' => $e->getMessage()
            ]);

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
            if (empty($this->accessToken)) {
                $authResult = $this->authenticate();
                if (!$authResult['success']) {
                    return $authResult;
                }
            }

            $queryString = http_build_query($filters);

            $this->logger->info('Getting Bison Bank account transactions', [
                'account_id' => $accountId,
                'filters' => $filters
            ]);

            $response = $this->httpClient->get($this->baseUrl . "/accounts/{$accountId}/transactions?{$queryString}", [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->accessToken,
                    'Content-Type' => 'application/json'
                ]
            ]);

            $responseData = json_decode($response->getBody()->getContents(), true);
            
            $this->logger->info('Bison Bank account transactions retrieved successfully', [
                'account_id' => $accountId,
                'status_code' => $response->getStatusCode()
            ]);

            return [
                'success' => $response->getStatusCode() === 200,
                'data' => $responseData
            ];

        } catch (GuzzleException $e) {
            $this->logger->error('Failed to get Bison Bank account transactions', [
                'account_id' => $accountId,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    private function formatDomesticTransferPayload(array $transferData): array
    {
        return [
            'sourceAccount' => $transferData['sourceAccount'],
            'destinationAccount' => [
                'iban' => $transferData['destinationIban'],
                'name' => $transferData['destinationName'],
                'description' => $transferData['description'] ?? ''
            ],
            'amount' => [
                'value' => $transferData['amount'],
                'currency' => $transferData['currency'] ?? 'EUR'
            ],
            'reference' => $transferData['reference'] ?? Uuid::uuid4()->toString(),
            'executionDate' => $transferData['executionDate'] ?? date('Y-m-d'),
            'priority' => $transferData['priority'] ?? 'NORMAL'
        ];
    }

    private function formatInternationalTransferPayload(array $transferData): array
    {
        return [
            'sourceAccount' => $transferData['sourceAccount'],
            'destinationAccount' => [
                'swiftCode' => $transferData['swiftCode'],
                'iban' => $transferData['destinationIban'],
                'name' => $transferData['destinationName'],
                'address' => $transferData['destinationAddress'] ?? '',
                'city' => $transferData['destinationCity'] ?? '',
                'country' => $transferData['destinationCountry'] ?? '',
                'bankName' => $transferData['bankName'] ?? '',
                'bankAddress' => $transferData['bankAddress'] ?? ''
            ],
            'amount' => [
                'value' => $transferData['amount'],
                'currency' => $transferData['currency'] ?? 'EUR'
            ],
            'reference' => $transferData['reference'] ?? Uuid::uuid4()->toString(),
            'executionDate' => $transferData['executionDate'] ?? date('Y-m-d'),
            'priority' => $transferData['priority'] ?? 'NORMAL',
            'charges' => $transferData['charges'] ?? 'SHA'
        ];
    }

    /**
     * Validate API credentials
     */
    public function validateCredentials(): array
    {
        try {
            $authResult = $this->authenticate();
            
            if ($authResult['success']) {
                // Try to get account list to verify full access
                $response = $this->httpClient->get($this->baseUrl . '/accounts', [
                    'headers' => [
                        'Authorization' => 'Bearer ' . $this->accessToken,
                        'Content-Type' => 'application/json'
                    ]
                ]);

                return [
                    'success' => $response->getStatusCode() === 200,
                    'message' => 'API credentials are valid'
                ];
            }

            return $authResult;

        } catch (GuzzleException $e) {
            return [
                'success' => false,
                'error' => 'Invalid API credentials: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Get supported currencies and transfer types
     */
    public function getSupportedFeatures(): array
    {
        return [
            'currencies' => ['EUR', 'USD', 'GBP', 'CHF', 'JPY', 'CAD', 'AUD', 'NZD'],
            'transfer_types' => ['DOMESTIC', 'INTERNATIONAL'],
            'priorities' => ['NORMAL', 'URGENT'],
            'charges' => ['SHA', 'OUR', 'BEN']
        ];
    }
} 