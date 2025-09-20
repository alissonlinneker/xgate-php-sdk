<?php

declare(strict_types=1);

namespace XGateGlobal\SDK\Services;

use XGateGlobal\SDK\Http\Client\HttpClient;
use XGateGlobal\SDK\Models\{Currency, Transaction, BlockchainNetwork};
use XGateGlobal\SDK\Exceptions\{ValidationException, ApiException};
use BcMath\Number;

class WithdrawalService
{
    public function __construct(
        private readonly HttpClient $httpClient
    ) {}

    /**
     * Get available withdrawal currencies
     *
     * @return Currency[]
     * @throws ApiException
     */
    public function getCurrencies(): array
    {
        $response = $this->httpClient->get('/withdraw/company/currencies');

        return array_map(
            fn(array $data) => Currency::fromArray($data),
            $response->getData()
        );
    }

    /**
     * Get available blockchain networks
     *
     * @return BlockchainNetwork[]
     * @throws ApiException
     */
    public function getBlockchainNetworks(): array
    {
        $response = $this->httpClient->get('/withdraw/company/blockchain-networks');

        return array_map(
            fn(array $data) => BlockchainNetwork::fromArray($data),
            $response->getData()
        );
    }

    /**
     * Create a withdrawal
     *
     * @param Number|string|float $amount Amount to withdraw
     * @param string $customerId Customer identifier
     * @param Currency|string $currency Currency object or symbol
     * @param string|null $pixKey PIX key for Brazilian withdrawals
     * @param array $metadata Optional metadata
     * @return Transaction
     * @throws ValidationException|ApiException
     */
    public function create(
        Number|string|float $amount,
        string $customerId,
        Currency|string $currency,
        ?string $pixKey = null,
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

        $currencyData = $currency instanceof Currency
            ? $currency->toArray()
            : ['symbol' => $currency];

        $payload = [
            'amount' => (string)$bcAmount,
            'customerId' => $customerId,
            'currency' => $currencyData
        ];

        if ($pixKey !== null) {
            $payload['pixKey'] = $pixKey;
        }

        if (!empty($metadata)) {
            $payload['metadata'] = $metadata;
        }

        $response = $this->httpClient->post('/withdraw', $payload);

        return Transaction::fromArray($response->getData());
    }

    /**
     * Create cryptocurrency withdrawal
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
    public function createCrypto(
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
     * Get withdrawal by ID
     *
     * @param string $withdrawalId Withdrawal identifier
     * @return Transaction
     * @throws ApiException
     */
    public function get(string $withdrawalId): Transaction
    {
        $response = $this->httpClient->get('/withdraw/' . $withdrawalId);
        return Transaction::fromArray($response->getData());
    }

    /**
     * List withdrawals
     *
     * @param array $filters Optional filters
     * @param int $page Page number
     * @param int $perPage Items per page
     * @return array
     * @throws ApiException
     */
    public function list(array $filters = [], int $page = 1, int $perPage = 20): array
    {
        $query = array_merge($filters, [
            'page' => $page,
            'per_page' => $perPage
        ]);

        $response = $this->httpClient->paginate('/withdraw', $query, $page, $perPage);

        return [
            'items' => array_map(
                fn(array $data) => Transaction::fromArray($data),
                $response->getItems()
            ),
            'pagination' => [
                'current_page' => $response->getCurrentPage(),
                'total_pages' => $response->getTotalPages(),
                'per_page' => $response->getPerPage(),
                'total_items' => $response->getTotalItems(),
                'has_more' => $response->hasMore()
            ]
        ];
    }

    /**
     * Get withdrawals by customer
     *
     * @param string $customerId Customer identifier
     * @param array $filters Optional filters
     * @return Transaction[]
     * @throws ApiException
     */
    public function getByCustomer(string $customerId, array $filters = []): array
    {
        $filters['customerId'] = $customerId;
        $result = $this->list($filters);
        return $result['items'];
    }

    /**
     * Get withdrawal limits
     *
     * @param string $currency Currency symbol
     * @return array
     * @throws ApiException
     */
    public function getLimits(string $currency): array
    {
        $response = $this->httpClient->get('/withdraw/limits/' . $currency);
        return $response->getData();
    }

    /**
     * Cancel a withdrawal
     *
     * @param string $withdrawalId Withdrawal identifier
     * @param string $reason Cancellation reason
     * @return Transaction
     * @throws ApiException
     */
    public function cancel(string $withdrawalId, string $reason = ''): Transaction
    {
        $payload = [];
        if ($reason) {
            $payload['reason'] = $reason;
        }

        $response = $this->httpClient->post('/withdraw/' . $withdrawalId . '/cancel', $payload);
        return Transaction::fromArray($response->getData());
    }

    /**
     * Calculate withdrawal fees
     *
     * @param Number|string|float $amount Amount
     * @param string $currency Currency symbol
     * @param string|null $network Network for crypto withdrawals
     * @return array
     * @throws ApiException
     */
    public function calculateFees(
        Number|string|float $amount,
        string $currency,
        ?string $network = null
    ): array {
        $bcAmount = match(true) {
            $amount instanceof Number => $amount,
            is_string($amount) => new Number($amount),
            is_float($amount) => new Number((string)$amount)
        };

        $payload = [
            'amount' => (string)$bcAmount,
            'currency' => $currency
        ];

        if ($network !== null) {
            $payload['network'] = $network;
        }

        $response = $this->httpClient->post('/withdraw/calculate-fees', $payload);

        $data = $response->getData();

        return [
            'amount' => new Number($data['amount'] ?? '0'),
            'fee' => new Number($data['fee'] ?? '0'),
            'total' => new Number($data['total'] ?? '0'),
            'currency' => $data['currency'] ?? $currency,
            'network' => $data['network'] ?? $network
        ];
    }

    /**
     * Validate withdrawal address
     *
     * @param string $address Wallet address
     * @param string $network Blockchain network
     * @return bool
     * @throws ApiException
     */
    public function validateAddress(string $address, string $network): bool
    {
        $response = $this->httpClient->post('/withdraw/validate-address', [
            'address' => $address,
            'network' => $network
        ]);

        return $response->get('valid', false);
    }
}