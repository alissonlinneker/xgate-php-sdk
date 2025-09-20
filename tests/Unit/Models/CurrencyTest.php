<?php

declare(strict_types=1);

namespace XGateGlobal\SDK\Tests\Unit\Models;

use PHPUnit\Framework\TestCase;
use XGateGlobal\SDK\Models\Currency;
use XGateGlobal\SDK\Exceptions\ValidationException;

class CurrencyTest extends TestCase
{
    public function testCreateCurrencyFromArray(): void
    {
        $data = [
            '_id' => '123',
            'symbol' => 'usd',
            'name' => 'US Dollar',
            'minAmount' => 10.0,
            'maxAmount' => 10000.0,
            'decimals' => 2,
            'enabled' => true
        ];

        $currency = Currency::fromArray($data);

        $this->assertEquals('123', $currency->id);
        $this->assertEquals('USD', $currency->symbol); // Should be uppercase
        $this->assertEquals('US Dollar', $currency->name);
        $this->assertEquals(10.0, $currency->minAmount);
        $this->assertEquals(10000.0, $currency->maxAmount);
        $this->assertEquals(2, $currency->decimals);
        $this->assertTrue($currency->enabled);
        $this->assertFalse($currency->isCrypto);
    }

    public function testCryptoCurrencyDetection(): void
    {
        $data = [
            '_id' => '456',
            'symbol' => 'BTC',
            'name' => 'Bitcoin',
            'coinGecko' => 'bitcoin'
        ];

        $currency = Currency::fromArray($data);

        $this->assertTrue($currency->isCrypto);
        $this->assertEquals('bitcoin', $currency->coinGecko);
    }

    public function testSymbolValidation(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Invalid currency symbol');

        $currency = new Currency();
        $currency->symbol = 'X'; // Too short
    }

    public function testSymbolUppercaseTransformation(): void
    {
        $currency = new Currency();
        $currency->symbol = 'usd';

        $this->assertEquals('USD', $currency->symbol);
    }

    public function testValidateAmount(): void
    {
        $currency = new Currency();
        $currency->minAmount = 10.0;
        $currency->maxAmount = 1000.0;

        $this->assertFalse($currency->validateAmount(5.0)); // Below min
        $this->assertTrue($currency->validateAmount(50.0)); // Valid
        $this->assertTrue($currency->validateAmount(10.0)); // At min
        $this->assertTrue($currency->validateAmount(1000.0)); // At max
        $this->assertFalse($currency->validateAmount(1500.0)); // Above max
    }

    public function testToArray(): void
    {
        $data = [
            '_id' => '789',
            'symbol' => 'ETH',
            'name' => 'Ethereum',
            'coinGecko' => 'ethereum',
            'minAmount' => 0.01,
            'maxAmount' => 100.0,
            'decimals' => 18,
            'enabled' => true
        ];

        $currency = Currency::fromArray($data);
        $array = $currency->toArray();

        $this->assertEquals('789', $array['_id']);
        $this->assertEquals('ETH', $array['symbol']);
        $this->assertEquals('Ethereum', $array['name']);
        $this->assertEquals('ethereum', $array['coinGecko']);
        $this->assertEquals(0.01, $array['minAmount']);
        $this->assertEquals(100.0, $array['maxAmount']);
        $this->assertEquals(18, $array['decimals']);
        $this->assertTrue($array['enabled']);
        $this->assertTrue($array['isCrypto']);
    }

    public function testNullValuesAreFilteredInToArray(): void
    {
        $currency = new Currency();
        $currency->symbol = 'USD';
        $currency->name = 'US Dollar';

        $array = $currency->toArray();

        $this->assertArrayNotHasKey('coinGecko', $array);
        $this->assertArrayNotHasKey('minAmount', $array);
        $this->assertArrayNotHasKey('maxAmount', $array);
    }
}