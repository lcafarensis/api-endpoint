<?php

namespace App\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Monolog\Logger;

class ExternalBankService
{
    private Client $httpClient;
    private Logger $logger;
    private array $config;

    public function __construct(Logger $logger)
    {
        $this->httpClient = new Client([
            'timeout' => 30,
            'connect_timeout' => 10,
        ]);
        $this->logger = $logger;
        $this->config = [
            'bri' => [
                'base_url' => $_ENV['BRI_API_BASE_URL'] ?? 'https://partner.api.bri.co.id/sandbox/v2',
                'api_key' => $_ENV['BRI_API_KEY'] ?? '2RKVWPLXIERK3WM',
                'endpoints' => [
                    'transfer' => '/transfer/external'
                ]
            ]
        ];
    }

    public function initiateTransfer(array $transferData): array
    {
        try {
            $this->logger->info('Initiating external transfer', ['transfer_id' => $transferData['transfer_request_id']]);

            $payload = $this->formatTransferPayload($transferData);
            
            $response = $this->httpClient->post($this->config['bri']['base_url'] . $this->config['bri']['endpoints']['transfer'], [
                'headers' => [
                    'x-api-key' => $this->config['bri']['api_key'],
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json'
                ],
                'json' => $payload
            ]);

            $responseData = json_decode($response->getBody()->getContents(), true);
            
            $this->logger->info('External transfer response received', [
                'transfer_id' => $transferData['transfer_request_id'],
                'status_code' => $response->getStatusCode(),
                'response' => $responseData
            ]);

            return [
                'success' => $response->getStatusCode() === 200,
                'status_code' => $response->getStatusCode(),
                'data' => $responseData,
                'external_reference' => $responseData['reference_id'] ?? null
            ];

        } catch (GuzzleException $e) {
            $this->logger->error('External transfer failed', [
                'transfer_id' => $transferData['transfer_request_id'],
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

    private function formatTransferPayload(array $transferData): array
    {
        return [
            'CashTransfer.v1' => [
                'Sending Name' => $transferData['sending_name'],
                'SendingAccount' => $transferData['sending_account'],
                'Receiving Name' => $transferData['receiving_name'],
                'Receiving Account' => $transferData['receiving_account'],
                'Datetime' => date('Y-m-d H:i:s'),
                'Amount' => number_format($transferData['amount'], 0, '', '.'),
                'Receiving Currency' => $transferData['receiving_currency'],
                'Sending Currency' => $transferData['sending_currency'],
                'Description' => $transferData['description'] ?? 'Your transaction is successful',
                'Transfer RequestID' => $transferData['transfer_request_id'],
                'Receiving Institution' => 'BRI',
                'Sending Institution' => $transferData['sending_institution'] ?? 'DEUTSCHE BANK AG'
            ]
        ];
    }

    public function getTransferStatus(string $externalReference): array
    {
        try {
            $response = $this->httpClient->get($this->config['bri']['base_url'] . '/transfer/status/' . $externalReference, [
                'headers' => [
                    'x-api-key' => $this->config['bri']['api_key'],
                    'Accept' => 'application/json'
                ]
            ]);

            $responseData = json_decode($response->getBody()->getContents(), true);

            return [
                'success' => true,
                'data' => $responseData
            ];

        } catch (GuzzleException $e) {
            $this->logger->error('Failed to get transfer status', [
                'external_reference' => $externalReference,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    public function validateApiCredentials(): array
    {
        try {
            $response = $this->httpClient->get($this->config['bri']['base_url'] . '/account/balance', [
                'headers' => [
                    'x-api-key' => $this->config['bri']['api_key'],
                    'Accept' => 'application/json'
                ]
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
} 