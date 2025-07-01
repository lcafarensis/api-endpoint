<?php

namespace App\Controllers;

use App\Services\BisonBankService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Monolog\Logger;

class BisonBankController
{
    private BisonBankService $bisonBankService;
    private Logger $logger;

    public function __construct(BisonBankService $bisonBankService, Logger $logger)
    {
        $this->bisonBankService = $bisonBankService;
        $this->logger = $logger;
    }

    /**
     * Get account balance
     */
    public function getAccountBalance(Request $request, Response $response, array $args): Response
    {
        try {
            $accountId = $args['account_id'];
            $result = $this->bisonBankService->getAccountBalance($accountId);

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
                        'message' => 'Failed to get account balance',
                        'error' => $result['error']
                    ])));
            }

        } catch (\Exception $e) {
            $this->logger->error('Failed to get Bison Bank account balance', [
                'account_id' => $args['account_id'] ?? 'unknown',
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
     * Get account details
     */
    public function getAccountDetails(Request $request, Response $response, array $args): Response
    {
        try {
            $accountId = $args['account_id'];
            $result = $this->bisonBankService->getAccountDetails($accountId);

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
                        'message' => 'Failed to get account details',
                        'error' => $result['error']
                    ])));
            }

        } catch (\Exception $e) {
            $this->logger->error('Failed to get Bison Bank account details', [
                'account_id' => $args['account_id'] ?? 'unknown',
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
     * Create domestic transfer
     */
    public function createDomesticTransfer(Request $request, Response $response): Response
    {
        try {
            $data = $request->getParsedBody();
            
            // Validate required fields
            $errors = $this->validateDomesticTransferData($data);
            if (!empty($errors)) {
                return $response
                    ->withStatus(400)
                    ->withHeader('Content-Type', 'application/json')
                    ->withBody($response->getBody()->write(json_encode([
                        'success' => false,
                        'errors' => $errors
                    ])));
            }

            // Create transfer
            $result = $this->bisonBankService->createDomesticTransfer($data);
            
            if ($result['success']) {
                $this->logger->info('Bison Bank domestic transfer created successfully', [
                    'reference' => $data['reference'] ?? 'unknown',
                    'transfer_id' => $result['data']['transferId'] ?? 'unknown'
                ]);

                return $response
                    ->withStatus(201)
                    ->withHeader('Content-Type', 'application/json')
                    ->withBody($response->getBody()->write(json_encode([
                        'success' => true,
                        'message' => 'Domestic transfer created successfully',
                        'data' => $result['data']
                    ])));
            } else {
                return $response
                    ->withStatus(500)
                    ->withHeader('Content-Type', 'application/json')
                    ->withBody($response->getBody()->write(json_encode([
                        'success' => false,
                        'message' => 'Failed to create domestic transfer',
                        'error' => $result['error'] ?? $result['message']
                    ])));
            }

        } catch (\Exception $e) {
            $this->logger->error('Bison Bank domestic transfer creation failed', [
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
     * Create international transfer
     */
    public function createInternationalTransfer(Request $request, Response $response): Response
    {
        try {
            $data = $request->getParsedBody();
            
            // Validate required fields
            $errors = $this->validateInternationalTransferData($data);
            if (!empty($errors)) {
                return $response
                    ->withStatus(400)
                    ->withHeader('Content-Type', 'application/json')
                    ->withBody($response->getBody()->write(json_encode([
                        'success' => false,
                        'errors' => $errors
                    ])));
            }

            // Create transfer
            $result = $this->bisonBankService->createInternationalTransfer($data);
            
            if ($result['success']) {
                $this->logger->info('Bison Bank international transfer created successfully', [
                    'reference' => $data['reference'] ?? 'unknown',
                    'transfer_id' => $result['data']['transferId'] ?? 'unknown'
                ]);

                return $response
                    ->withStatus(201)
                    ->withHeader('Content-Type', 'application/json')
                    ->withBody($response->getBody()->write(json_encode([
                        'success' => true,
                        'message' => 'International transfer created successfully',
                        'data' => $result['data']
                    ])));
            } else {
                return $response
                    ->withStatus(500)
                    ->withHeader('Content-Type', 'application/json')
                    ->withBody($response->getBody()->write(json_encode([
                        'success' => false,
                        'message' => 'Failed to create international transfer',
                        'error' => $result['error'] ?? $result['message']
                    ])));
            }

        } catch (\Exception $e) {
            $this->logger->error('Bison Bank international transfer creation failed', [
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
     * Get transfer status
     */
    public function getTransferStatus(Request $request, Response $response, array $args): Response
    {
        try {
            $transferId = $args['transfer_id'];
            $result = $this->bisonBankService->getTransferStatus($transferId);

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
                        'message' => 'Transfer not found',
                        'error' => $result['error'] ?? $result['message']
                    ])));
            }

        } catch (\Exception $e) {
            $this->logger->error('Failed to get Bison Bank transfer status', [
                'transfer_id' => $args['transfer_id'] ?? 'unknown',
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
     * Get transfer list
     */
    public function getTransferList(Request $request, Response $response): Response
    {
        try {
            $queryParams = $request->getQueryParams();
            $filters = [
                'page' => $queryParams['page'] ?? 1,
                'size' => $queryParams['size'] ?? 10,
                'status' => $queryParams['status'] ?? null,
                'startDate' => $queryParams['startDate'] ?? null,
                'endDate' => $queryParams['endDate'] ?? null
            ];

            // Remove null values
            $filters = array_filter($filters, function($value) {
                return $value !== null;
            });

            $result = $this->bisonBankService->getTransferList($filters);

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
                        'message' => 'Failed to get transfer list',
                        'error' => $result['error'] ?? $result['message']
                    ])));
            }

        } catch (\Exception $e) {
            $this->logger->error('Failed to get Bison Bank transfer list', [
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
     * Get account transactions
     */
    public function getAccountTransactions(Request $request, Response $response, array $args): Response
    {
        try {
            $accountId = $args['account_id'];
            $queryParams = $request->getQueryParams();
            $filters = [
                'page' => $queryParams['page'] ?? 1,
                'size' => $queryParams['size'] ?? 10,
                'startDate' => $queryParams['startDate'] ?? null,
                'endDate' => $queryParams['endDate'] ?? null
            ];

            // Remove null values
            $filters = array_filter($filters, function($value) {
                return $value !== null;
            });

            $result = $this->bisonBankService->getAccountTransactions($accountId, $filters);

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
                        'message' => 'Failed to get account transactions',
                        'error' => $result['error'] ?? $result['message']
                    ])));
            }

        } catch (\Exception $e) {
            $this->logger->error('Failed to get Bison Bank account transactions', [
                'account_id' => $args['account_id'] ?? 'unknown',
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
     * Get supported features
     */
    public function getSupportedFeatures(Request $request, Response $response): Response
    {
        try {
            $features = $this->bisonBankService->getSupportedFeatures();

            return $response
                ->withStatus(200)
                ->withHeader('Content-Type', 'application/json')
                ->withBody($response->getBody()->write(json_encode([
                    'success' => true,
                    'data' => $features
                ])));

        } catch (\Exception $e) {
            $this->logger->error('Failed to get Bison Bank supported features', [
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

    private function validateDomesticTransferData(array $data): array
    {
        $errors = [];

        if (empty($data['sourceAccount'])) {
            $errors[] = 'sourceAccount is required';
        }

        if (empty($data['destinationIban'])) {
            $errors[] = 'destinationIban is required';
        }

        if (empty($data['destinationName'])) {
            $errors[] = 'destinationName is required';
        }

        if (empty($data['amount'])) {
            $errors[] = 'amount is required';
        }

        if (isset($data['amount']) && (!is_numeric($data['amount']) || $data['amount'] <= 0)) {
            $errors[] = 'amount must be a positive number';
        }

        return $errors;
    }

    private function validateInternationalTransferData(array $data): array
    {
        $errors = [];

        if (empty($data['sourceAccount'])) {
            $errors[] = 'sourceAccount is required';
        }

        if (empty($data['swiftCode'])) {
            $errors[] = 'swiftCode is required';
        }

        if (empty($data['destinationIban'])) {
            $errors[] = 'destinationIban is required';
        }

        if (empty($data['destinationName'])) {
            $errors[] = 'destinationName is required';
        }

        if (empty($data['amount'])) {
            $errors[] = 'amount is required';
        }

        if (isset($data['amount']) && (!is_numeric($data['amount']) || $data['amount'] <= 0)) {
            $errors[] = 'amount must be a positive number';
        }

        if (empty($data['destinationCountry'])) {
            $errors[] = 'destinationCountry is required';
        }

        return $errors;
    }
} 