<?php

namespace App\Routes;

use Slim\App;
use App\Controllers\TransferController;
use App\Models\Transfer;
use App\Services\ExternalBankService;

class TransferRoutes
{
    public static function register(App $app): void
    {
        $container = $app->getContainer();
        
        // Create dependencies
        $transferModel = new Transfer();
        $externalBankService = new ExternalBankService($container->get('logger'));
        $transferController = new TransferController($transferModel, $externalBankService, $container->get('logger'));

        // Transfer routes
        $app->post('/api/transfer/initiate', [$transferController, 'initiateTransfer']);
        $app->get('/api/transfer/{transfer_request_id}/status', [$transferController, 'getTransferStatus']);
        $app->get('/api/transfers', [$transferController, 'getAllTransfers']);
        $app->post('/api/transfer/{transfer_request_id}/confirm-credit', [$transferController, 'confirmFundCredit']);
        
        // External API validation
        $app->get('/api/validate-credentials', function ($request, $response) use ($externalBankService) {
            $result = $externalBankService->validateApiCredentials();
            
            return $response
                ->withStatus($result['success'] ? 200 : 400)
                ->withHeader('Content-Type', 'application/json')
                ->withBody($response->getBody()->write(json_encode($result)));
        });
    }
} 