<?php

declare(strict_types=1);

namespace XGateGlobal\SDK\Services;

use XGateGlobal\SDK\Http\Client\HttpClient;
use XGateGlobal\SDK\Auth\{TokenManager, JwtAuthenticator};
use XGateGlobal\SDK\Exceptions\AuthenticationException;

class AuthenticationService
{
    private JwtAuthenticator $authenticator;

    public function __construct(
        private readonly HttpClient $httpClient,
        private readonly TokenManager $tokenManager
    ) {
        $this->authenticator = new JwtAuthenticator(
            $httpClient->getConfiguration(),
            $tokenManager
        );
        $this->authenticator->setHttpClient($httpClient);
    }

    /**
     * Authenticate and get access token
     *
     * @return string Access token
     * @throws AuthenticationException
     */
    public function login(): string
    {
        $token = $this->authenticator->login();
        $this->httpClient->setAccessToken($token);
        return $token;
    }

    /**
     * Refresh access token
     *
     * @return string New access token
     * @throws AuthenticationException
     */
    public function refresh(): string
    {
        $token = $this->authenticator->refresh();
        $this->httpClient->setAccessToken($token);
        return $token;
    }

    /**
     * Logout and clear tokens
     */
    public function logout(): void
    {
        $this->authenticator->logout();
        $this->httpClient->setAccessToken('');
    }

    /**
     * Get current access token
     *
     * @return string|null Current access token
     */
    public function getToken(): ?string
    {
        return $this->authenticator->getToken();
    }

    /**
     * Check if authenticated
     *
     * @return bool True if authenticated
     */
    public function isAuthenticated(): bool
    {
        return $this->tokenManager->hasToken();
    }

    /**
     * Get time until token expiration
     *
     * @return int|null Seconds until expiration
     */
    public function getTimeUntilExpiration(): ?int
    {
        return $this->tokenManager->getTimeUntilExpiration();
    }

    /**
     * Ensure authenticated before making requests
     *
     * @return string Access token
     * @throws AuthenticationException
     */
    public function ensureAuthenticated(): string
    {
        $token = $this->authenticator->getToken();

        if (!$token) {
            $token = $this->login();
        }

        $this->httpClient->setAccessToken($token);
        return $token;
    }
}