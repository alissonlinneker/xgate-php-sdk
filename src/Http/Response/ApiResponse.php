<?php

declare(strict_types=1);

namespace XGateGlobal\SDK\Http\Response;

use Psr\Http\Message\ResponseInterface;

class ApiResponse
{
    private array $data;
    private int $statusCode;
    private array $headers;
    private ?array $meta = null;

    public function __construct(
        array $data,
        int $statusCode = 200,
        array $headers = []
    ) {
        $this->data = $data;
        $this->statusCode = $statusCode;
        $this->headers = $headers;
        
        if (isset($data['meta'])) {
            $this->meta = $data['meta'];
        }
    }

    public static function fromPsrResponse(ResponseInterface $response): self
    {
        $body = (string) $response->getBody();
        $data = json_decode($body, true) ?? [];
        
        $headers = [];
        foreach ($response->getHeaders() as $name => $values) {
            $headers[$name] = implode(', ', $values);
        }
        
        return new self($data, $response->getStatusCode(), $headers);
    }

    public function getData(): array
    {
        return $this->data;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->data[$key] ?? $default;
    }

    public function has(string $key): bool
    {
        return isset($this->data[$key]);
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function getHeaders(): array
    {
        return $this->headers;
    }

    public function getHeader(string $name): ?string
    {
        return $this->headers[$name] ?? null;
    }

    public function getMeta(): ?array
    {
        return $this->meta;
    }

    public function isSuccessful(): bool
    {
        return $this->statusCode >= 200 && $this->statusCode < 300;
    }

    public function isError(): bool
    {
        return $this->statusCode >= 400;
    }

    public function toArray(): array
    {
        return $this->data;
    }

    public function toJson(): string
    {
        return json_encode($this->data, JSON_THROW_ON_ERROR);
    }
}