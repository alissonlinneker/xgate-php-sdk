<?php

declare(strict_types=1);

namespace XGateGlobal\SDK\Models;

use DateTimeImmutable;

class Customer
{
    public private(set) string $id;

    public string $email;
    public ?string $name = null;
    public ?string $document = null;
    public ?string $phone = null;
    public ?array $metadata = null;
    public bool $active = true;
    public ?DateTimeImmutable $createdAt = null;
    public ?DateTimeImmutable $updatedAt = null;

    public static function fromArray(array $data): self
    {
        $customer = new self();
        $customer->id = $data['_id'] ?? $data['id'] ?? '';
        $customer->email = $data['email'] ?? '';
        $customer->name = $data['name'] ?? null;
        $customer->document = $data['document'] ?? null;
        $customer->phone = $data['phone'] ?? null;
        $customer->metadata = $data['metadata'] ?? null;
        $customer->active = $data['active'] ?? true;

        if (isset($data['createdAt'])) {
            $customer->createdAt = new DateTimeImmutable($data['createdAt']);
        }

        if (isset($data['updatedAt'])) {
            $customer->updatedAt = new DateTimeImmutable($data['updatedAt']);
        }

        return $customer;
    }

    public function toArray(): array
    {
        return array_filter([
            '_id' => $this->id ?? null,
            'email' => $this->email ?? null,
            'name' => $this->name,
            'document' => $this->document,
            'phone' => $this->phone,
            'metadata' => $this->metadata,
            'active' => $this->active,
            'createdAt' => $this->createdAt?->format('c'),
            'updatedAt' => $this->updatedAt?->format('c'),
        ], fn($value) => $value !== null);
    }
}