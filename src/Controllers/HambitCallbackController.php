<?php

namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Monolog\Logger;
use App\Config\Database;

class HambitCallbackController
{
    private Logger $logger;

    public function __construct(Logger $logger)
    {
        $this->logger = $logger;
    }

    /**
     * Handle Hambit callback notifications
     */
    public function handleCallback(Request $request, Response $response): Response
    {
        try {
            $data = $request->getParsedBody();
            
            $this->logger->info('Hambit callback received', [
                'data' => $data
            ]);

            // Validate callback data
            if (!$this->validateCallbackData($data)) {
                $this->logger->error('Invalid callback data received', [
                    'data' => $data
                ]);

                return $response
                    ->withStatus(400)
                    ->withHeader('Content-Type', 'application/json')
                    ->withBody($response->getBody()->write(json_encode([
                        'success' => false,
                        'message' => 'Invalid callback data'
                    ])));
            }

            // Process the callback based on status
            $this->processCallback($data);

            // Return success response to Hambit
            return $response
                ->withStatus(200)
                ->withHeader('Content-Type', 'application/json')
                ->withBody($response->getBody()->write(json_encode([
                    'success' => true,
                    'message' => 'Callback processed successfully'
                ])));

        } catch (\Exception $e) {
            $this->logger->error('Callback processing failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
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

    private function validateCallbackData(array $data): bool
    {
        // Check for required fields based on Hambit callback format
        $requiredFields = ['orderId', 'externalOrderId', 'status'];
        
        foreach ($requiredFields as $field) {
            if (!isset($data[$field])) {
                return false;
            }
        }

        return true;
    }

    private function processCallback(array $data): void
    {
        $orderId = $data['orderId'];
        $externalOrderId = $data['externalOrderId'];
        $status = $data['status'];

        $this->logger->info('Processing Hambit callback', [
            'order_id' => $orderId,
            'external_order_id' => $externalOrderId,
            'status' => $status
        ]);

        // Update order status in database
        $this->updateOrderStatus($orderId, $externalOrderId, $status, $data);

        // Handle different status types
        switch (strtoupper($status)) {
            case 'SUCCESS':
            case 'COMPLETED':
                $this->handleSuccessfulOrder($data);
                break;
                
            case 'FAILED':
            case 'CANCELLED':
                $this->handleFailedOrder($data);
                break;
                
            case 'PENDING':
            case 'PROCESSING':
                $this->handlePendingOrder($data);
                break;
                
            default:
                $this->logger->warning('Unknown order status', [
                    'status' => $status,
                    'order_id' => $orderId
                ]);
                break;
        }
    }

    private function updateOrderStatus(string $orderId, string $externalOrderId, string $status, array $data): void
    {
        try {
            $db = Database::getInstance();
            
            // Check if order exists in our database
            $existingOrder = $db->fetchAssociative(
                "SELECT id FROM hambit_orders WHERE order_id = ? OR external_order_id = ?",
                [$orderId, $externalOrderId]
            );

            if ($existingOrder) {
                // Update existing order
                $db->executeStatement(
                    "UPDATE hambit_orders SET 
                        status = ?, 
                        updated_at = CURRENT_TIMESTAMP,
                        callback_data = ?
                     WHERE order_id = ? OR external_order_id = ?",
                    [$status, json_encode($data), $orderId, $externalOrderId]
                );
            } else {
                // Create new order record
                $db->executeStatement(
                    "INSERT INTO hambit_orders (
                        order_id, external_order_id, status, callback_data, created_at, updated_at
                    ) VALUES (?, ?, ?, ?, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)",
                    [$orderId, $externalOrderId, $status, json_encode($data)]
                );
            }

            $this->logger->info('Order status updated', [
                'order_id' => $orderId,
                'external_order_id' => $externalOrderId,
                'status' => $status
            ]);

        } catch (\Exception $e) {
            $this->logger->error('Failed to update order status', [
                'order_id' => $orderId,
                'error' => $e->getMessage()
            ]);
        }
    }

    private function handleSuccessfulOrder(array $data): void
    {
        $this->logger->info('Processing successful order', [
            'order_id' => $data['orderId'],
            'external_order_id' => $data['externalOrderId']
        ]);

        // Here you can add your business logic for successful orders
        // For example:
        // - Send confirmation email to user
        // - Update user balance
        // - Trigger payout process
        // - Send notification to admin
        
        // Example: Send webhook to your application
        $this->sendWebhookNotification($data, 'success');
    }

    private function handleFailedOrder(array $data): void
    {
        $this->logger->info('Processing failed order', [
            'order_id' => $data['orderId'],
            'external_order_id' => $data['externalOrderId']
        ]);

        // Here you can add your business logic for failed orders
        // For example:
        // - Send failure notification to user
        // - Refund user if necessary
        // - Log the failure for investigation
        
        // Example: Send webhook to your application
        $this->sendWebhookNotification($data, 'failed');
    }

    private function handlePendingOrder(array $data): void
    {
        $this->logger->info('Processing pending order', [
            'order_id' => $data['orderId'],
            'external_order_id' => $data['externalOrderId']
        ]);

        // Here you can add your business logic for pending orders
        // For example:
        // - Send status update to user
        // - Update order tracking
        
        // Example: Send webhook to your application
        $this->sendWebhookNotification($data, 'pending');
    }

    private function sendWebhookNotification(array $data, string $status): void
    {
        // This is an example of how you might forward the webhook to your application
        // You can customize this based on your needs
        
        $webhookData = [
            'order_id' => $data['orderId'],
            'external_order_id' => $data['externalOrderId'],
            'status' => $status,
            'hambit_data' => $data,
            'timestamp' => date('Y-m-d H:i:s')
        ];

        // Example webhook URL - replace with your actual webhook endpoint
        $webhookUrl = $_ENV['WEBHOOK_URL'] ?? '';
        
        if (!empty($webhookUrl)) {
            try {
                $ch = curl_init();
                curl_setopt_array($ch, [
                    CURLOPT_URL => $webhookUrl,
                    CURLOPT_POST => true,
                    CURLOPT_POSTFIELDS => json_encode($webhookData),
                    CURLOPT_HTTPHEADER => [
                        'Content-Type: application/json',
                        'X-Webhook-Signature: ' . hash_hmac('sha256', json_encode($webhookData), $_ENV['WEBHOOK_SECRET'] ?? '')
                    ],
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_TIMEOUT => 10
                ]);

                $response = curl_exec($ch);
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                curl_close($ch);

                $this->logger->info('Webhook notification sent', [
                    'webhook_url' => $webhookUrl,
                    'http_code' => $httpCode,
                    'response' => $response
                ]);

            } catch (\Exception $e) {
                $this->logger->error('Failed to send webhook notification', [
                    'error' => $e->getMessage()
                ]);
            }
        }
    }
} 