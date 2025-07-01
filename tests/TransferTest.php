<?php

namespace Tests;

use PHPUnit\Framework\TestCase;
use App\Models\Transfer;
use App\Services\ExternalBankService;
use Monolog\Logger;
use Monolog\Handler\NullHandler;

class TransferTest extends TestCase
{
    private Transfer $transferModel;

    protected function setUp(): void
    {
        $this->transferModel = new Transfer();
    }

    public function testValidateTransferDataWithValidData(): void
    {
        $validData = [
            'sending_name' => 'DEUTSCHE BANK AG',
            'sending_account' => '1234567890',
            'receiving_name' => 'PT JUMINDO INDAH PERKASA',
            'receiving_account' => '007501002373309',
            'amount' => 500000000,
            'sending_currency' => 'EUR',
            'receiving_currency' => 'EUR'
        ];

        $errors = $this->transferModel->validateTransferData($validData);
        $this->assertEmpty($errors);
    }

    public function testValidateTransferDataWithMissingFields(): void
    {
        $invalidData = [
            'sending_name' => 'DEUTSCHE BANK AG',
            'amount' => 500000000
        ];

        $errors = $this->transferModel->validateTransferData($invalidData);
        $this->assertNotEmpty($errors);
        $this->assertContains('Sending account is required', $errors);
        $this->assertContains('Receiving name is required', $errors);
        $this->assertContains('Receiving account is required', $errors);
        $this->assertContains('Sending currency is required', $errors);
        $this->assertContains('Receiving currency is required', $errors);
    }

    public function testValidateTransferDataWithInvalidAmount(): void
    {
        $invalidData = [
            'sending_name' => 'DEUTSCHE BANK AG',
            'sending_account' => '1234567890',
            'receiving_name' => 'PT JUMINDO INDAH PERKASA',
            'receiving_account' => '007501002373309',
            'amount' => -100,
            'sending_currency' => 'EUR',
            'receiving_currency' => 'EUR'
        ];

        $errors = $this->transferModel->validateTransferData($invalidData);
        $this->assertContains('Valid amount is required', $errors);
    }

    public function testExternalBankServiceFormatTransferPayload(): void
    {
        $logger = new Logger('test');
        $logger->pushHandler(new NullHandler());
        
        $service = new ExternalBankService($logger);
        
        $transferData = [
            'transfer_request_id' => 'test-uuid-123',
            'sending_name' => 'DEUTSCHE BANK AG',
            'sending_account' => '1234567890',
            'receiving_name' => 'PT JUMINDO INDAH PERKASA',
            'receiving_account' => '007501002373309',
            'amount' => 500000000,
            'sending_currency' => 'EUR',
            'receiving_currency' => 'EUR',
            'description' => 'Test transaction'
        ];

        // Use reflection to test private method
        $reflection = new \ReflectionClass($service);
        $method = $reflection->getMethod('formatTransferPayload');
        $method->setAccessible(true);
        
        $result = $method->invoke($service, $transferData);
        
        $this->assertArrayHasKey('CashTransfer.v1', $result);
        $this->assertEquals('DEUTSCHE BANK AG', $result['CashTransfer.v1']['Sending Name']);
        $this->assertEquals('PT JUMINDO INDAH PERKASA', $result['CashTransfer.v1']['Receiving Name']);
        $this->assertEquals('500.000.000', $result['CashTransfer.v1']['Amount']);
        $this->assertEquals('EUR', $result['CashTransfer.v1']['Sending Currency']);
        $this->assertEquals('EUR', $result['CashTransfer.v1']['Receiving Currency']);
    }
} 