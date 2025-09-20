<?php

declare(strict_types=1);

namespace XGateGlobal\SDK\Utils;

use Symfony\Component\Cache\Adapter\AdapterInterface;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use XGateGlobal\SDK\Exceptions\RateLimitException;

class RateLimiter
{
    private AdapterInterface $cache;
    private int $maxRequests;
    private int $windowSeconds;
    private string $prefix;

    public function __construct(
        int $maxRequests = 60,
        int $windowSeconds = 60,
        ?AdapterInterface $cache = null,
        string $prefix = 'rate_limit_'
    ) {
        $this->maxRequests = $maxRequests;
        $this->windowSeconds = $windowSeconds;
        $this->cache = $cache ?? new FilesystemAdapter(
            'rate_limiter',
            0,
            sys_get_temp_dir() . '/xgate_rate_limit'
        );
        $this->prefix = $prefix;
    }

    /**
     * Check if request is allowed
     *
     * @param string $key Identifier for rate limiting (e.g., API key, IP, user ID)
     * @return bool
     * @throws RateLimitException
     */
    public function allow(string $key): bool
    {
        $cacheKey = $this->prefix . md5($key);
        $item = $this->cache->getItem($cacheKey);
        
        $data = $item->isHit() ? $item->get() : $this->initializeData();
        
        // Clean old requests outside the window
        $data = $this->cleanOldRequests($data);
        
        if (count($data['requests']) >= $this->maxRequests) {
            $oldestRequest = min($data['requests']);
            $waitTime = $this->windowSeconds - (time() - $oldestRequest);
            
            $exception = new RateLimitException(
                'Rate limit exceeded',
                $waitTime
            );
            
            $exception->setLimit($this->maxRequests)
                      ->setRemaining(0)
                      ->setResetAt(time() + $waitTime);
            
            throw $exception;
        }
        
        // Add current request
        $data['requests'][] = time();
        $data['count'] = count($data['requests']);
        
        $item->set($data);
        $item->expiresAfter($this->windowSeconds);
        $this->cache->save($item);
        
        return true;
    }

    /**
     * Get remaining requests
     *
     * @param string $key
     * @return int
     */
    public function getRemaining(string $key): int
    {
        $cacheKey = $this->prefix . md5($key);
        $item = $this->cache->getItem($cacheKey);
        
        if (!$item->isHit()) {
            return $this->maxRequests;
        }
        
        $data = $this->cleanOldRequests($item->get());
        
        return max(0, $this->maxRequests - count($data['requests']));
    }

    /**
     * Get reset time
     *
     * @param string $key
     * @return int Unix timestamp
     */
    public function getResetTime(string $key): int
    {
        $cacheKey = $this->prefix . md5($key);
        $item = $this->cache->getItem($cacheKey);
        
        if (!$item->isHit()) {
            return time() + $this->windowSeconds;
        }
        
        $data = $item->get();
        
        if (empty($data['requests'])) {
            return time() + $this->windowSeconds;
        }
        
        $oldestRequest = min($data['requests']);
        
        return $oldestRequest + $this->windowSeconds;
    }

    /**
     * Reset rate limit for a key
     *
     * @param string $key
     * @return void
     */
    public function reset(string $key): void
    {
        $cacheKey = $this->prefix . md5($key);
        $this->cache->deleteItem($cacheKey);
    }

    /**
     * Initialize data structure
     *
     * @return array
     */
    private function initializeData(): array
    {
        return [
            'requests' => [],
            'count' => 0,
            'window_start' => time()
        ];
    }

    /**
     * Clean requests outside the time window
     *
     * @param array $data
     * @return array
     */
    private function cleanOldRequests(array $data): array
    {
        $cutoff = time() - $this->windowSeconds;
        
        $data['requests'] = array_values(
            array_filter(
                $data['requests'],
                fn($timestamp) => $timestamp > $cutoff
            )
        );
        
        $data['count'] = count($data['requests']);
        
        return $data;
    }

    /**
     * Set max requests
     *
     * @param int $maxRequests
     * @return self
     */
    public function setMaxRequests(int $maxRequests): self
    {
        $this->maxRequests = $maxRequests;
        return $this;
    }

    /**
     * Set window seconds
     *
     * @param int $windowSeconds
     * @return self
     */
    public function setWindowSeconds(int $windowSeconds): self
    {
        $this->windowSeconds = $windowSeconds;
        return $this;
    }

    /**
     * Get rate limit info
     *
     * @param string $key
     * @return array
     */
    public function getInfo(string $key): array
    {
        return [
            'limit' => $this->maxRequests,
            'remaining' => $this->getRemaining($key),
            'reset_at' => $this->getResetTime($key),
            'window' => $this->windowSeconds
        ];
    }

    /**
     * Create a sliding window rate limiter
     *
     * @param string $key
     * @param int $maxRequests
     * @param int $windowSeconds
     * @return bool
     */
    public function slidingWindow(string $key, int $maxRequests, int $windowSeconds): bool
    {
        $cacheKey = $this->prefix . 'sliding_' . md5($key);
        $item = $this->cache->getItem($cacheKey);
        
        $now = microtime(true);
        $window = $windowSeconds * 1000000; // Convert to microseconds
        
        $data = $item->isHit() ? $item->get() : [];
        
        // Remove old entries
        $data = array_filter(
            $data,
            fn($timestamp) => ($now - $timestamp) < $window
        );
        
        if (count($data) >= $maxRequests) {
            return false;
        }
        
        $data[] = $now;
        
        $item->set($data);
        $item->expiresAfter($windowSeconds);
        $this->cache->save($item);
        
        return true;
    }

    /**
     * Apply rate limit with callback
     *
     * @param string $key
     * @param callable $callback
     * @return mixed
     * @throws RateLimitException
     */
    public function throttle(string $key, callable $callback): mixed
    {
        $this->allow($key);
        
        return $callback();
    }
}