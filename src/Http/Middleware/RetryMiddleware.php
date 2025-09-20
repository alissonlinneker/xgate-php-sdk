<?php

declare(strict_types=1);

namespace XGateGlobal\SDK\Http\Middleware;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use XGateGlobal\SDK\Exceptions\NetworkException;

class RetryMiddleware
{
    private const MAX_RETRIES = 3;
    private const BASE_DELAY_MS = 1000;
    private const RETRYABLE_STATUS_CODES = [408, 429, 500, 502, 503, 504];

    public function __construct(
        private int $maxRetries = self::MAX_RETRIES
    ) {}

    public function __invoke(callable $handler): callable
    {
        return function (RequestInterface $request, array $options) use ($handler) {
            $attempt = 0;
            $lastException = null;
            $lastResponse = null;

            while ($attempt <= $this->maxRetries) {
                try {
                    $promise = $handler($request, $options);
                    
                    return $promise->then(
                        function (ResponseInterface $response) use (&$attempt, &$lastResponse) {
                            $lastResponse = $response;
                            
                            if (!$this->shouldRetry($response, $attempt)) {
                                return $response;
                            }
                            
                            throw new \Exception('Retryable error');
                        }
                    )->wait();
                    
                } catch (\Exception $e) {
                    $lastException = $e;
                    
                    if ($attempt === $this->maxRetries) {
                        if ($lastException instanceof NetworkException) {
                            throw $lastException;
                        }
                        
                        throw new NetworkException(
                            'Max retries exceeded: ' . $lastException->getMessage(),
                            0,
                            $lastException
                        );
                    }
                }

                $this->sleep($attempt);
                $attempt++;
            }

            if ($lastResponse) {
                return $lastResponse;
            }

            throw $lastException ?? new NetworkException('Max retries exceeded');
        };
    }

    private function shouldRetry(ResponseInterface $response, int $attempt): bool
    {
        if ($attempt >= $this->maxRetries) {
            return false;
        }

        return in_array(
            $response->getStatusCode(),
            self::RETRYABLE_STATUS_CODES,
            true
        );
    }

    private function sleep(int $attempt): void
    {
        $delay = self::BASE_DELAY_MS * (2 ** $attempt);
        $jitter = random_int(0, 100);

        usleep(($delay + $jitter) * 1000);
    }
}