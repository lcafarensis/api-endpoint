<?php

namespace App\Config;

use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Connection;

class Database
{
    private static ?Connection $instance = null;

    public static function getInstance(): Connection
    {
        if (self::$instance === null) {
            $connectionParams = [
                'dbname' => $_ENV['DB_NAME'] ?? 'fund_transfer',
                'user' => $_ENV['DB_USER'] ?? 'root',
                'password' => $_ENV['DB_PASSWORD'] ?? '',
                'host' => $_ENV['DB_HOST'] ?? 'localhost',
                'driver' => $_ENV['DB_DRIVER'] ?? 'pdo_mysql',
                'charset' => 'utf8mb4'
            ];

            self::$instance = DriverManager::getConnection($connectionParams);
        }

        return self::$instance;
    }

    public static function createTables(): void
    {
        $connection = self::getInstance();
        $schema = $connection->createSchemaManager();

        // Create users table
        $connection->executeStatement("
            CREATE TABLE IF NOT EXISTS users (
                id INT AUTO_INCREMENT PRIMARY KEY,
                username VARCHAR(255) UNIQUE NOT NULL,
                api_key VARCHAR(255) UNIQUE NOT NULL,
                institution_name VARCHAR(255) NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            )
        ");

        // Create transfers table
        $connection->executeStatement("
            CREATE TABLE IF NOT EXISTS transfers (
                id INT AUTO_INCREMENT PRIMARY KEY,
                transfer_request_id VARCHAR(255) UNIQUE NOT NULL,
                sending_name VARCHAR(255) NOT NULL,
                sending_account VARCHAR(255) NOT NULL,
                receiving_name VARCHAR(255) NOT NULL,
                receiving_account VARCHAR(255) NOT NULL,
                amount DECIMAL(15,2) NOT NULL,
                sending_currency VARCHAR(3) NOT NULL,
                receiving_currency VARCHAR(3) NOT NULL,
                description TEXT,
                status ENUM('pending', 'processing', 'completed', 'failed') DEFAULT 'pending',
                external_api_response JSON,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            )
        ");

        // Create payout_accounts table
        $connection->executeStatement("
            CREATE TABLE IF NOT EXISTS payout_accounts (
                id INT AUTO_INCREMENT PRIMARY KEY,
                transfer_id INT NOT NULL,
                sender_account VARCHAR(255) NOT NULL,
                paymaster_account VARCHAR(255) NOT NULL,
                payout_type ENUM('crypto', 'cash') NOT NULL,
                status ENUM('pending', 'processing', 'completed', 'failed') DEFAULT 'pending',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (transfer_id) REFERENCES transfers(id)
            )
        ");
    }
} 