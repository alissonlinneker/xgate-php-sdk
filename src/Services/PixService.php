<?php

declare(strict_types=1);

namespace XGateGlobal\SDK\Services;

use XGateGlobal\SDK\Http\Client\HttpClient;
use XGateGlobal\SDK\Models\Transaction;
use XGateGlobal\SDK\Exceptions\{ValidationException, ApiException};
use BcMath\Number;

class PixService
{
    public function __construct(
        private readonly HttpClient $httpClient
    ) {}

    /**
     * Get customer PIX keys
     *
     * @param string $customerId Customer identifier
     * @return array
     * @throws ApiException
     */
    public function getKeys(string $customerId): array
    {
        $response = $this->httpClient->get('/pix/customer/' . $customerId . '/key');

        return $response->getData();
    }

    /**
     * Register PIX key
     *
     * @param string $customerId Customer identifier
     * @param string $key PIX key
     * @param string $type Key type (cpf, cnpj, email, phone, random)
     * @param array $metadata Optional metadata
     * @return array
     * @throws ValidationException|ApiException
     */
    public function registerKey(
        string $customerId,
        string $key,
        string $type,
        array $metadata = []
    ): array {
        $validTypes = ['cpf', 'cnpj', 'email', 'phone', 'random'];
        if (!in_array($type, $validTypes, true)) {
            throw new ValidationException('Invalid PIX key type: ' . $type);
        }

        $this->validatePixKey($key, $type);

        $payload = [
            'customerId' => $customerId,
            'key' => $key,
            'type' => $type
        ];

        if (!empty($metadata)) {
            $payload['metadata'] = $metadata;
        }

        $response = $this->httpClient->post('/pix/register-key', $payload);

        return $response->getData();
    }

    /**
     * Delete PIX key
     *
     * @param string $customerId Customer identifier
     * @param string $key PIX key
     * @return bool
     * @throws ApiException
     */
    public function deleteKey(string $customerId, string $key): bool
    {
        $response = $this->httpClient->delete('/pix/customer/' . $customerId . '/key/' . $key);

        return $response->get('success', false);
    }

    /**
     * Create PIX withdrawal
     *
     * @param Number|string|float $amount Amount
     * @param string $customerId Customer identifier
     * @param string $pixKey PIX key
     * @param array $metadata Optional metadata
     * @return Transaction
     * @throws ValidationException|ApiException
     */
    public function withdraw(
        Number|string|float $amount,
        string $customerId,
        string $pixKey,
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

        if (empty($pixKey)) {
            throw new ValidationException('PIX key is required');
        }

        $payload = [
            'amount' => (string)$bcAmount,
            'customerId' => $customerId,
            'currency' => 'BRL', // PIX is always BRL
            'pixKey' => $pixKey
        ];

        if (!empty($metadata)) {
            $payload['metadata'] = $metadata;
        }

        $response = $this->httpClient->post('/withdraw', $payload);

        return Transaction::fromArray($response->getData());
    }

    /**
     * Generate PIX QR Code
     *
     * @param Number|string|float $amount Amount
     * @param string $description Description
     * @param string|null $pixKey Optional PIX key
     * @return array
     * @throws ValidationException|ApiException
     */
    public function generateQRCode(
        Number|string|float $amount,
        string $description,
        ?string $pixKey = null
    ): array {
        $bcAmount = match(true) {
            $amount instanceof Number => $amount,
            is_string($amount) => new Number($amount),
            is_float($amount) => new Number((string)$amount)
        };

        if ($bcAmount->compare('0') <= 0) {
            throw new ValidationException('Amount must be greater than zero');
        }

        $payload = [
            'amount' => (string)$bcAmount,
            'description' => $description
        ];

        if ($pixKey !== null) {
            $payload['pixKey'] = $pixKey;
        }

        $response = $this->httpClient->post('/pix/qrcode', $payload);

        return $response->getData();
    }

    /**
     * Decode PIX QR Code
     *
     * @param string $qrCode QR Code content
     * @return array
     * @throws ApiException
     */
    public function decodeQRCode(string $qrCode): array
    {
        $response = $this->httpClient->post('/pix/decode-qrcode', [
            'qrcode' => $qrCode
        ]);

        return $response->getData();
    }

    /**
     * Get PIX transaction by E2E ID
     *
     * @param string $e2eId End-to-end identifier
     * @return Transaction
     * @throws ApiException
     */
    public function getTransactionByE2E(string $e2eId): Transaction
    {
        $response = $this->httpClient->get('/pix/transaction/e2e/' . $e2eId);

        return Transaction::fromArray($response->getData());
    }

    /**
     * List PIX transactions
     *
     * @param string $customerId Customer identifier
     * @param array $filters Optional filters
     * @param int $page Page number
     * @param int $perPage Items per page
     * @return array
     * @throws ApiException
     */
    public function listTransactions(
        string $customerId,
        array $filters = [],
        int $page = 1,
        int $perPage = 20
    ): array {
        $query = array_merge($filters, [
            'customerId' => $customerId,
            'page' => $page,
            'per_page' => $perPage
        ]);

        $response = $this->httpClient->paginate('/pix/transactions', $query, $page, $perPage);

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
     * Validate PIX key
     *
     * @param string $key PIX key
     * @param string $type Key type
     * @return void
     * @throws ValidationException
     */
    private function validatePixKey(string $key, string $type): void
    {
        switch ($type) {
            case 'cpf':
                if (!preg_match('/^\d{11}$/', $key)) {
                    throw new ValidationException('Invalid CPF format');
                }
                break;

            case 'cnpj':
                if (!preg_match('/^\d{14}$/', $key)) {
                    throw new ValidationException('Invalid CNPJ format');
                }
                break;

            case 'email':
                if (!filter_var($key, FILTER_VALIDATE_EMAIL)) {
                    throw new ValidationException('Invalid email format');
                }
                break;

            case 'phone':
                if (!preg_match('/^\+55\d{10,11}$/', $key)) {
                    throw new ValidationException('Invalid phone format');
                }
                break;

            case 'random':
                if (!preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i', $key)) {
                    throw new ValidationException('Invalid random key format');
                }
                break;
        }
    }

    /**
     * Request PIX refund
     *
     * @param string $transactionId Transaction ID
     * @param string $reason Refund reason
     * @return Transaction
     * @throws ApiException
     */
    public function refund(string $transactionId, string $reason = ''): Transaction
    {
        $payload = [];
        if ($reason) {
            $payload['reason'] = $reason;
        }

        $response = $this->httpClient->post('/pix/refund/' . $transactionId, $payload);

        return Transaction::fromArray($response->getData());
    }
}