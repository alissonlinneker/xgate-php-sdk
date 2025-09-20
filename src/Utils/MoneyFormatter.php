<?php

declare(strict_types=1);

namespace XGateGlobal\SDK\Utils;

use BcMath\Number;
use NumberFormatter;

class MoneyFormatter
{
    private NumberFormatter $formatter;
    private string $currency;
    private int $scale;

    public function __construct(
        string $locale = 'en_US',
        string $currency = 'USD',
        int $scale = 2
    ) {
        $this->formatter = new NumberFormatter($locale, NumberFormatter::CURRENCY);
        $this->currency = $currency;
        $this->scale = $scale;
    }

    /**
     * Format amount as currency
     *
     * @param Number|string|float $amount
     * @param string|null $currency
     * @return string
     */
    public function format(Number|string|float $amount, ?string $currency = null): string
    {
        $bcAmount = $this->toBcMath($amount);
        $currency = $currency ?? $this->currency;
        
        return $this->formatter->formatCurrency((float) (string) $bcAmount, $currency);
    }

    /**
     * Format amount with custom decimal places
     *
     * @param Number|string|float $amount
     * @param int $decimals
     * @return string
     */
    public function formatDecimal(Number|string|float $amount, int $decimals = 2): string
    {
        $bcAmount = $this->toBcMath($amount);
        $formatter = new NumberFormatter('en_US', NumberFormatter::DECIMAL);
        $formatter->setAttribute(NumberFormatter::MIN_FRACTION_DIGITS, $decimals);
        $formatter->setAttribute(NumberFormatter::MAX_FRACTION_DIGITS, $decimals);
        
        return $formatter->format((float) (string) $bcAmount);
    }

    /**
     * Format cryptocurrency amount
     *
     * @param Number|string|float $amount
     * @param string $symbol
     * @param int $decimals
     * @return string
     */
    public function formatCrypto(Number|string|float $amount, string $symbol, int $decimals = 8): string
    {
        $bcAmount = $this->toBcMath($amount);
        $formatted = $this->formatDecimal($bcAmount, $decimals);
        
        return $formatted . ' ' . strtoupper($symbol);
    }

    /**
     * Parse money string to BcMath\Number
     *
     * @param string $money
     * @param string|null $currency
     * @return Number
     */
    public function parse(string $money, ?string $currency = null): Number
    {
        $currency = $currency ?? $this->currency;
        
        // Remove currency symbol and non-numeric characters except . and -
        $cleaned = preg_replace('/[^0-9.-]/', '', $money);
        
        return new Number($cleaned ?: '0');
    }

    /**
     * Convert to BcMath\Number
     *
     * @param Number|string|float $amount
     * @return Number
     */
    private function toBcMath(Number|string|float $amount): Number
    {
        return match(true) {
            $amount instanceof Number => $amount,
            is_string($amount) => new Number($amount),
            is_float($amount) => new Number((string) $amount)
        };
    }

    /**
     * Add amounts using BcMath\Number
     *
     * @param array $amounts
     * @return Number
     */
    public function sum(array $amounts): Number
    {
        $total = new Number('0');
        
        foreach ($amounts as $amount) {
            $bcAmount = $this->toBcMath($amount);
            $total = $total->add($bcAmount);
        }
        
        return $total;
    }

    /**
     * Calculate percentage
     *
     * @param Number|string|float $amount
     * @param Number|string|float $percentage
     * @return Number
     */
    public function percentage(Number|string|float $amount, Number|string|float $percentage): Number
    {
        $bcAmount = $this->toBcMath($amount);
        $bcPercentage = $this->toBcMath($percentage);
        
        return $bcAmount->mul($bcPercentage)->div(new Number('100'));
    }

    /**
     * Calculate fee
     *
     * @param Number|string|float $amount
     * @param Number|string|float $feeRate
     * @param bool $isPercentage
     * @return array
     */
    public function calculateFee(
        Number|string|float $amount,
        Number|string|float $feeRate,
        bool $isPercentage = true
    ): array {
        $bcAmount = $this->toBcMath($amount);
        $bcFeeRate = $this->toBcMath($feeRate);
        
        if ($isPercentage) {
            $fee = $this->percentage($bcAmount, $bcFeeRate);
        } else {
            $fee = $bcFeeRate;
        }
        
        $total = $bcAmount->add($fee);
        
        return [
            'amount' => $bcAmount,
            'fee' => $fee,
            'total' => $total
        ];
    }

    /**
     * Convert between currencies
     *
     * @param Number|string|float $amount
     * @param Number|string|float $rate
     * @return Number
     */
    public function convert(Number|string|float $amount, Number|string|float $rate): Number
    {
        $bcAmount = $this->toBcMath($amount);
        $bcRate = $this->toBcMath($rate);
        
        return $bcAmount->mul($bcRate);
    }

    /**
     * Round to nearest increment
     *
     * @param Number|string|float $amount
     * @param Number|string|float $increment
     * @return Number
     */
    public function roundToIncrement(Number|string|float $amount, Number|string|float $increment): Number
    {
        $bcAmount = $this->toBcMath($amount);
        $bcIncrement = $this->toBcMath($increment);
        
        $divided = $bcAmount->div($bcIncrement);
        $rounded = new Number((string) round((float) (string) $divided));
        
        return $rounded->mul($bcIncrement);
    }

    /**
     * Format as compact notation
     *
     * @param Number|string|float $amount
     * @return string
     */
    public function formatCompact(Number|string|float $amount): string
    {
        $bcAmount = $this->toBcMath($amount);
        $value = (float) (string) $bcAmount;
        
        if ($value >= 1_000_000_000) {
            return number_format($value / 1_000_000_000, 1) . 'B';
        } elseif ($value >= 1_000_000) {
            return number_format($value / 1_000_000, 1) . 'M';
        } elseif ($value >= 1_000) {
            return number_format($value / 1_000, 1) . 'K';
        }
        
        return number_format($value, 2);
    }

    /**
     * Get currency symbol
     *
     * @param string|null $currency
     * @return string
     */
    public function getSymbol(?string $currency = null): string
    {
        $currency = $currency ?? $this->currency;
        $formatter = new NumberFormatter('en_US', NumberFormatter::CURRENCY);
        
        return $formatter->getSymbol(NumberFormatter::CURRENCY_SYMBOL);
    }
}