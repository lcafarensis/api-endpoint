<?php

namespace App\Controllers;

use App\Models\Transfer;
use App\Services\ExternalBankService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Monolog\Logger;

class TransferController
{
    private Transfer $transferModel;
    private ExternalBankService $externalBankService;
    private Logger $logger;

    public function __construct(Transfer $transferModel, ExternalBankService $externalBankService, Logger $logger)
    {
        $this->transferModel = $transferModel;
        $this->externalBankService = $externalBankService;
        $this->logger = $logger;
    }

    public function initiateTransfer(Request $request, Response $response): Response
    {
        try {
            $data = $request->getParsedBody();
            
            // Validate input data
            $errors = $this->transferModel->validateTransferData($data);
            if (!empty($errors)) {
                return $response
                    ->withStatus(400)
                    ->withHeader('Content-Type', 'application/json')
                    ->withBody($response->getBody()->write(json_encode([
                        'success' => false,
                        'errors' => $errors
                    ])));
            }

            // Create transfer record
            $transfer = $this->transferModel->create($data);
            
            // Initiate external transfer
            $externalResult = $this->externalBankService->initiateTransfer($transfer);
            
            if ($externalResult['success']) {
                $this->transferModel->updateStatus(
                    $transfer['transfer_request_id'], 
                    'processing', 
                    $externalResult
                );
                
                $this->logger->info('Transfer initiated successfully', [
                    'transfer_id' => $transfer['transfer_request_id'],
                    'external_reference' => $externalResult['external_reference']
                ]);

                return $response
                    ->withStatus(200)
                    ->withHeader('Content-Type', 'application/json')
                    ->withBody($response->getBody()->write(json_encode([
                        'success' => true,
                        'message' => 'Transfer initiated successfully',
                        'data' => [
                            'transfer_request_id' => $transfer['transfer_request_id'],
                            'status' => 'processing',
                            'external_reference' => $externalResult['external_reference']
                        ]
                    ])));
            } else {
                $this->transferModel->updateStatus(
                    $transfer['transfer_request_id'], 
                    'failed', 
                    $externalResult
                );

                return $response
                    ->withStatus(500)
                    ->withHeader('Content-Type', 'application/json')
                    ->withBody($response->getBody()->write(json_encode([
                        'success' => false,
                        'message' => 'External transfer failed',
                        'error' => $externalResult['error']
                    ])));
            }

        } catch (\Exception $e) {
            $this->logger->error('Transfer initiation failed', [
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

    public function getTransferStatus(Request $request, Response $response, array $args): Response
    {
        try {
            $transferRequestId = $args['transfer_request_id'];
            $transfer = $this->transferModel->findByTransferRequestId($transferRequestId);

            if (!$transfer) {
                return $response
                    ->withStatus(404)
                    ->withHeader('Content-Type', 'application/json')
                    ->withBody($response->getBody()->write(json_encode([
                        'success' => false,
                        'message' => 'Transfer not found'
                    ])));
            }

            return $response
                ->withStatus(200)
                ->withHeader('Content-Type', 'application/json')
                ->withBody($response->getBody()->write(json_encode([
                    'success' => true,
                    'data' => $transfer
                ])));

        } catch (\Exception $e) {
            $this->logger->error('Failed to get transfer status', [
                'transfer_id' => $args['transfer_request_id'] ?? 'unknown',
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

    public function getAllTransfers(Request $request, Response $response): Response
    {
        try {
            $transfers = $this->transferModel->getAll();

            return $response
                ->withStatus(200)
                ->withHeader('Content-Type', 'application/json')
                ->withBody($response->getBody()->write(json_encode([
                    'success' => true,
                    'data' => $transfers
                ])));

        } catch (\Exception $e) {
            $this->logger->error('Failed to get all transfers', [
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

    public function confirmFundCredit(Request $request, Response $response, array $args): Response
    {
        try {
            $transferRequestId = $args['transfer_request_id'];
            $data = $request->getParsedBody();

            $transfer = $this->transferModel->findByTransferRequestId($transferRequestId);
            if (!$transfer) {
                return $response
                    ->withStatus(404)
                    ->withHeader('Content-Type', 'application/json')
                    ->withBody($response->getBody()->write(json_encode([
                        'success' => false,
                        'message' => 'Transfer not found'
                    ])));
            }

            // Validate payout account data
            if (empty($data['sender_account']) || empty($data['paymaster_account'])) {
                return $response
                    ->withStatus(400)
                    ->withHeader('Content-Type', 'application/json')
                    ->withBody($response->getBody()->write(json_encode([
                        'success' => false,
                        'message' => 'Sender account and paymaster account are required'
                    ])));
            }

            // Update transfer status to completed
            $this->transferModel->updateStatus($transferRequestId, 'completed');

            // Create payout account record
            $db = \App\Config\Database::getInstance();
            $db->executeStatement(
                "INSERT INTO payout_accounts (transfer_id, sender_account, paymaster_account, payout_type) VALUES (?, ?, ?, ?)",
                [
                    $transfer['id'],
                    $data['sender_account'],
                    $data['paymaster_account'],
                    $data['payout_type'] ?? 'cash'
                ]
            );

            $this->logger->info('Fund credit confirmed', [
                'transfer_id' => $transferRequestId,
                'payout_type' => $data['payout_type'] ?? 'cash'
            ]);

            return $response
                ->withStatus(200)
                ->withHeader('Content-Type', 'application/json')
                ->withBody($response->getBody()->write(json_encode([
                    'success' => true,
                    'message' => 'Fund credit confirmed successfully',
                    'payout_timeframe' => $this->getPayoutTimeframe($data['payout_type'] ?? 'cash')
                ])));

        } catch (\Exception $e) {
            $this->logger->error('Failed to confirm fund credit', [
                'transfer_id' => $args['transfer_request_id'] ?? 'unknown',
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

    private function getPayoutTimeframe(string $payoutType): string
    {
        return match($payoutType) {
            'crypto' => '4-24 hours',
            'cash' => '72 hours',
            default => '72 hours'
        };
    }
} 