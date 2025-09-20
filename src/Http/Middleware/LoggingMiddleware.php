<?php

declare(strict_types=1);

namespace XGateGlobal\SDK\Http\Middleware;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;

class LoggingMiddleware
{
    public function __construct(
        private LoggerInterface $logger,
        private string $logLevel = LogLevel::DEBUG
    ) {}

    public function __invoke(callable $handler): callable
    {
        return function (RequestInterface $request, array $options) use ($handler) {
            $requestId = uniqid('req_');
            $startTime = microtime(true);

            // Log request
            $this->logRequest($request, $requestId);

            return $handler($request, $options)->then(
                function (ResponseInterface $response) use ($request, $requestId, $startTime) {
                    $duration = round((microtime(true) - $startTime) * 1000, 2);
                    
                    // Log response
                    $this->logResponse($response, $requestId, $duration);
                    
                    return $response;
                },
                function ($reason) use ($request, $requestId, $startTime) {
                    $duration = round((microtime(true) - $startTime) * 1000, 2);
                    
                    // Log error
                    $this->logError($reason, $requestId, $duration);
                    
                    throw $reason;
                }
            );
        };
    }

    private function logRequest(RequestInterface $request, string $requestId): void
    {
        $context = [
            'request_id' => $requestId,
            'method' => $request->getMethod(),
            'uri' => (string) $request->getUri(),
            'headers' => $this->sanitizeHeaders($request->getHeaders()),
        ];

        $body = (string) $request->getBody();
        if ($body) {
            $context['body'] = $this->sanitizeBody($body);
        }

        $this->logger->log($this->logLevel, 'HTTP Request', $context);
    }

    private function logResponse(ResponseInterface $response, string $requestId, float $duration): void
    {
        $context = [
            'request_id' => $requestId,
            'status_code' => $response->getStatusCode(),
            'duration_ms' => $duration,
            'headers' => $this->sanitizeHeaders($response->getHeaders()),
        ];

        $body = (string) $response->getBody();
        $response->getBody()->rewind(); // Rewind the body stream
        
        if ($body && strlen($body) < 10000) { // Only log small response bodies
            $context['body'] = $this->sanitizeBody($body);
        } else {
            $context['body_size'] = strlen($body);
        }

        $level = $response->getStatusCode() >= 400 ? LogLevel::ERROR : $this->logLevel;
        $this->logger->log($level, 'HTTP Response', $context);
    }

    private function logError($reason, string $requestId, float $duration): void
    {
        $context = [
            'request_id' => $requestId,
            'duration_ms' => $duration,
            'error' => $reason instanceof \Exception ? $reason->getMessage() : (string) $reason,
        ];

        if ($reason instanceof \Exception) {
            $context['exception'] = get_class($reason);
            $context['trace'] = $reason->getTraceAsString();
        }

        $this->logger->error('HTTP Request Failed', $context);
    }

    private function sanitizeHeaders(array $headers): array
    {
        $sanitized = [];
        $sensitiveHeaders = ['authorization', 'api-key', 'x-api-key', 'cookie', 'set-cookie'];

        foreach ($headers as $name => $values) {
            $lowerName = strtolower($name);
            if (in_array($lowerName, $sensitiveHeaders, true)) {
                $sanitized[$name] = ['[REDACTED]'];
            } else {
                $sanitized[$name] = $values;
            }
        }

        return $sanitized;
    }

    private function sanitizeBody(string $body): mixed
    {
        $decoded = json_decode($body, true);
        
        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            return $this->sanitizeData($decoded);
        }
        
        return substr($body, 0, 1000);
    }

    private function sanitizeData(array $data): array
    {
        $sensitiveKeys = ['password', 'secret', 'token', 'api_key', 'private_key', 'card_number', 'cvv'];
        
        foreach ($data as $key => &$value) {
            if (is_array($value)) {
                $value = $this->sanitizeData($value);
            } elseif (is_string($key)) {
                foreach ($sensitiveKeys as $sensitiveKey) {
                    if (stripos($key, $sensitiveKey) !== false) {
                        $value = '[REDACTED]';
                        break;
                    }
                }
            }
        }
        
        return $data;
    }
}