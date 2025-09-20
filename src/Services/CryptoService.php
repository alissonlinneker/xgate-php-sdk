<?php

declare(strict_types=1);

namespace XGateGlobal\SDK\Services;

use XGateGlobal\SDK\Http\Client\HttpClient;
use XGateGlobal\SDK\Models\{Wallet, Transaction, BlockchainNetwork};
use XGateGlobal\SDK\Exceptions\{ValidationException, ApiException};
use BcMath\Number;

class CryptoService
{
    public function __construct(
        private readonly HttpClient $httpClient
    ) {}

    /**
     * Get customer crypto wallet
     *
     * @param string $customerId Customer identifier
     * @return Wallet
     * @throws ApiException
     */
    public function getWallet(string $customerId): Wallet
    {
        $response = $this->httpClient->get('/crypto/customer/' . $customerId . '/wallet');
        return Wallet::fromArray($response->getData());
    }

    /**
     * Get all customer wallets
     *
     * @param string $customerId Customer identifier
     * @return Wallet[]
     * @throws ApiException
     */
    public function getWallets(string $customerId): array
    {
        $response = $this->httpClient->get('/crypto/customer/' . $customerId . '/wallets');

        return array_map(
            fn(array $data) => Wallet::fromArray($data),
            $response->getData()
        );
    }

    /**
     * Create crypto withdrawal
     *
     * @param Number|string|float $amount Amount to withdraw
     * @param string $wallet Wallet address
     * @param string $customerId Customer identifier
     * @param string $cryptocurrency Cryptocurrency symbol
     * @param string $network Blockchain network
     * @param array $metadata Optional metadata
     * @return Transaction
     * @throws ValidationException|ApiException
     */
    public function withdraw(
        Number|string|float $amount,
        string $wallet,
        string $customerId,
        string $cryptocurrency,
        string $network,
        array $metadata = []
    ): Transaction {
        $bcAmount = match(true) {
            $amount instanceof Number => $amount,
            is_string($amount) => new Number($amount),
            is_float($amount) => new Number((string)$amount)
        };

        if ($bcAmount->compare('0') <= 0) {
            throw new ValidationException('Amount must be greater than zero');
        }

        if (empty($wallet)) {
            throw new ValidationException('Wallet address is required');
        }

        $payload = [
            'amount' => (string)$bcAmount,
            'wallet' => $wallet,
            'customerId' => $customerId,
            'cryptocurrency' => $cryptocurrency,
            'network' => $network
        ];

        if (!empty($metadata)) {
            $payload['metadata'] = $metadata;
        }

        $response = $this->httpClient->post('/withdraw/transaction/crypto/amount', $payload);

        return Transaction::fromArray($response->getData());
    }

    /**
     * Get crypto deposit address
     *
     * @param string $customerId Customer identifier
     * @param string $cryptocurrency Cryptocurrency symbol
     * @param string $network Blockchain network
     * @return array
     * @throws ApiException
     */
    public function getDepositAddress(
        string $customerId,
        string $cryptocurrency,
        string $network
    ): array {
        $response = $this->httpClient->post('/crypto/deposit-address', [
            'customerId' => $customerId,
            'cryptocurrency' => $cryptocurrency,
            'network' => $network
        ]);

        return $response->getData();
    }

    /**
     * Get crypto transaction by hash
     *
     * @param string $txHash Transaction hash
     * @param string $network Blockchain network
     * @return Transaction
     * @throws ApiException
     */
    public function getTransactionByHash(string $txHash, string $network): Transaction
    {
        $response = $this->httpClient->get('/crypto/transaction/' . $txHash, [
            'network' => $network
        ]);

        return Transaction::fromArray($response->getData());
    }

    /**
     * Get crypto balance
     *
     * @param string $customerId Customer identifier
     * @param string $cryptocurrency Cryptocurrency symbol
     * @return Number
     * @throws ApiException
     */
    public function getBalance(string $customerId, string $cryptocurrency): Number
    {
        $response = $this->httpClient->get('/crypto/customer/' . $customerId . '/balance/' . $cryptocurrency);

        return new Number($response->get('balance', '0'));
    }

    /**
     * Get all crypto balances for customer
     *
     * @param string $customerId Customer identifier
     * @return array
     * @throws ApiException
     */
    public function getBalances(string $customerId): array
    {
        $response = $this->httpClient->get('/crypto/customer/' . $customerId . '/balances');

        $balances = [];
        foreach ($response->getData() as $currency => $balance) {
            $balances[$currency] = new Number($balance);
        }

        return $balances;
    }

    /**
     * Convert crypto amount to fiat
     *
     * @param Number|string|float $amount Crypto amount
     * @param string $fromCurrency Crypto currency
     * @param string $toCurrency Fiat currency
     * @return array
     * @throws ApiException
     */
    public function convertToFiat(
        Number|string|float $amount,
        string $fromCurrency,
        string $toCurrency
    ): array {
        $bcAmount = match(true) {
            $amount instanceof Number => $amount,
            is_string($amount) => new Number($amount),
            is_float($amount) => new Number((string)$amount)
        };

        $response = $this->httpClient->post('/crypto/convert', [
            'amount' => (string)$bcAmount,
            'from' => $fromCurrency,
            'to' => $toCurrency
        ]);

        $data = $response->getData();

        return [
            'from_amount' => new Number($data['from_amount'] ?? '0'),
            'from_currency' => $data['from_currency'] ?? $fromCurrency,
            'to_amount' => new Number($data['to_amount'] ?? '0'),
            'to_currency' => $data['to_currency'] ?? $toCurrency,
            'rate' => new Number($data['rate'] ?? '0'),
            'timestamp' => $data['timestamp'] ?? time()
        ];
    }

    /**
     * Get crypto prices
     *
     * @param array $symbols Cryptocurrency symbols
     * @param string $fiatCurrency Fiat currency for prices
     * @return array
     * @throws ApiException
     */
    public function getPrices(array $symbols, string $fiatCurrency = 'USD'): array
    {
        $response = $this->httpClient->get('/crypto/prices', [
            'symbols' => implode(',', $symbols),
            'currency' => $fiatCurrency
        ]);

        $prices = [];
        foreach ($response->getData() as $symbol => $price) {
            $prices[$symbol] = new Number($price);
        }

        return $prices;
    }

    /**
     * Get network fees
     *
     * @param string $network Blockchain network
     * @return array
     * @throws ApiException
     */
    public function getNetworkFees(string $network): array
    {
        $response = $this->httpClient->get('/crypto/network-fees/' . $network);

        $data = $response->getData();

        return [
            'network' => $data['network'] ?? $network,
            'low' => new Number($data['low'] ?? '0'),
            'medium' => new Number($data['medium'] ?? '0'),
            'high' => new Number($data['high'] ?? '0'),
            'currency' => $data['currency'] ?? 'USD',
            'estimated_time' => [
                'low' => $data['estimated_time']['low'] ?? 60,
                'medium' => $data['estimated_time']['medium'] ?? 30,
                'high' => $data['estimated_time']['high'] ?? 10
            ]
        ];
    }

    /**
     * Validate crypto address
     *
     * @param string $address Wallet address
     * @param string $network Blockchain network
     * @return bool
     * @throws ApiException
     */
    public function validateAddress(string $address, string $network): bool
    {
        $response = $this->httpClient->post('/crypto/validate-address', [
            'address' => $address,
            'network' => $network
        ]);

        return $response->get('valid', false);
    }
}