<?php

declare(strict_types=1);

namespace XGateGlobal\SDK\Models;

use BcMath\Number;
use DateTimeImmutable;
use XGateGlobal\SDK\Exceptions\ValidationException;

class Transaction
{
    public private(set) string $id;

    public string $type {
        get => $this->type;
        set (string $value) {
            if (!in_array($value, ['deposit', 'withdrawal', 'transfer'], true)) {
                throw new ValidationException('Invalid transaction type');
            }
            $this->type = $value;
        }
    }

    public string $status {
        get => $this->status;
        set (string $value) {
            if (!in_array($value, ['pending', 'processing', 'completed', 'failed', 'cancelled'], true)) {
                throw new ValidationException('Invalid transaction status');
            }
            $this->status = $value;
        }
    }

    public Number $amount {
        get => $this->amount;
        set (Number|string|float $value) {
            $this->amount = match(true) {
                $value instanceof Number => $value,
                is_string($value) => new Number($value),
                is_float($value) => new Number((string)$value)
            };
        }
    }

    public string $customerId;
    public Currency|string $currency;
    public ?string $reference = null;
    public ?string $description = null;
    public ?array $metadata = null;
    public ?DateTimeImmutable $createdAt = null;
    public ?DateTimeImmutable $updatedAt = null;
    public ?DateTimeImmutable $completedAt = null;
    public ?Number $fee = null;
    public ?string $pixKey = null;
    public ?string $walletAddress = null;
    public ?string $blockchainNetwork = null;
    public ?string $txHash = null;

    public static function fromArray(array $data): self
    {
        $transaction = new self();
        $transaction->id = $data['_id'] ?? $data['id'] ?? '';
        $transaction->type = $data['type'] ?? 'deposit';
        $transaction->status = $data['status'] ?? 'pending';
        $transaction->amount = $data['amount'] ?? '0';
        $transaction->customerId = $data['customerId'] ?? '';
        
        if (isset($data['currency'])) {
            $transaction->currency = is_array($data['currency']) 
                ? Currency::fromArray($data['currency'])
                : $data['currency'];
        }
        
        $transaction->reference = $data['reference'] ?? null;
        $transaction->description = $data['description'] ?? null;
        $transaction->metadata = $data['metadata'] ?? null;
        
        if (isset($data['createdAt'])) {
            $transaction->createdAt = new DateTimeImmutable($data['createdAt']);
        }
        
        if (isset($data['updatedAt'])) {
            $transaction->updatedAt = new DateTimeImmutable($data['updatedAt']);
        }
        
        if (isset($data['completedAt'])) {
            $transaction->completedAt = new DateTimeImmutable($data['completedAt']);
        }
        
        if (isset($data['fee'])) {
            $transaction->fee = new Number($data['fee']);
        }
        
        $transaction->pixKey = $data['pixKey'] ?? null;
        $transaction->walletAddress = $data['walletAddress'] ?? null;
        $transaction->blockchainNetwork = $data['blockchainNetwork'] ?? null;
        $transaction->txHash = $data['txHash'] ?? null;

        return $transaction;
    }

    public function toArray(): array
    {
        $data = [
            '_id' => $this->id ?? null,
            'type' => $this->type ?? null,
            'status' => $this->status ?? null,
            'amount' => isset($this->amount) ? (string)$this->amount : null,
            'customerId' => $this->customerId ?? null,
            'currency' => $this->currency instanceof Currency 
                ? $this->currency->toArray() 
                : $this->currency ?? null,
            'reference' => $this->reference,
            'description' => $this->description,
            'metadata' => $this->metadata,
            'createdAt' => $this->createdAt?->format('c'),
            'updatedAt' => $this->updatedAt?->format('c'),
            'completedAt' => $this->completedAt?->format('c'),
            'fee' => isset($this->fee) ? (string)$this->fee : null,
            'pixKey' => $this->pixKey,
            'walletAddress' => $this->walletAddress,
            'blockchainNetwork' => $this->blockchainNetwork,
            'txHash' => $this->txHash,
        ];

        return array_filter($data, fn($value) => $value !== null);
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isProcessing(): bool
    {
        return $this->status === 'processing';
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }

    public function isCancelled(): bool
    {
        return $this->status === 'cancelled';
    }
}