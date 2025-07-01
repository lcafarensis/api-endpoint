<?php

namespace App\Routes;

use Slim\App;
use App\Controllers\HambitController;
use App\Controllers\HambitCallbackController;
use App\Services\HambitService;

class HambitRoutes
{
    public static function register(App $app): void
    {
        $container = $app->getContainer();
        
        // Create dependencies
        $hambitService = new HambitService($container->get('logger'));
        $hambitController = new HambitController($hambitService, $container->get('logger'));
        $hambitCallbackController = new HambitCallbackController($container->get('logger'));

        // Hambit routes
        $app->post('/api/hambit/fiat-to-crypto', [$hambitController, 'createFiatToCryptoOrder']);
        $app->post('/api/hambit/crypto-to-fiat', [$hambitController, 'createCryptoToFiatOrder']);
        $app->get('/api/hambit/order/{order_id}', [$hambitController, 'getOrderDetails']);
        $app->get('/api/hambit/orders', [$hambitController, 'getOrderList']);
        $app->post('/api/hambit/quotes', [$hambitController, 'getQuotes']);
        $app->get('/api/hambit/currencies', [$hambitController, 'getSupportedCurrencies']);
        
        // Hambit callback route (no authentication required)
        $app->post('/api/hambit/callback', [$hambitCallbackController, 'handleCallback']);
        
        // Hambit API validation
        $app->get('/api/hambit/validate-credentials', function ($request, $response) use ($hambitService) {
            $result = $hambitService->validateCredentials();
            
            return $response
                ->withStatus($result['success'] ? 200 : 400)
                ->withHeader('Content-Type', 'application/json')
                ->withBody($response->getBody()->write(json_encode($result)));
        });
    }
} 