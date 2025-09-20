<?php

declare(strict_types=1);

namespace XGateGlobal\SDK\Models;

class BlockchainNetwork
{
    public private(set) string $id;

    public string $name;
    public string $symbol;
    public string $network;
    public ?float $minWithdrawal = null;
    public ?float $maxWithdrawal = null;
    public ?float $withdrawalFee = null;
    public ?int $confirmations = null;
    public bool $enabled = true;
    public ?string $explorerUrl = null;
    public ?array $contractAddresses = null;

    public static function fromArray(array $data): self
    {
        $network = new self();
        $network->id = $data['_id'] ?? $data['id'] ?? '';
        $network->name = $data['name'] ?? '';
        $network->symbol = $data['symbol'] ?? '';
        $network->network = $data['network'] ?? '';
        $network->minWithdrawal = isset($data['minWithdrawal']) ? (float) $data['minWithdrawal'] : null;
        $network->maxWithdrawal = isset($data['maxWithdrawal']) ? (float) $data['maxWithdrawal'] : null;
        $network->withdrawalFee = isset($data['withdrawalFee']) ? (float) $data['withdrawalFee'] : null;
        $network->confirmations = isset($data['confirmations']) ? (int) $data['confirmations'] : null;
        $network->enabled = $data['enabled'] ?? true;
        $network->explorerUrl = $data['explorerUrl'] ?? null;
        $network->contractAddresses = $data['contractAddresses'] ?? null;

        return $network;
    }

    public function toArray(): array
    {
        return array_filter([
            '_id' => $this->id,
            'name' => $this->name ?? null,
            'symbol' => $this->symbol ?? null,
            'network' => $this->network ?? null,
            'minWithdrawal' => $this->minWithdrawal,
            'maxWithdrawal' => $this->maxWithdrawal,
            'withdrawalFee' => $this->withdrawalFee,
            'confirmations' => $this->confirmations,
            'enabled' => $this->enabled,
            'explorerUrl' => $this->explorerUrl,
            'contractAddresses' => $this->contractAddresses,
        ], fn($value) => $value !== null);
    }

    public function validateWithdrawalAmount(float $amount): bool
    {
        if ($this->minWithdrawal !== null && $amount < $this->minWithdrawal) {
            return false;
        }

        if ($this->maxWithdrawal !== null && $amount > $this->maxWithdrawal) {
            return false;
        }

        return true;
    }

    public function getExplorerTxUrl(string $txHash): ?string
    {
        if ($this->explorerUrl === null) {
            return null;
        }

        return rtrim($this->explorerUrl, '/') . '/tx/' . $txHash;
    }
}