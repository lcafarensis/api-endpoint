<?php

namespace App\Controllers;

use App\Services\HambitService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Monolog\Logger;

class HambitController
{
    private HambitService $hambitService;
    private Logger $logger;

    public function __construct(HambitService $hambitService, Logger $logger)
    {
        $this->hambitService = $hambitService;
        $this->logger = $logger;
    }

    /**
     * Create fiat-to-crypto exchange order
     */
    public function createFiatToCryptoOrder(Request $request, Response $response): Response
    {
        try {
            $data = $request->getParsedBody();
            
            // Validate required fields
            $errors = $this->validateFiatToCryptoData($data);
            if (!empty($errors)) {
                return $response
                    ->withStatus(400)
                    ->withHeader('Content-Type', 'application/json')
                    ->withBody($response->getBody()->write(json_encode([
                        'success' => false,
                        'errors' => $errors
                    ])));
            }

            // Create order
            $result = $this->hambitService->createFiatToCryptoOrder($data);
            
            if ($result['success']) {
                $this->logger->info('Fiat-to-crypto order created successfully', [
                    'external_order_id' => $data['externalOrderId'] ?? 'unknown',
                    'order_id' => $result['data']['orderId'] ?? 'unknown'
                ]);

                return $response
                    ->withStatus(200)
                    ->withHeader('Content-Type', 'application/json')
                    ->withBody($response->getBody()->write(json_encode([
                        'success' => true,
                        'message' => 'Fiat-to-crypto order created successfully',
                        'data' => $result['data']
                    ])));
            } else {
                return $response
                    ->withStatus(500)
                    ->withHeader('Content-Type', 'application/json')
                    ->withBody($response->getBody()->write(json_encode([
                        'success' => false,
                        'message' => 'Failed to create fiat-to-crypto order',
                        'error' => $result['error'] ?? $result['message']
                    ])));
            }

        } catch (\Exception $e) {
            $this->logger->error('Fiat-to-crypto order creation failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return $response
                ->withStatus(500)
                ->withHeader('Content-Type', 'application/json')
                ->withBody($response->getBody()->write(json_encode([
                    'success' => false,
                    'message' => 'Internal server error',
                    'error' => $e->getMessage()
                ])));
        }
    }

    /**
     * Create crypto-to-fiat exchange order
     */
    public function createCryptoToFiatOrder(Request $request, Response $response): Response
    {
        try {
            $data = $request->getParsedBody();
            
            // Validate required fields
            $errors = $this->validateCryptoToFiatData($data);
            if (!empty($errors)) {
                return $response
                    ->withStatus(400)
                    ->withHeader('Content-Type', 'application/json')
                    ->withBody($response->getBody()->write(json_encode([
                        'success' => false,
                        'errors' => $errors
                    ])));
            }

            // Create order
            $result = $this->hambitService->createCryptoToFiatOrder($data);
            
            if ($result['success']) {
                $this->logger->info('Crypto-to-fiat order created successfully', [
                    'external_order_id' => $data['externalOrderId'] ?? 'unknown',
                    'order_id' => $result['data']['orderId'] ?? 'unknown'
                ]);

                return $response
                    ->withStatus(200)
                    ->withHeader('Content-Type', 'application/json')
                    ->withBody($response->getBody()->write(json_encode([
                        'success' => true,
                        'message' => 'Crypto-to-fiat order created successfully',
                        'data' => $result['data']
                    ])));
            } else {
                return $response
                    ->withStatus(500)
                    ->withHeader('Content-Type', 'application/json')
                    ->withBody($response->getBody()->write(json_encode([
                        'success' => false,
                        'message' => 'Failed to create crypto-to-fiat order',
                        'error' => $result['error'] ?? $result['message']
                    ])));
            }

        } catch (\Exception $e) {
            $this->logger->error('Crypto-to-fiat order creation failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return $response
                ->withStatus(500)
                ->withHeader('Content-Type', 'application/json')
                ->withBody($response->getBody()->write(json_encode([
                    'success' => false,
                    'message' => 'Internal server error',
                    'error' => $e->getMessage()
                ])));
        }
    }

    /**
     * Get order details
     */
    public function getOrderDetails(Request $request, Response $response, array $args): Response
    {
        try {
            $orderId = $args['order_id'];
            $result = $this->hambitService->getOrderDetails($orderId);

            if ($result['success']) {
                return $response
                    ->withStatus(200)
                    ->withHeader('Content-Type', 'application/json')
                    ->withBody($response->getBody()->write(json_encode([
                        'success' => true,
                        'data' => $result['data']
                    ])));
            } else {
                return $response
                    ->withStatus(404)
                    ->withHeader('Content-Type', 'application/json')
                    ->withBody($response->getBody()->write(json_encode([
                        'success' => false,
                        'message' => 'Order not found',
                        'error' => $result['error'] ?? $result['message']
                    ])));
            }

        } catch (\Exception $e) {
            $this->logger->error('Failed to get order details', [
                'order_id' => $args['order_id'] ?? 'unknown',
                'error' => $e->getMessage()
            ]);

            return $response
                ->withStatus(500)
                ->withHeader('Content-Type', 'application/json')
                ->withBody($response->getBody()->write(json_encode([
                    'success' => false,
                    'message' => 'Internal server error'
                ])));
        }
    }

    /**
     * Get order list
     */
    public function getOrderList(Request $request, Response $response): Response
    {
        try {
            $queryParams = $request->getQueryParams();
            $filters = [
                'page' => $queryParams['page'] ?? 1,
                'size' => $queryParams['size'] ?? 10,
                'status' => $queryParams['status'] ?? null,
                'startTime' => $queryParams['startTime'] ?? null,
                'endTime' => $queryParams['endTime'] ?? null
            ];

            // Remove null values
            $filters = array_filter($filters, function($value) {
                return $value !== null;
            });

            $result = $this->hambitService->getOrderList($filters);

            if ($result['success']) {
                return $response
                    ->withStatus(200)
                    ->withHeader('Content-Type', 'application/json')
                    ->withBody($response->getBody()->write(json_encode([
                        'success' => true,
                        'data' => $result['data']
                    ])));
            } else {
                return $response
                    ->withStatus(500)
                    ->withHeader('Content-Type', 'application/json')
                    ->withBody($response->getBody()->write(json_encode([
                        'success' => false,
                        'message' => 'Failed to get order list',
                        'error' => $result['error'] ?? $result['message']
                    ])));
            }

        } catch (\Exception $e) {
            $this->logger->error('Failed to get order list', [
                'error' => $e->getMessage()
            ]);

            return $response
                ->withStatus(500)
                ->withHeader('Content-Type', 'application/json')
                ->withBody($response->getBody()->write(json_encode([
                    'success' => false,
                    'message' => 'Internal server error'
                ])));
        }
    }

    /**
     * Get quotes
     */
    public function getQuotes(Request $request, Response $response): Response
    {
        try {
            $data = $request->getParsedBody();
            
            // Validate quote data
            $errors = $this->validateQuoteData($data);
            if (!empty($errors)) {
                return $response
                    ->withStatus(400)
                    ->withHeader('Content-Type', 'application/json')
                    ->withBody($response->getBody()->write(json_encode([
                        'success' => false,
                        'errors' => $errors
                    ])));
            }

            $result = $this->hambitService->getQuotes($data);

            if ($result['success']) {
                return $response
                    ->withStatus(200)
                    ->withHeader('Content-Type', 'application/json')
                    ->withBody($response->getBody()->write(json_encode([
                        'success' => true,
                        'data' => $result['data']
                    ])));
            } else {
                return $response
                    ->withStatus(500)
                    ->withHeader('Content-Type', 'application/json')
                    ->withBody($response->getBody()->write(json_encode([
                        'success' => false,
                        'message' => 'Failed to get quotes',
                        'error' => $result['error'] ?? $result['message']
                    ])));
            }

        } catch (\Exception $e) {
            $this->logger->error('Failed to get quotes', [
                'error' => $e->getMessage()
            ]);

            return $response
                ->withStatus(500)
                ->withHeader('Content-Type', 'application/json')
                ->withBody($response->getBody()->write(json_encode([
                    'success' => false,
                    'message' => 'Internal server error'
                ])));
        }
    }

    /**
     * Get supported currencies
     */
    public function getSupportedCurrencies(Request $request, Response $response): Response
    {
        try {
            $currencies = $this->hambitService->getSupportedCurrencies();

            return $response
                ->withStatus(200)
                ->withHeader('Content-Type', 'application/json')
                ->withBody($response->getBody()->write(json_encode([
                    'success' => true,
                    'data' => $currencies
                ])));

        } catch (\Exception $e) {
            $this->logger->error('Failed to get supported currencies', [
                'error' => $e->getMessage()
            ]);

            return $response
                ->withStatus(500)
                ->withHeader('Content-Type', 'application/json')
                ->withBody($response->getBody()->write(json_encode([
                    'success' => false,
                    'message' => 'Internal server error'
                ])));
        }
    }

    private function validateFiatToCryptoData(array $data): array
    {
        $errors = [];

        if (empty($data['chainType'])) {
            $errors[] = 'chainType is required';
        }

        if (empty($data['tokenType'])) {
            $errors[] = 'tokenType is required';
        }

        if (empty($data['currencyType'])) {
            $errors[] = 'currencyType is required';
        }

        if (empty($data['payType'])) {
            $errors[] = 'payType is required';
        }

        if (empty($data['addressTo'])) {
            $errors[] = 'addressTo is required';
        }

        if (empty($data['tokenAmount'])) {
            $errors[] = 'tokenAmount is required';
        }

        return $errors;
    }

    private function validateCryptoToFiatData(array $data): array
    {
        $errors = [];

        if (empty($data['chainType'])) {
            $errors[] = 'chainType is required';
        }

        if (empty($data['tokenType'])) {
            $errors[] = 'tokenType is required';
        }

        if (empty($data['currencyType'])) {
            $errors[] = 'currencyType is required';
        }

        if (empty($data['payType'])) {
            $errors[] = 'payType is required';
        }

        if (empty($data['addressFrom'])) {
            $errors[] = 'addressFrom is required';
        }

        if (empty($data['tokenAmount'])) {
            $errors[] = 'tokenAmount is required';
        }

        return $errors;
    }

    private function validateQuoteData(array $data): array
    {
        $errors = [];

        if (empty($data['chainType'])) {
            $errors[] = 'chainType is required';
        }

        if (empty($data['tokenType'])) {
            $errors[] = 'tokenType is required';
        }

        if (empty($data['currencyType'])) {
            $errors[] = 'currencyType is required';
        }

        return $errors;
    }
} 