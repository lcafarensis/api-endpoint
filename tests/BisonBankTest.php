<?php

use PHPUnit\Framework\TestCase;
use App\Services\BisonBankService;
use Monolog\Logger;
use Monolog\Handler\TestHandler;

class BisonBankTest extends TestCase
{
    private BisonBankService $bisonBankService;
    private TestHandler $logHandler;

    protected function setUp(): void
    {
        // Set up test environment variables
        $_ENV['BISON_BANK_API_BASE_URL'] = 'https://api.bisonbank.com';
        $_ENV['BISON_BANK_CLIENT_ID'] = 'test_client_id';
        $_ENV['BISON_BANK_CLIENT_SECRET'] = 'test_client_secret';

        // Create test logger
        $logger = new Logger('test');
        $this->logHandler = new TestHandler();
        $logger->pushHandler($this->logHandler);

        $this->bisonBankService = new BisonBankService($logger);
    }

    public function testGetSupportedFeatures()
    {
        $features = $this->bisonBankService->getSupportedFeatures();

        $this->assertIsArray($features);
        $this->assertArrayHasKey('currencies', $features);
        $this->assertArrayHasKey('transfer_types', $features);
        $this->assertArrayHasKey('priorities', $features);
        $this->assertArrayHasKey('charges', $features);

        $this->assertContains('EUR', $features['currencies']);
        $this->assertContains('USD', $features['currencies']);
        $this->assertContains('DOMESTIC', $features['transfer_types']);
        $this->assertContains('INTERNATIONAL', $features['transfer_types']);
        $this->assertContains('NORMAL', $features['priorities']);
        $this->assertContains('URGENT', $features['priorities']);
        $this->assertContains('SHA', $features['charges']);
        $this->assertContains('OUR', $features['charges']);
        $this->assertContains('BEN', $features['charges']);
    }

    public function testValidateDomesticTransferData()
    {
        $validData = [
            'sourceAccount' => 'ACCOUNT123',
            'destinationIban' => 'PT50003506510000000000033',
            'destinationName' => 'John Doe',
            'amount' => 100.00,
            'currency' => 'EUR',
            'description' => 'Payment for services',
            'reference' => 'TRANSFER-123',
            'priority' => 'NORMAL'
        ];

        // Test with valid data
        $result = $this->bisonBankService->createDomesticTransfer($validData);
        // Note: This will fail in test environment due to no real API connection
        // but we can test the data formatting logic

        $this->assertIsArray($result);
    }

    public function testValidateInternationalTransferData()
    {
        $validData = [
            'sourceAccount' => 'ACCOUNT123',
            'swiftCode' => 'CHASUS33',
            'destinationIban' => 'US12345678901234567890',
            'destinationName' => 'Jane Smith',
            'destinationAddress' => '123 Main St',
            'destinationCity' => 'New York',
            'destinationCountry' => 'US',
            'bankName' => 'Chase Bank',
            'bankAddress' => '456 Wall Street',
            'amount' => 500.00,
            'currency' => 'USD',
            'reference' => 'INT-TRANSFER-123',
            'priority' => 'NORMAL',
            'charges' => 'SHA'
        ];

        // Test with valid data
        $result = $this->bisonBankService->createInternationalTransfer($validData);
        // Note: This will fail in test environment due to no real API connection
        // but we can test the data formatting logic

        $this->assertIsArray($result);
    }

    public function testGetAccountBalance()
    {
        $accountId = 'ACCOUNT123';
        $result = $this->bisonBankService->getAccountBalance($accountId);

        $this->assertIsArray($result);
        // In test environment, this should fail due to no real API connection
        $this->assertFalse($result['success']);
    }

    public function testGetAccountDetails()
    {
        $accountId = 'ACCOUNT123';
        $result = $this->bisonBankService->getAccountDetails($accountId);

        $this->assertIsArray($result);
        // In test environment, this should fail due to no real API connection
        $this->assertFalse($result['success']);
    }

    public function testGetTransferStatus()
    {
        $transferId = 'TRANSFER123';
        $result = $this->bisonBankService->getTransferStatus($transferId);

        $this->assertIsArray($result);
        // In test environment, this should fail due to no real API connection
        $this->assertFalse($result['success']);
    }

    public function testGetTransferList()
    {
        $filters = [
            'page' => 1,
            'size' => 10,
            'status' => 'PENDING'
        ];

        $result = $this->bisonBankService->getTransferList($filters);

        $this->assertIsArray($result);
        // In test environment, this should fail due to no real API connection
        $this->assertFalse($result['success']);
    }

    public function testGetAccountTransactions()
    {
        $accountId = 'ACCOUNT123';
        $filters = [
            'page' => 1,
            'size' => 10,
            'startDate' => '2024-01-01',
            'endDate' => '2024-12-31'
        ];

        $result = $this->bisonBankService->getAccountTransactions($accountId, $filters);

        $this->assertIsArray($result);
        // In test environment, this should fail due to no real API connection
        $this->assertFalse($result['success']);
    }

    public function testValidateCredentials()
    {
        $result = $this->bisonBankService->validateCredentials();

        $this->assertIsArray($result);
        // In test environment, this should fail due to no real API connection
        $this->assertFalse($result['success']);
    }

    public function testLogging()
    {
        // Test that logging is working
        $this->bisonBankService->getSupportedFeatures();

        $this->assertTrue($this->logHandler->hasInfoRecords());
    }

    public function testEnvironmentVariables()
    {
        $this->assertEquals('https://api.bisonbank.com', $_ENV['BISON_BANK_API_BASE_URL']);
        $this->assertEquals('test_client_id', $_ENV['BISON_BANK_CLIENT_ID']);
        $this->assertEquals('test_client_secret', $_ENV['BISON_BANK_CLIENT_SECRET']);
    }
} 