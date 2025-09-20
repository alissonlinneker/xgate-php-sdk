<?php

declare(strict_types=1);

namespace XGateGlobal\SDK\Auth;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\Cache\Adapter\AdapterInterface;
use XGateGlobal\SDK\Configuration;
use XGateGlobal\SDK\Exceptions\AuthenticationException;

class TokenManager
{
    private ?string $accessToken = null;
    private ?string $refreshToken = null;
    private ?int $expiresAt = null;
    private AdapterInterface $cache;
    private Configuration $config;
    private const CACHE_KEY = 'xgate_access_token';
    private const REFRESH_CACHE_KEY = 'xgate_refresh_token';

    public function __construct(
        Configuration $config,
        ?AdapterInterface $cache = null
    ) {
        $this->config = $config;
        $this->cache = $cache ?? new FilesystemAdapter(
            'xgate_sdk',
            $config->cacheTtl,
            sys_get_temp_dir() . '/xgate_cache'
        );
        
        $this->loadFromCache();
    }

    public function setToken(string $accessToken, ?string $refreshToken = null, ?int $expiresIn = null): void
    {
        $this->accessToken = $accessToken;
        $this->refreshToken = $refreshToken;
        
        if ($expiresIn !== null) {
            $this->expiresAt = time() + $expiresIn;
        } else {
            // Try to decode JWT to get expiration
            try {
                $payload = $this->decodeToken($accessToken);
                $this->expiresAt = $payload['exp'] ?? (time() + 3600);
            } catch (\Exception $e) {
                // Default to 1 hour if we can't decode
                $this->expiresAt = time() + 3600;
            }
        }
        
        $this->saveToCache();
    }

    public function getToken(): ?string
    {
        if ($this->isExpired()) {
            $this->clearToken();
            return null;
        }
        
        return $this->accessToken;
    }

    public function getRefreshToken(): ?string
    {
        return $this->refreshToken;
    }

    public function hasToken(): bool
    {
        return $this->accessToken !== null && !$this->isExpired();
    }

    public function isExpired(): bool
    {
        if ($this->expiresAt === null) {
            return false;
        }
        
        // Consider token expired 60 seconds before actual expiration
        return time() >= ($this->expiresAt - 60);
    }

    public function clearToken(): void
    {
        $this->accessToken = null;
        $this->refreshToken = null;
        $this->expiresAt = null;
        
        $this->cache->delete(self::CACHE_KEY);
        $this->cache->delete(self::REFRESH_CACHE_KEY);
    }

    private function loadFromCache(): void
    {
        $cachedToken = $this->cache->getItem(self::CACHE_KEY);
        if ($cachedToken->isHit()) {
            $data = $cachedToken->get();
            if (is_array($data)) {
                $this->accessToken = $data['token'] ?? null;
                $this->expiresAt = $data['expires_at'] ?? null;
            }
        }
        
        $cachedRefresh = $this->cache->getItem(self::REFRESH_CACHE_KEY);
        if ($cachedRefresh->isHit()) {
            $this->refreshToken = $cachedRefresh->get();
        }
    }

    private function saveToCache(): void
    {
        if ($this->accessToken) {
            $tokenItem = $this->cache->getItem(self::CACHE_KEY);
            $tokenItem->set([
                'token' => $this->accessToken,
                'expires_at' => $this->expiresAt,
            ]);
            
            if ($this->expiresAt) {
                $tokenItem->expiresAt(new \DateTime('@' . $this->expiresAt));
            }
            
            $this->cache->save($tokenItem);
        }
        
        if ($this->refreshToken) {
            $refreshItem = $this->cache->getItem(self::REFRESH_CACHE_KEY);
            $refreshItem->set($this->refreshToken);
            $this->cache->save($refreshItem);
        }
    }

    private function decodeToken(string $token): array
    {
        // Split token to get payload without verification
        // This is just to read expiration, actual verification happens server-side
        $parts = explode('.', $token);
        if (count($parts) !== 3) {
            throw new AuthenticationException('Invalid token format');
        }
        
        $payload = json_decode(base64_decode(strtr($parts[1], '-_', '+/')), true);
        
        if (!$payload) {
            throw new AuthenticationException('Invalid token payload');
        }
        
        return $payload;
    }

    public function getTimeUntilExpiration(): ?int
    {
        if ($this->expiresAt === null) {
            return null;
        }
        
        $remaining = $this->expiresAt - time();
        return $remaining > 0 ? $remaining : 0;
    }

    public function shouldRefresh(): bool
    {
        if (!$this->hasToken()) {
            return false;
        }
        
        $timeLeft = $this->getTimeUntilExpiration();
        
        // Refresh if less than 5 minutes remaining
        return $timeLeft !== null && $timeLeft < 300;
    }
}