<?php

require_once __DIR__ . '/vendor/autoload.php';

use App\Config\Database;
use Dotenv\Dotenv;

echo "🚀 Fund Transfer API Setup\n";
echo "==========================\n\n";

// Load environment variables
if (file_exists(__DIR__ . '/.env')) {
    $dotenv = Dotenv::createImmutable(__DIR__);
    $dotenv->load();
    echo "✅ Environment file loaded\n";
} else {
    echo "⚠️  No .env file found. Please copy env.example to .env and configure it.\n";
    echo "   cp env.example .env\n\n";
}

// Create logs directory
if (!is_dir(__DIR__ . '/logs')) {
    mkdir(__DIR__ . '/logs', 0755, true);
    echo "✅ Logs directory created\n";
} else {
    echo "✅ Logs directory exists\n";
}

// Create database tables
try {
    Database::createTables();
    echo "✅ Database tables created successfully\n";
} catch (Exception $e) {
    echo "❌ Failed to create database tables: " . $e->getMessage() . "\n";
    echo "   Please check your database configuration in .env file\n";
    exit(1);
}

// Test database connection
try {
    $db = Database::getInstance();
    echo "✅ Database connection successful\n";
} catch (Exception $e) {
    echo "❌ Database connection failed: " . $e->getMessage() . "\n";
    exit(1);
}

echo "\n🎉 Setup completed successfully!\n";
echo "\nNext steps:\n";
echo "1. Start the server: composer start\n";
echo "2. Generate an API key: POST /api/generate-key\n";
echo "3. Test the API: GET /health\n";
echo "\nFor more information, see README.md\n"; 