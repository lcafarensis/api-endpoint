<?php

use Slim\Factory\AppFactory;
use DI\Container;
use App\Middleware\CorsMiddleware;
use App\Middleware\AuthenticationMiddleware;
use App\Routes\TransferRoutes;
use App\Routes\AuthRoutes;
use App\Routes\HambitRoutes;
use App\Routes\BisonBankRoutes;
use App\Config\Database;
use Dotenv\Dotenv;

require_once __DIR__ . '/../vendor/autoload.php';

// Load environment variables
$dotenv = Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

// Create Container
$container = new Container();

// Configure container
$container->set('db', function () {
    return Database::getInstance();
});

$container->set('logger', function () {
    $logger = new \Monolog\Logger('api');
    $logger->pushHandler(new \Monolog\Handler\StreamHandler(__DIR__ . '/../logs/app.log', \Monolog\Logger::DEBUG));
    return $logger;
});

// Create App
$app = AppFactory::createFromContainer($container);

// Add middleware
$app->add(new CorsMiddleware());
$app->add(new AuthenticationMiddleware());

// Add error middleware
$app->addErrorMiddleware(true, true, true);

// Register routes
TransferRoutes::register($app);
AuthRoutes::register($app);
HambitRoutes::register($app);
BisonBankRoutes::register($app);

// Health check endpoint
$app->get('/health', function ($request, $response) {
    $response->getBody()->write(json_encode(['status' => 'healthy', 'timestamp' => date('Y-m-d H:i:s')]));
    return $response->withHeader('Content-Type', 'application/json');
});

$app->run(); 