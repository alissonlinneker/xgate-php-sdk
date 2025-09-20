<?php

declare(strict_types=1);

namespace XGateGlobal\SDK\Models;

use BcMath\Number;
use DateTimeImmutable;

class Wallet
{
    public private(set) string $id;

    public string $customerId;
    public string $address;
    public ?string $network = null;
    public ?Currency $currency = null;
    public Number $balance {
        get => $this->balance ?? new Number('0');
        set (Number|string|float $value) {
            $this->balance = match(true) {
                $value instanceof Number => $value,
                is_string($value) => new Number($value),
                is_float($value) => new Number((string)$value)
            };
        }
    }
    public ?string $label = null;
    public bool $active = true;
    public ?DateTimeImmutable $createdAt = null;
    public ?DateTimeImmutable $updatedAt = null;

    public static function fromArray(array $data): self
    {
        $wallet = new self();
        $wallet->id = $data['_id'] ?? $data['id'] ?? '';
        $wallet->customerId = $data['customerId'] ?? '';
        $wallet->address = $data['address'] ?? '';
        $wallet->network = $data['network'] ?? null;
        
        if (isset($data['currency'])) {
            $wallet->currency = is_array($data['currency']) 
                ? Currency::fromArray($data['currency'])
                : null;
        }
        
        $wallet->balance = $data['balance'] ?? '0';
        $wallet->label = $data['label'] ?? null;
        $wallet->active = $data['active'] ?? true;

        if (isset($data['createdAt'])) {
            $wallet->createdAt = new DateTimeImmutable($data['createdAt']);
        }

        if (isset($data['updatedAt'])) {
            $wallet->updatedAt = new DateTimeImmutable($data['updatedAt']);
        }

        return $wallet;
    }

    public function toArray(): array
    {
        return array_filter([
            '_id' => $this->id ?? null,
            'customerId' => $this->customerId ?? null,
            'address' => $this->address ?? null,
            'network' => $this->network,
            'currency' => $this->currency?->toArray(),
            'balance' => isset($this->balance) ? (string)$this->balance : null,
            'label' => $this->label,
            'active' => $this->active,
            'createdAt' => $this->createdAt?->format('c'),
            'updatedAt' => $this->updatedAt?->format('c'),
        ], fn($value) => $value !== null);
    }

    public function hasBalance(): bool
    {
        return $this->balance->compare('0') > 0;
    }
}