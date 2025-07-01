<?php

namespace App\Routes;

use Slim\App;
use App\Config\Database;
use Ramsey\Uuid\Uuid;

class AuthRoutes
{
    public static function register(App $app): void
    {
        // Generate API key endpoint
        $app->post('/api/generate-key', function ($request, $response) {
            try {
                $data = $request->getParsedBody();
                
                if (empty($data['username']) || empty($data['institution_name'])) {
                    return $response
                        ->withStatus(400)
                        ->withHeader('Content-Type', 'application/json')
                        ->withBody($response->getBody()->write(json_encode([
                            'success' => false,
                            'message' => 'Username and institution name are required'
                        ])));
                }

                $db = Database::getInstance();
                
                // Check if username already exists
                $existingUser = $db->fetchAssociative(
                    "SELECT id FROM users WHERE username = ?",
                    [$data['username']]
                );
                
                if ($existingUser) {
                    return $response
                        ->withStatus(409)
                        ->withHeader('Content-Type', 'application/json')
                        ->withBody($response->getBody()->write(json_encode([
                            'success' => false,
                            'message' => 'Username already exists'
                        ])));
                }

                // Generate API key
                $apiKey = 'API_' . strtoupper(substr(Uuid::uuid4()->toString(), 0, 16));
                
                // Insert new user
                $db->executeStatement(
                    "INSERT INTO users (username, api_key, institution_name) VALUES (?, ?, ?)",
                    [$data['username'], $apiKey, $data['institution_name']]
                );

                return $response
                    ->withStatus(201)
                    ->withHeader('Content-Type', 'application/json')
                    ->withBody($response->getBody()->write(json_encode([
                        'success' => true,
                        'message' => 'API key generated successfully',
                        'data' => [
                            'username' => $data['username'],
                            'institution_name' => $data['institution_name'],
                            'api_key' => $apiKey
                        ]
                    ])));

            } catch (\Exception $e) {
                return $response
                    ->withStatus(500)
                    ->withHeader('Content-Type', 'application/json')
                    ->withBody($response->getBody()->write(json_encode([
                        'success' => false,
                        'message' => 'Internal server error',
                        'error' => $e->getMessage()
                    ])));
            }
        });

        // List API keys endpoint
        $app->get('/api/keys', function ($request, $response) {
            try {
                $db = Database::getInstance();
                $users = $db->fetchAllAssociative(
                    "SELECT username, institution_name, created_at FROM users ORDER BY created_at DESC"
                );

                return $response
                    ->withStatus(200)
                    ->withHeader('Content-Type', 'application/json')
                    ->withBody($response->getBody()->write(json_encode([
                        'success' => true,
                        'data' => $users
                    ])));

            } catch (\Exception $e) {
                return $response
                    ->withStatus(500)
                    ->withHeader('Content-Type', 'application/json')
                    ->withBody($response->getBody()->write(json_encode([
                        'success' => false,
                        'message' => 'Internal server error'
                    ])));
            }
        });
    }
} 