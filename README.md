# Fund Transfer API

A PHP-based API for handling fund transfers between banking institutions using Composer and Slim Framework.

## Features

- **Secure API Authentication** using API keys
- **External Bank Integration** with BRI API
- **Transfer Status Tracking** with real-time updates
- **Payout Management** for crypto and cash transfers
- **Comprehensive Logging** for audit trails
- **Database Integration** with MySQL/PostgreSQL support

## Requirements

- PHP 8.1 or higher
- Composer
- MySQL 5.7+ or PostgreSQL 10+
- Web server (Apache/Nginx) or PHP built-in server

## Installation

1. **Clone the repository**
   ```bash
   git clone https://github.com/lcafarensis/api-endpoint
   cd API_connection
   ```

2. **Install dependencies**
   ```bash
   composer install
   ```

3. **Configure environment**
   ```bash
   cp env.example .env
   # Edit .env with your database and API credentials
   ```

4. **Create database tables**
   ```bash
   php -r "require 'vendor/autoload.php'; \App\Config\Database::createTables();"
   ```

5. **Create logs directory**
   ```bash
   mkdir logs
   chmod 755 logs
   ```

6. **Start the server**
   ```bash
   composer start
   # Or manually: php -S localhost:8000 -t public
   ```

## API Endpoints

### Authentication

#### Generate API Key
```http
POST /api/generate-key
Content-Type: application/json

{
    "username": "deutsche_bank",
    "institution_name": "DEUTSCHE BANK AG"
}
```

#### List API Keys
```http
GET /api/keys
```

### Transfer Operations

#### Initiate Transfer
```http
POST /api/transfer/initiate
x-api-key: YOUR_API_KEY
Content-Type: application/json

{
    "sending_name": "DEUTSCHE BANK AG",
    "sending_account": "1234567890",
    "receiving_name": "PT JUMINDO INDAH PERKASA",
    "receiving_account": "007501002373309",
    "amount": 500000000,
    "sending_currency": "EUR",
    "receiving_currency": "EUR",
    "description": "Your transaction is successful"
}
```

#### Get Transfer Status
```http
GET /api/transfer/{transfer_request_id}/status
x-api-key: YOUR_API_KEY
```

#### Get All Transfers
```http
GET /api/transfers
x-api-key: YOUR_API_KEY
```

#### Confirm Fund Credit
```http
POST /api/transfer/{transfer_request_id}/confirm-credit
x-api-key: YOUR_API_KEY
Content-Type: application/json

{
    "sender_account": "1234567890",
    "paymaster_account": "0987654321",
    "payout_type": "crypto"
}
```

### System

#### Health Check
```http
GET /health
```

#### Validate External API Credentials
```http
GET /api/validate-credentials
```

## Example Usage

### Using cURL

```bash
# Generate API key
curl -X POST http://localhost:8000/api/generate-key \
  -H "Content-Type: application/json" \
  -d '{
    "username": "deutsche_bank",
    "institution_name": "DEUTSCHE BANK AG"
  }'

# Initiate transfer (using the API key from above)
curl -X POST http://localhost:8000/api/transfer/initiate \
  -H "x-api-key: API_1234567890ABCDEF" \
  -H "Content-Type: application/json" \
  -d '{
    "sending_name": "DEUTSCHE BANK AG",
    "sending_account": "1234567890",
    "receiving_name": "PT JUMINDO INDAH PERKASA",
    "receiving_account": "007501002373309",
    "amount": 500000000,
    "sending_currency": "EUR",
    "receiving_currency": "EUR",
    "description": "Your transaction is successful"
  }'
```

### Using PHP

```php
<?php

$apiKey = 'YOUR_API_KEY';
$baseUrl = 'http://localhost:8000';

// Initiate transfer
$transferData = [
    'sending_name' => 'DEUTSCHE BANK AG',
    'sending_account' => '1234567890',
    'receiving_name' => 'PT JUMINDO INDAH PERKASA',
    'receiving_account' => '007501002373309',
    'amount' => 500000000,
    'sending_currency' => 'EUR',
    'receiving_currency' => 'EUR',
    'description' => 'Your transaction is successful'
];

$ch = curl_init($baseUrl . '/api/transfer/initiate');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($transferData));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'x-api-key: ' . $apiKey
]);

$response = curl_exec($ch);
$result = json_decode($response, true);

if ($result['success']) {
    echo "Transfer initiated: " . $result['data']['transfer_request_id'];
} else {
    echo "Error: " . $result['message'];
}
```

## Database Schema

### Users Table
- `id` - Primary key
- `username` - Unique username
- `api_key` - Unique API key for authentication
- `institution_name` - Bank/institution name
- `created_at` - Creation timestamp
- `updated_at` - Last update timestamp

### Transfers Table
- `id` - Primary key
- `transfer_request_id` - Unique transfer identifier
- `sending_name` - Sender name
- `sending_account` - Sender account number
- `receiving_name` - Receiver name
- `receiving_account` - Receiver account number
- `amount` - Transfer amount
- `sending_currency` - Sender currency
- `receiving_currency` - Receiver currency
- `description` - Transfer description
- `status` - Transfer status (pending/processing/completed/failed)
- `external_api_response` - External API response data
- `created_at` - Creation timestamp
- `updated_at` - Last update timestamp

### Payout Accounts Table
- `id` - Primary key
- `transfer_id` - Foreign key to transfers table
- `sender_account` - Sender account for payout
- `paymaster_account` - Paymaster account
- `payout_type` - Payout type (crypto/cash)
- `status` - Payout status
- `created_at` - Creation timestamp
- `updated_at` - Last update timestamp

## Payout Timeframes

- **Crypto Wallets**: 4-24 hours
- **Cash Transfers**: 72 hours

## Error Handling

The API returns consistent error responses:

```json
{
    "success": false,
    "message": "Error description",
    "errors": ["Detailed error messages"]
}
```

## Logging

All API operations are logged to `logs/app.log` with detailed information including:
- Request/response data
- External API calls
- Error details
- Performance metrics

## Security

- API key authentication for all protected endpoints
- Input validation and sanitization
- SQL injection prevention using prepared statements
- CORS support for cross-origin requests
- Comprehensive error logging

## Testing

```bash
# Run tests
composer test

# Run static analysis
composer analyze
```

## Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Add tests
5. Submit a pull request

## License

This project is licensed under the MIT License. 
