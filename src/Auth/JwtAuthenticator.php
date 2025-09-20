<?php

declare(strict_types=1);

namespace XGateGlobal\SDK\Auth;

use XGateGlobal\SDK\Configuration;
use XGateGlobal\SDK\Http\Client\HttpClient;
use XGateGlobal\SDK\Exceptions\AuthenticationException;

class JwtAuthenticator
{
    private TokenManager $tokenManager;
    private Configuration $config;
    private ?HttpClient $httpClient = null;

    public function __construct(
        Configuration $config,
        TokenManager $tokenManager
    ) {
        $this->config = $config;
        $this->tokenManager = $tokenManager;
    }

    public function setHttpClient(HttpClient $httpClient): void
    {
        $this->httpClient = $httpClient;
    }

    public function authenticate(): string
    {
        // Check if we already have a valid token
        if ($this->tokenManager->hasToken()) {
            $token = $this->tokenManager->getToken();
            if ($token) {
                return $token;
            }
        }

        // Perform authentication
        return $this->login();
    }

    public function login(): string
    {
        if (!$this->httpClient) {
            throw new AuthenticationException('HTTP client not set');
        }

        $response = $this->httpClient->post('/auth/token', [
            'email' => $this->config->email,
            'password' => $this->config->password,
        ]);

        if (!$response->isSuccessful()) {
            throw new AuthenticationException(
                'Authentication failed: ' . ($response->get('message') ?? 'Unknown error'),
                $response->getStatusCode()
            );
        }

        $data = $response->getData();
        
        if (!isset($data['token']) && !isset($data['access_token'])) {
            throw new AuthenticationException('No token received in authentication response');
        }

        $accessToken = $data['token'] ?? $data['access_token'];
        $refreshToken = $data['refresh_token'] ?? null;
        $expiresIn = isset($data['expires_in']) ? (int) $data['expires_in'] : null;

        $this->tokenManager->setToken($accessToken, $refreshToken, $expiresIn);
        
        return $accessToken;
    }

    public function refresh(): string
    {
        if (!$this->httpClient) {
            throw new AuthenticationException('HTTP client not set');
        }

        $refreshToken = $this->tokenManager->getRefreshToken();
        
        if (!$refreshToken) {
            // No refresh token, perform full login
            return $this->login();
        }

        try {
            $response = $this->httpClient->post('/auth/refresh', [
                'refresh_token' => $refreshToken,
            ]);

            if (!$response->isSuccessful()) {
                // Refresh failed, try full login
                return $this->login();
            }

            $data = $response->getData();
            
            if (!isset($data['token']) && !isset($data['access_token'])) {
                throw new AuthenticationException('No token received in refresh response');
            }

            $accessToken = $data['token'] ?? $data['access_token'];
            $newRefreshToken = $data['refresh_token'] ?? $refreshToken;
            $expiresIn = isset($data['expires_in']) ? (int) $data['expires_in'] : null;

            $this->tokenManager->setToken($accessToken, $newRefreshToken, $expiresIn);
            
            return $accessToken;
            
        } catch (\Exception $e) {
            // Refresh failed, try full login
            return $this->login();
        }
    }

    public function logout(): void
    {
        if ($this->httpClient && $this->tokenManager->hasToken()) {
            try {
                $this->httpClient->post('/auth/logout');
            } catch (\Exception $e) {
                // Ignore logout errors
            }
        }
        
        $this->tokenManager->clearToken();
    }

    public function getToken(): ?string
    {
        if ($this->tokenManager->shouldRefresh()) {
            try {
                $this->refresh();
            } catch (\Exception $e) {
                // If refresh fails, try to login
                $this->login();
            }
        }
        
        return $this->tokenManager->getToken();
    }
}