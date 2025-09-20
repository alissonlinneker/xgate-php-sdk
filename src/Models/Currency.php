<?php

declare(strict_types=1);

namespace XGateGlobal\SDK\Models;

use XGateGlobal\SDK\Exceptions\ValidationException;

class Currency
{
    public private(set) string $id = '';

    private string $_symbol = '';

    public string $symbol {
        get => strtoupper($this->_symbol);
        set (string $value) {
            if (strlen($value) < 2 || strlen($value) > 10) {
                throw new ValidationException('Invalid currency symbol');
            }
            $this->_symbol = $value;
        }
    }

    public string $name = '';
    public ?string $coinGecko = null;
    public ?float $minAmount = null;
    public ?float $maxAmount = null;
    public ?int $decimals = null;
    public bool $enabled = true;

    public bool $isCrypto {
        get => $this->coinGecko !== null;
    }

    public static function fromArray(array $data): self
    {
        $currency = new self();
        $currency->id = $data['_id'] ?? $data['id'] ?? '';
        $currency->symbol = $data['symbol'] ?? '';
        $currency->name = $data['name'] ?? '';
        $currency->coinGecko = $data['coinGecko'] ?? null;
        $currency->minAmount = isset($data['minAmount']) ? (float) $data['minAmount'] : null;
        $currency->maxAmount = isset($data['maxAmount']) ? (float) $data['maxAmount'] : null;
        $currency->decimals = isset($data['decimals']) ? (int) $data['decimals'] : null;
        $currency->enabled = $data['enabled'] ?? true;

        return $currency;
    }

    public function toArray(): array
    {
        return array_filter([
            '_id' => $this->id,
            'symbol' => $this->symbol,
            'name' => $this->name,
            'coinGecko' => $this->coinGecko,
            'minAmount' => $this->minAmount,
            'maxAmount' => $this->maxAmount,
            'decimals' => $this->decimals,
            'enabled' => $this->enabled,
            'isCrypto' => $this->isCrypto,
        ], fn($value) => $value !== null);
    }

    public function validateAmount(float $amount): bool
    {
        if ($this->minAmount !== null && $amount < $this->minAmount) {
            return false;
        }

        if ($this->maxAmount !== null && $amount > $this->maxAmount) {
            return false;
        }

        return true;
    }
}