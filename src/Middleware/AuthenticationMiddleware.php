<?php

namespace App\Middleware;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use App\Config\Database;

class AuthenticationMiddleware implements MiddlewareInterface
{
    public function process(Request $request, RequestHandlerInterface $handler): Response
    {
        // Skip authentication for health check and public endpoints
        $path = $request->getUri()->getPath();
        if (in_array($path, ['/health', '/api/validate-credentials'])) {
            return $handler->handle($request);
        }

        $apiKey = $request->getHeaderLine('x-api-key');
        
        if (empty($apiKey)) {
            return $this->createErrorResponse(401, 'API key is required');
        }

        // Validate API key against database
        if (!$this->validateApiKey($apiKey)) {
            return $this->createErrorResponse(401, 'Invalid API key');
        }

        return $handler->handle($request);
    }

    private function validateApiKey(string $apiKey): bool
    {
        try {
            $db = Database::getInstance();
            $sql = "SELECT id FROM users WHERE api_key = ?";
            $result = $db->fetchAssociative($sql, [$apiKey]);
            
            return $result !== false;
        } catch (\Exception $e) {
            // Log error but don't expose it to client
            error_log("API key validation error: " . $e->getMessage());
            return false;
        }
    }

    private function createErrorResponse(int $statusCode, string $message): Response
    {
        $response = new \Slim\Psr7\Response();
        $response->getBody()->write(json_encode([
            'success' => false,
            'error' => $message
        ]));
        
        return $response
            ->withStatus($statusCode)
            ->withHeader('Content-Type', 'application/json');
    }
} 