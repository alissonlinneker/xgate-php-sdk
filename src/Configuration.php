<?php

declare(strict_types=1);

namespace XGateGlobal\SDK;

use XGateGlobal\SDK\Exceptions\ValidationException;
use Psr\Http\Client\ClientInterface;

class Configuration
{
    public const DEFAULT_BASE_URL = 'https://api.xgateglobal.com';
    public const DEFAULT_TIMEOUT = 30;
    public const DEFAULT_RETRY_ATTEMPTS = 3;
    public const DEFAULT_CACHE_TTL = 3600;

    public string $email {
        get => $this->email;
        set (string $email) {
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw new ValidationException('Invalid email format');
            }
            $this->email = $email;
        }
    }

    public string $password {
        get => $this->password;
        set (string $password) {
            if (strlen($password) < 6) {
                throw new ValidationException('Password must be at least 6 characters');
            }
            $this->password = $password;
        }
    }

    public string $baseUrl {
        get => rtrim($this->baseUrl, '/');
        set (string $url) {
            if (!filter_var($url, FILTER_VALIDATE_URL)) {
                throw new ValidationException('Invalid base URL');
            }
            $this->baseUrl = $url;
        }
    }

    public int $timeout {
        get => $this->timeout;
        set (int $timeout) {
            if ($timeout < 1 || $timeout > 300) {
                throw new ValidationException('Timeout must be between 1 and 300 seconds');
            }
            $this->timeout = $timeout;
        }
    }

    public int $retryAttempts {
        get => $this->retryAttempts;
        set (int $attempts) {
            if ($attempts < 0 || $attempts > 10) {
                throw new ValidationException('Retry attempts must be between 0 and 10');
            }
            $this->retryAttempts = $attempts;
        }
    }

    public int $cacheTtl = self::DEFAULT_CACHE_TTL;
    public bool $verifySsl = true;
    public bool $debug = false;
    public ?ClientInterface $httpClient = null;
    public ?string $userAgent = null;

    private array $customHeaders = [];

    public function __construct(array $options = [])
    {
        $this->applyDefaults();
        $this->applyOptions($options);
    }

    public static function create(string|array $config): self
    {
        if (is_string($config)) {
            if (file_exists($config)) {
                $config = require $config;
            } else {
                throw new ValidationException('Configuration file not found: ' . $config);
            }
        }

        return new self($config);
    }

    private function applyDefaults(): void
    {
        $this->baseUrl = self::DEFAULT_BASE_URL;
        $this->timeout = self::DEFAULT_TIMEOUT;
        $this->retryAttempts = self::DEFAULT_RETRY_ATTEMPTS;
        $this->userAgent = 'XGateGlobal-PHP-SDK/1.0.0 (PHP ' . PHP_VERSION . ')';
    }

    private function applyOptions(array $options): void
    {
        foreach ($options as $key => $value) {
            $method = 'set' . ucfirst($key);
            if (method_exists($this, $method)) {
                $this->$method($value);
            } elseif (property_exists($this, $key)) {
                $this->$key = $value;
            }
        }
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;
        return $this;
    }

    public function setPassword(string $password): self
    {
        $this->password = $password;
        return $this;
    }

    public function setBaseUrl(string $baseUrl): self
    {
        $this->baseUrl = $baseUrl;
        return $this;
    }

    public function setTimeout(int $timeout): self
    {
        $this->timeout = $timeout;
        return $this;
    }

    public function setRetryAttempts(int $attempts): self
    {
        $this->retryAttempts = $attempts;
        return $this;
    }

    public function setCacheTtl(int $ttl): self
    {
        $this->cacheTtl = $ttl;
        return $this;
    }

    public function setVerifySsl(bool $verify): self
    {
        $this->verifySsl = $verify;
        return $this;
    }

    public function setDebug(bool $debug): self
    {
        $this->debug = $debug;
        return $this;
    }

    public function setHttpClient(ClientInterface $client): self
    {
        $this->httpClient = $client;
        return $this;
    }

    public function setCustomHeader(string $name, string $value): self
    {
        $this->customHeaders[$name] = $value;
        return $this;
    }

    public function getCustomHeaders(): array
    {
        return $this->customHeaders;
    }

    public function validate(): self
    {
        if (!isset($this->email)) {
            throw new ValidationException('Email is required');
        }

        if (!isset($this->password)) {
            throw new ValidationException('Password is required');
        }

        return $this;
    }

    public function toArray(): array
    {
        return [
            'email' => $this->email ?? '',
            'password' => $this->password ?? '',
            'base_url' => $this->baseUrl,
            'timeout' => $this->timeout,
            'retry_attempts' => $this->retryAttempts,
            'cache_ttl' => $this->cacheTtl,
            'verify_ssl' => $this->verifySsl,
            'debug' => $this->debug,
            'user_agent' => $this->userAgent,
            'custom_headers' => $this->customHeaders,
        ];
    }
}