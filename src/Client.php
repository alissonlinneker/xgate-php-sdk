<?php

declare(strict_types=1);

namespace XGateGlobal\SDK;

use XGateGlobal\SDK\Services\{
    AuthenticationService,
    DepositService,
    WithdrawalService,
    CryptoService,
    PixService
};
use XGateGlobal\SDK\Http\Client\HttpClient;
use XGateGlobal\SDK\Auth\TokenManager;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class Client
{
    public private(set) string $version = '1.0.0';

    private Configuration $config {
        set (Configuration $config) {
            $this->config = $config->validate();
        }
    }

    public readonly AuthenticationService $auth;
    public readonly DepositService $deposits;
    public readonly WithdrawalService $withdrawals;
    public readonly CryptoService $crypto;
    public readonly PixService $pix;

    private ?HttpClient $httpClient = null;
    private LoggerInterface $logger;

    public function __construct(
        string|Configuration $config,
        ?LoggerInterface $logger = null
    ) {
        $this->config = $config instanceof Configuration
            ? $config
            : Configuration::create($config);

        $this->logger = $logger ?? new NullLogger();

        $this->initializeServices();
    }

    private function initializeServices(): void
    {
        $httpClient = $this->getHttpClient();
        $tokenManager = new TokenManager($this->config);

        $this->auth = new AuthenticationService($httpClient, $tokenManager);
        $this->deposits = new DepositService($httpClient);
        $this->withdrawals = new WithdrawalService($httpClient);
        $this->crypto = new CryptoService($httpClient);
        $this->pix = new PixService($httpClient);
    }

    private function getHttpClient(): HttpClient
    {
        return $this->httpClient ??= new HttpClient(
            $this->config,
            $this->logger
        );
    }

    public function getVersion(): string
    {
        return $this->version;
    }

    public function getConfiguration(): Configuration
    {
        return $this->config;
    }
}