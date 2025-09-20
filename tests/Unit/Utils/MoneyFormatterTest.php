<?php

declare(strict_types=1);

namespace XGateGlobal\SDK\Tests\Unit\Utils;

use PHPUnit\Framework\TestCase;
use XGateGlobal\SDK\Utils\MoneyFormatter;
use BcMath\Number;

class MoneyFormatterTest extends TestCase
{
    private MoneyFormatter $formatter;

    protected function setUp(): void
    {
        $this->formatter = new MoneyFormatter('en_US', 'USD');
    }

    public function testFormatCurrency(): void
    {
        $amount = new Number('1234.56');
        $formatted = $this->formatter->format($amount);

        $this->assertStringContainsString('1,234.56', $formatted);
    }

    public function testFormatDecimal(): void
    {
        $amount = new Number('1234.5678');
        $formatted = $this->formatter->formatDecimal($amount, 2);

        $this->assertEquals('1,234.57', $formatted);
    }

    public function testFormatCrypto(): void
    {
        $amount = new Number('0.00000123');
        $formatted = $this->formatter->formatCrypto($amount, 'BTC', 8);

        $this->assertEquals('0.00000123 BTC', $formatted);
    }

    public function testParseMoneyString(): void
    {
        $parsed = $this->formatter->parse('$1,234.56');

        $this->assertInstanceOf(Number::class, $parsed);
        $this->assertEquals('1234.56', (string)$parsed);
    }

    public function testSum(): void
    {
        $amounts = [
            new Number('100.50'),
            '200.25',
            300.75
        ];

        $total = $this->formatter->sum($amounts);

        $this->assertInstanceOf(Number::class, $total);
        $this->assertEquals('601.50', (string)$total);
    }

    public function testPercentageCalculation(): void
    {
        $amount = new Number('100');
        $percentage = new Number('15');

        $result = $this->formatter->percentage($amount, $percentage);

        $this->assertEquals('15', (string)$result);
    }

    public function testFeeCalculation(): void
    {
        $amount = new Number('100');
        $feeRate = new Number('2.5');

        $result = $this->formatter->calculateFee($amount, $feeRate, true);

        $this->assertEquals('100', (string)$result['amount']);
        $this->assertEquals('2.5', (string)$result['fee']);
        $this->assertEquals('102.5', (string)$result['total']);
    }

    public function testFlatFeeCalculation(): void
    {
        $amount = new Number('100');
        $feeRate = new Number('5');

        $result = $this->formatter->calculateFee($amount, $feeRate, false);

        $this->assertEquals('100', (string)$result['amount']);
        $this->assertEquals('5', (string)$result['fee']);
        $this->assertEquals('105', (string)$result['total']);
    }

    public function testCurrencyConversion(): void
    {
        $amount = new Number('100');
        $rate = new Number('1.2');

        $converted = $this->formatter->convert($amount, $rate);

        $this->assertEquals('120.0', (string)$converted);
    }

    public function testRoundToIncrement(): void
    {
        $amount = new Number('123.45');
        $increment = new Number('0.25');

        $rounded = $this->formatter->roundToIncrement($amount, $increment);

        $this->assertEquals('123.50', (string)$rounded);
    }

    public function testFormatCompact(): void
    {
        $this->assertEquals('1.5K', $this->formatter->formatCompact(new Number('1500')));
        $this->assertEquals('2.5M', $this->formatter->formatCompact(new Number('2500000')));
        $this->assertEquals('3.2B', $this->formatter->formatCompact(new Number('3200000000')));
        $this->assertEquals('999.00', $this->formatter->formatCompact(new Number('999')));
    }

    public function testVariousInputTypes(): void
    {
        // Test with Number
        $result1 = $this->formatter->formatDecimal(new Number('100.50'), 2);
        $this->assertEquals('100.50', $result1);

        // Test with string
        $result2 = $this->formatter->formatDecimal('100.50', 2);
        $this->assertEquals('100.50', $result2);

        // Test with float
        $result3 = $this->formatter->formatDecimal(100.50, 2);
        $this->assertEquals('100.50', $result3);
    }
}