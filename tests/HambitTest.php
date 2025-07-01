<?php

namespace Tests;

use PHPUnit\Framework\TestCase;
use App\Services\HambitService;
use Monolog\Logger;
use Monolog\Handler\NullHandler;

class HambitTest extends TestCase
{
    private HambitService $hambitService;

    protected function setUp(): void
    {
        $logger = new Logger('test');
        $logger->pushHandler(new NullHandler());
        $this->hambitService = new HambitService($logger);
    }

    public function testFormatFiatToCryptoPayload(): void
    {
        $orderData = [
            'externalOrderId' => 'TEST_1234567890',
            'chainType' => 'BSC',
            'tokenType' => 'USDT',
            'addressTo' => '0xa8666442fA7583F783a169CC9F3333333360295E8',
            'tokenAmount' => '1',
            'currencyType' => 'INR',
            'payType' => 'BANK',
            'remark' => 'Test order',
            'notifyUrl' => 'https://test.com/webhook'
        ];

        // Use reflection to test private method
        $reflection = new \ReflectionClass($this->hambitService);
        $method = $reflection->getMethod('formatFiatToCryptoPayload');
        $method->setAccessible(true);
        
        $result = $method->invoke($this->hambitService, $orderData);
        
        $this->assertArrayHasKey('CashTransfer.v1', $result);
        $this->assertEquals('TEST_1234567890', $result['CashTransfer.v1']['externalOrderId']);
        $this->assertEquals('BSC', $result['CashTransfer.v1']['chainType']);
        $this->assertEquals('USDT', $result['CashTransfer.v1']['tokenType']);
        $this->assertEquals('INR', $result['CashTransfer.v1']['currencyType']);
        $this->assertEquals('BANK', $result['CashTransfer.v1']['payType']);
    }

    public function testFormatCryptoToFiatPayload(): void
    {
        $orderData = [
            'externalOrderId' => 'TEST_1234567890',
            'chainType' => 'BSC',
            'tokenType' => 'USDT',
            'addressFrom' => '0xa8666442fA7583F783a169CC9F3333333360295E8',
            'tokenAmount' => '1',
            'currencyType' => 'INR',
            'payType' => 'BANK',
            'remark' => 'Test order',
            'notifyUrl' => 'https://test.com/webhook'
        ];

        // Use reflection to test private method
        $reflection = new \ReflectionClass($this->hambitService);
        $method = $reflection->getMethod('formatCryptoToFiatPayload');
        $method->setAccessible(true);
        
        $result = $method->invoke($this->hambitService, $orderData);
        
        $this->assertArrayHasKey('externalOrderId', $result);
        $this->assertEquals('TEST_1234567890', $result['externalOrderId']);
        $this->assertEquals('BSC', $result['chainType']);
        $this->assertEquals('USDT', $result['tokenType']);
        $this->assertEquals('INR', $result['currencyType']);
        $this->assertEquals('BANK', $result['payType']);
    }

    public function testGenerateAuthHeaders(): void
    {
        $payload = ['test' => 'data'];

        // Use reflection to test private method
        $reflection = new \ReflectionClass($this->hambitService);
        $method = $reflection->getMethod('generateAuthHeaders');
        $method->setAccessible(true);
        
        $result = $method->invoke($this->hambitService, $payload);
        
        $this->assertArrayHasKey('Content-Type', $result);
        $this->assertArrayHasKey('access_key', $result);
        $this->assertArrayHasKey('timestamp', $result);
        $this->assertArrayHasKey('nonce', $result);
        $this->assertArrayHasKey('sign', $result);
        $this->assertEquals('application/json; charset=utf-8', $result['Content-Type']);
    }

    public function testGetSupportedCurrencies(): void
    {
        $currencies = $this->hambitService->getSupportedCurrencies();
        
        $this->assertArrayHasKey('INR', $currencies);
        $this->assertArrayHasKey('BRL', $currencies);
        $this->assertArrayHasKey('MXN', $currencies);
        $this->assertArrayHasKey('VND', $currencies);
        
        // Check INR configuration
        $this->assertArrayHasKey('payType', $currencies['INR']);
        $this->assertArrayHasKey('chainType', $currencies['INR']);
        $this->assertArrayHasKey('tokenType', $currencies['INR']);
        $this->assertContains('BANK', $currencies['INR']['payType']);
        $this->assertContains('BSC', $currencies['INR']['chainType']);
        $this->assertContains('USDT', $currencies['INR']['tokenType']);
        
        // Check VND configuration
        $this->assertContains('BANK', $currencies['VND']['payType']);
        $this->assertContains('MOMO', $currencies['VND']['payType']);
        $this->assertContains('ZALO_PAY', $currencies['VND']['payType']);
    }

    public function testValidateFiatToCryptoData(): void
    {
        $validData = [
            'chainType' => 'BSC',
            'tokenType' => 'USDT',
            'currencyType' => 'INR',
            'payType' => 'BANK',
            'addressTo' => '0xa8666442fA7583F783a169CC9F3333333360295E8',
            'tokenAmount' => '1'
        ];

        $invalidData = [
            'chainType' => 'BSC',
            'tokenType' => 'USDT'
            // Missing required fields
        ];

        // Test with valid data
        $this->assertTrue($this->validateFiatToCryptoData($validData));
        
        // Test with invalid data
        $this->assertFalse($this->validateFiatToCryptoData($invalidData));
    }

    public function testValidateCryptoToFiatData(): void
    {
        $validData = [
            'chainType' => 'BSC',
            'tokenType' => 'USDT',
            'currencyType' => 'INR',
            'payType' => 'BANK',
            'addressFrom' => '0xa8666442fA7583F783a169CC9F3333333360295E8',
            'tokenAmount' => '1'
        ];

        $invalidData = [
            'chainType' => 'BSC',
            'tokenType' => 'USDT'
            // Missing required fields
        ];

        // Test with valid data
        $this->assertTrue($this->validateCryptoToFiatData($validData));
        
        // Test with invalid data
        $this->assertFalse($this->validateCryptoToFiatData($invalidData));
    }

    private function validateFiatToCryptoData(array $data): bool
    {
        $requiredFields = ['chainType', 'tokenType', 'currencyType', 'payType', 'addressTo', 'tokenAmount'];
        
        foreach ($requiredFields as $field) {
            if (empty($data[$field])) {
                return false;
            }
        }
        
        return true;
    }

    private function validateCryptoToFiatData(array $data): bool
    {
        $requiredFields = ['chainType', 'tokenType', 'currencyType', 'payType', 'addressFrom', 'tokenAmount'];
        
        foreach ($requiredFields as $field) {
            if (empty($data[$field])) {
                return false;
            }
        }
        
        return true;
    }
} 