<?php

declare(strict_types=1);

namespace XGateGlobal\SDK\Services;

use XGateGlobal\SDK\Http\Client\HttpClient;
use XGateGlobal\SDK\Models\{Currency, Transaction, Customer};
use XGateGlobal\SDK\Exceptions\{ValidationException, ApiException};
use BcMath\Number;

class DepositService
{
    public function __construct(
        private readonly HttpClient $httpClient
    ) {}

    /**
     * Get available deposit currencies
     *
     * @return Currency[]
     * @throws ApiException
     */
    public function getCurrencies(): array
    {
        $response = $this->httpClient->get('/deposit/company/currencies');

        // Using PHP 8.4 array functions
        return array_map(
            fn(array $data) => Currency::fromArray($data),
            $response->getData()
        );
    }

    /**
     * Create a new deposit
     *
     * @param Number|string|float $amount Amount to deposit
     * @param string $customerId Customer identifier
     * @param Currency|string $currency Currency object or symbol
     * @param array $metadata Optional metadata
     * @return Transaction
     * @throws ValidationException|ApiException
     */
    public function create(
        Number|string|float $amount,
        string $customerId,
        Currency|string $currency,
        array $metadata = []
    ): Transaction {
        // Use BcMath\Number for precise calculations
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

        if (!empty($metadata)) {
            $payload['metadata'] = $metadata;
        }

        $response = $this->httpClient->post('/deposit', $payload);

        return Transaction::fromArray($response->getData());
    }

    /**
     * Get deposit by ID
     *
     * @param string $depositId Deposit identifier
     * @return Transaction
     * @throws ApiException
     */
    public function get(string $depositId): Transaction
    {
        $response = $this->httpClient->get('/deposit/' . $depositId);
        return Transaction::fromArray($response->getData());
    }

    /**
     * List deposits
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

        $response = $this->httpClient->paginate('/deposit', $query, $page, $perPage);

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
     * Get deposits by customer
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
     * Get deposit limits
     *
     * @param string $currency Currency symbol
     * @return array
     * @throws ApiException
     */
    public function getLimits(string $currency): array
    {
        $response = $this->httpClient->get('/deposit/limits/' . $currency);
        return $response->getData();
    }

    /**
     * Cancel a deposit
     *
     * @param string $depositId Deposit identifier
     * @param string $reason Cancellation reason
     * @return Transaction
     * @throws ApiException
     */
    public function cancel(string $depositId, string $reason = ''): Transaction
    {
        $payload = [];
        if ($reason) {
            $payload['reason'] = $reason;
        }

        $response = $this->httpClient->post('/deposit/' . $depositId . '/cancel', $payload);
        return Transaction::fromArray($response->getData());
    }

    /**
     * Calculate deposit fees
     *
     * @param Number|string|float $amount Amount
     * @param string $currency Currency symbol
     * @return array
     * @throws ApiException
     */
    public function calculateFees(Number|string|float $amount, string $currency): array
    {
        $bcAmount = match(true) {
            $amount instanceof Number => $amount,
            is_string($amount) => new Number($amount),
            is_float($amount) => new Number((string)$amount)
        };

        $response = $this->httpClient->post('/deposit/calculate-fees', [
            'amount' => (string)$bcAmount,
            'currency' => $currency
        ]);

        $data = $response->getData();

        return [
            'amount' => new Number($data['amount'] ?? '0'),
            'fee' => new Number($data['fee'] ?? '0'),
            'total' => new Number($data['total'] ?? '0'),
            'currency' => $data['currency'] ?? $currency
        ];
    }
}