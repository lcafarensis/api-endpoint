# Fund Transfer API

A PHP-based API for handling fund transfers between banking institutions using Composer and Slim Framework.

## Features

- **Secure API Authentication** using API keys
- **External Bank Integration** with BRI API
- **Crypto-Fiat Exchange Integration** with [Hambit Ramp API](https://docs.hambit.co/ramp/api-integration-1.html#inr)
- **Banking Integration** with [Bison Bank API](https://portal.bisonbank.com/)
- **Transfer Status Tracking** with real-time updates
- **Payout Management** for crypto and cash transfers
- **Comprehensive Logging** for audit trails
- **Database Integration** with MySQL/PostgreSQL support
- **Webhook Support** for real-time notifications

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

### Hambit Crypto-Fiat Exchange

#### Create Fiat-to-Crypto Order
```http
POST /api/hambit/fiat-to-crypto
x-api-key: YOUR_API_KEY
Content-Type: application/json

{
    "externalOrderId": "20250513184347329830",
    "chainType": "BSC",
    "tokenType": "USDT",
    "addressTo": "0xa8666442fA7583F783a169CC9F3333333360295E8",
    "tokenAmount": "1",
    "currencyType": "INR",
    "payType": "BANK",
    "remark": "test",
    "notifyUrl": "https://your-callback-url.com/webhook"
}
```

#### Create Crypto-to-Fiat Order
```http
POST /api/hambit/crypto-to-fiat
x-api-key: YOUR_API_KEY
Content-Type: application/json

{
    "externalOrderId": "20250513192309317184",
    "chainType": "BSC",
    "tokenType": "USDT",
    "addressFrom": "0xa8666442fA7583F783a169CC9F5449333333395E8",
    "tokenAmount": "10.214234221423422312",
    "currencyType": "INR",
    "payType": "BANK",
    "remark": "test",
    "notifyUrl": "https://your-callback-url.com/webhook"
}
```

#### Get Order Details
```http
GET /api/hambit/order/{order_id}
x-api-key: YOUR_API_KEY
```

#### Get Order List
```http
GET /api/hambit/orders?page=1&size=10&status=completed
x-api-key: YOUR_API_KEY
```

#### Get Quotes
```http
POST /api/hambit/quotes
x-api-key: YOUR_API_KEY
Content-Type: application/json

{
    "chainType": "BSC",
    "tokenType": "USDT",
    "currencyType": "INR",
    "tokenAmount": "1"
}
```

#### Get Supported Currencies
```http
GET /api/hambit/currencies
x-api-key: YOUR_API_KEY
```

#### Validate Hambit Credentials
```http
GET /api/hambit/validate-credentials
x-api-key: YOUR_API_KEY
```

#### Hambit Callback Webhook
```http
POST /api/hambit/callback
Content-Type: application/json

{
    "orderId": "OEXCHEXCH202505131043481747133028307HAMBIT-U0000000401298824",
    "externalOrderId": "20250513184347329830",
    "status": "completed",
    "data": {...}
}
```

### Bison Bank Integration

#### Get Supported Features
```http
GET /api/bison-bank/features
x-api-key: YOUR_API_KEY
```

#### Get Account Balance
```http
GET /api/bison-bank/accounts/{account_id}/balance
x-api-key: YOUR_API_KEY
```

#### Get Account Details
```http
GET /api/bison-bank/accounts/{account_id}/details
x-api-key: YOUR_API_KEY
```

#### Get Account Transactions
```http
GET /api/bison-bank/accounts/{account_id}/transactions?page=1&size=10&startDate=2024-01-01&endDate=2024-12-31
x-api-key: YOUR_API_KEY
```

#### Create Domestic Transfer
```http
POST /api/bison-bank/transfers/domestic
x-api-key: YOUR_API_KEY
Content-Type: application/json

{
    "sourceAccount": "ACCOUNT123",
    "destinationIban": "PT50003506510000000000033",
    "destinationName": "John Doe",
    "amount": 100.00,
    "currency": "EUR",
    "description": "Payment for services",
    "reference": "TRANSFER-123",
    "priority": "NORMAL"
}
```

#### Create International Transfer (SWIFT)
```http
POST /api/bison-bank/transfers/international
x-api-key: YOUR_API_KEY
Content-Type: application/json

{
    "sourceAccount": "ACCOUNT123",
    "swiftCode": "CHASUS33",
    "destinationIban": "US12345678901234567890",
    "destinationName": "Jane Smith",
    "destinationAddress": "123 Main St",
    "destinationCity": "New York",
    "destinationCountry": "US",
    "bankName": "Chase Bank",
    "bankAddress": "456 Wall Street",
    "amount": 500.00,
    "currency": "USD",
    "reference": "INT-TRANSFER-123",
    "priority": "NORMAL",
    "charges": "SHA"
}
```

#### Get Transfer Status
```http
GET /api/bison-bank/transfers/{transfer_id}
x-api-key: YOUR_API_KEY
```

#### Get Transfer List
```http
GET /api/bison-bank/transfers?page=1&size=10&status=PENDING&startDate=2024-01-01&endDate=2024-12-31
x-api-key: YOUR_API_KEY
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

# Create fiat-to-crypto order (INR to USDT)
curl -X POST http://localhost:8000/api/hambit/fiat-to-crypto \
  -H "x-api-key: API_1234567890ABCDEF" \
  -H "Content-Type: application/json" \
  -d '{
    "externalOrderId": "TEST_1234567890",
    "chainType": "BSC",
    "tokenType": "USDT",
    "addressTo": "0xa8666442fA7583F783a169CC9F3333333360295E8",
    "tokenAmount": "1",
    "currencyType": "INR",
    "payType": "BANK",
    "remark": "Test order",
    "notifyUrl": "https://your-callback-url.com/webhook"
  }'

# Get quotes for VND to USDT
curl -X POST http://localhost:8000/api/hambit/quotes \
  -H "x-api-key: API_1234567890ABCDEF" \
  -H "Content-Type: application/json" \
  -d '{
    "chainType": "BSC",
    "tokenType": "USDT",
    "currencyType": "VND",
    "tokenAmount": "1"
  }'

# Get Bison Bank account balance
curl -X GET http://localhost:8000/api/bison-bank/accounts/ACCOUNT123/balance \
  -H "x-api-key: API_1234567890ABCDEF"

# Create domestic transfer via Bison Bank
curl -X POST http://localhost:8000/api/bison-bank/transfers/domestic \
  -H "x-api-key: API_1234567890ABCDEF" \
  -H "Content-Type: application/json" \
  -d '{
    "sourceAccount": "ACCOUNT123",
    "destinationIban": "PT50003506510000000000033",
    "destinationName": "John Doe",
    "amount": 100.00,
    "currency": "EUR",
    "description": "Payment for services",
    "reference": "TRANSFER-123",
    "priority": "NORMAL"
  }'

# Create international transfer via Bison Bank
curl -X POST http://localhost:8000/api/bison-bank/transfers/international \
  -H "x-api-key: API_1234567890ABCDEF" \
  -H "Content-Type: application/json" \
  -d '{
    "sourceAccount": "ACCOUNT123",
    "swiftCode": "CHASUS33",
    "destinationIban": "US12345678901234567890",
    "destinationName": "Jane Smith",
    "destinationAddress": "123 Main St",
    "destinationCity": "New York",
    "destinationCountry": "US",
    "bankName": "Chase Bank",
    "bankAddress": "456 Wall Street",
    "amount": 500.00,
    "currency": "USD",
    "reference": "INT-TRANSFER-123",
    "priority": "NORMAL",
    "charges": "SHA"
  }'

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

### Hambit Orders Table
- `id` - Primary key
- `order_id` - Hambit order ID
- `external_order_id` - External order ID
- `chain_type` - Blockchain type (BSC)
- `token_type` - Token type (USDT)
- `currency_type` - Fiat currency type (INR, BRL, MXN, VND)
- `pay_type` - Payment type (BANK, PIX, etc.)
- `token_amount` - Token amount
- `currency_amount` - Fiat currency amount
- `exchange_price` - Exchange rate
- `order_fee` - Order fee
- `status` - Order status (pending/processing/completed/failed/cancelled)
- `address_to` - Recipient wallet address
- `address_from` - Sender wallet address
- `remark` - Order remarks
- `cashier_url` - Cashier URL
- `callback_data` - Callback data from Hambit
- `created_at` - Creation timestamp
- `updated_at` - Last update timestamp

## Payout Timeframes

- **Crypto Wallets**: 4-24 hours
- **Cash Transfers**: 72 hours

## Supported Currencies (Hambit)

### INR (India)
- **Payment Methods**: BANK
- **Blockchain**: BSC
- **Token**: USDT

### BRL (Brazil)
- **Payment Methods**: PIX
- **Blockchain**: BSC
- **Token**: USDT

### MXN (Mexico)
- **Payment Methods**: CASH, PAYCASHRECURRENT, QRIS
- **Blockchain**: BSC
- **Token**: USDT

### VND (Vietnam)
- **Payment Methods**: BANK, BANK_SCAN_CODE, CARD_TO_CARD, MOMO, ZALO_PAY, VIETTEL_MONEY
- **Blockchain**: BSC
- **Token**: USDT

## Supported Features (Bison Bank)

### Currencies
- **EUR** - Euro
- **USD** - US Dollar
- **GBP** - British Pound
- **CHF** - Swiss Franc
- **JPY** - Japanese Yen
- **CAD** - Canadian Dollar
- **AUD** - Australian Dollar
- **NZD** - New Zealand Dollar

### Transfer Types
- **DOMESTIC** - Domestic transfers within the same country
- **INTERNATIONAL** - International transfers (SWIFT)

### Priorities
- **NORMAL** - Standard processing time
- **URGENT** - Expedited processing

### Charges
- **SHA** - Shared charges (default)
- **OUR** - All charges paid by sender
- **BEN** - All charges paid by beneficiary

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
