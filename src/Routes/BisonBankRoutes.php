<?php

namespace App\Routes;

use App\Controllers\BisonBankController;
use Slim\App;
use App\Middleware\AuthenticationMiddleware;

class BisonBankRoutes
{
    public static function register(App $app): void
    {
        $app->group('/api/bison-bank', function ($group) {
            // Get supported features
            $group->get('/features', [BisonBankController::class, 'getSupportedFeatures']);

            // Account operations
            $group->get('/accounts/{account_id}/balance', [BisonBankController::class, 'getAccountBalance']);
            $group->get('/accounts/{account_id}/details', [BisonBankController::class, 'getAccountDetails']);
            $group->get('/accounts/{account_id}/transactions', [BisonBankController::class, 'getAccountTransactions']);

            // Transfer operations
            $group->post('/transfers/domestic', [BisonBankController::class, 'createDomesticTransfer']);
            $group->post('/transfers/international', [BisonBankController::class, 'createInternationalTransfer']);
            $group->get('/transfers/{transfer_id}', [BisonBankController::class, 'getTransferStatus']);
            $group->get('/transfers', [BisonBankController::class, 'getTransferList']);

        })->add(AuthenticationMiddleware::class);
    }
} 