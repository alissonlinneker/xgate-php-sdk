<?php

declare(strict_types=1);

namespace XGateGlobal\SDK\Http\Client;

use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;
use XGateGlobal\SDK\Configuration;
use XGateGlobal\SDK\Exceptions\{
    ApiException,
    AuthenticationException,
    NetworkException,
    RateLimitException,
    ValidationException
};
use XGateGlobal\SDK\Http\Middleware\{
    AuthenticationMiddleware,
    LoggingMiddleware,
    RetryMiddleware
};
use XGateGlobal\SDK\Http\Response\{ApiResponse, PaginatedResponse};

class HttpClient
{
    private ClientInterface $client;
    private Configuration $config;
    private LoggerInterface $logger;
    private ?string $accessToken = null;
    private array $defaultHeaders;

    public function __construct(
        Configuration $config,
        LoggerInterface $logger
    ) {
        $this->config = $config;
        $this->logger = $logger;
        $this->defaultHeaders = $this->buildDefaultHeaders();
        $this->client = $this->createClient();
    }

    private function createClient(): ClientInterface
    {
        if ($this->config->httpClient) {
            return $this->config->httpClient;
        }

        $stack = HandlerStack::create();

        // Add retry middleware
        $retryMiddleware = new RetryMiddleware($this->config->retryAttempts);
        $stack->push($retryMiddleware(...), 'retry');

        // Add logging middleware
        if ($this->config->debug) {
            $loggingMiddleware = new LoggingMiddleware($this->logger);
            $stack->push($loggingMiddleware(...), 'logging');
        }

        // Add authentication middleware
        $stack->push(Middleware::mapRequest(function (RequestInterface $request) {
            if ($this->accessToken) {
                return $request->withHeader('Authorization', 'Bearer ' . $this->accessToken);
            }
            return $request;
        }), 'auth');

        return new Client([
            'base_uri' => $this->config->baseUrl,
            'timeout' => $this->config->timeout,
            'verify' => $this->config->verifySsl,
            'handler' => $stack,
            'headers' => $this->defaultHeaders,
        ]);
    }

    private function buildDefaultHeaders(): array
    {
        $headers = [
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
            'User-Agent' => $this->config->userAgent ?? 'XGateGlobal-PHP-SDK/1.0.0',
        ];

        return array_merge($headers, $this->config->getCustomHeaders());
    }

    public function setAccessToken(string $token): void
    {
        $this->accessToken = $token;
    }

    public function getConfiguration(): Configuration
    {
        return $this->config;
    }

    public function get(string $path, array $query = []): ApiResponse
    {
        return $this->request('GET', $path, ['query' => $query]);
    }

    public function post(string $path, array $data = []): ApiResponse
    {
        return $this->request('POST', $path, ['json' => $data]);
    }

    public function put(string $path, array $data = []): ApiResponse
    {
        return $this->request('PUT', $path, ['json' => $data]);
    }

    public function patch(string $path, array $data = []): ApiResponse
    {
        return $this->request('PATCH', $path, ['json' => $data]);
    }

    public function delete(string $path): ApiResponse
    {
        return $this->request('DELETE', $path);
    }

    private function request(string $method, string $path, array $options = []): ApiResponse
    {
        $this->logger->debug('API Request', [
            'method' => $method,
            'path' => $path,
            'options' => $options,
        ]);

        try {
            $response = $this->client->request($method, $path, $options);

            $apiResponse = $this->handleResponse($response);

            $this->logger->debug('API Response', [
                'status' => $apiResponse->getStatusCode(),
                'data' => $apiResponse->getData(),
            ]);

            return $apiResponse;

        } catch (\GuzzleHttp\Exception\ClientException $e) {
            $response = $e->getResponse();
            $statusCode = $response ? $response->getStatusCode() : 0;
            $body = $response ? (string) $response->getBody() : '';

            $this->handleErrorResponse($statusCode, $body, $path);

        } catch (\GuzzleHttp\Exception\ServerException $e) {
            $response = $e->getResponse();
            $statusCode = $response ? $response->getStatusCode() : 500;
            $body = $response ? (string) $response->getBody() : '';

            throw new ApiException(
                'Server error: ' . $e->getMessage(),
                $statusCode,
                $e
            );

        } catch (\GuzzleHttp\Exception\RequestException $e) {
            throw new NetworkException(
                'Network error: ' . $e->getMessage(),
                0,
                $e
            );
        }
    }

    private function handleResponse(ResponseInterface $response): ApiResponse
    {
        $body = (string) $response->getBody();
        $data = json_decode($body, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new ApiException('Invalid JSON response: ' . json_last_error_msg());
        }

        $headers = [];
        foreach ($response->getHeaders() as $name => $values) {
            $headers[$name] = implode(', ', $values);
        }

        // Check if it's a paginated response
        if (isset($data['meta']) && (isset($data['meta']['total_pages']) || isset($data['meta']['has_more']))) {
            return new PaginatedResponse($data, $response->getStatusCode(), $headers);
        }

        return new ApiResponse($data, $response->getStatusCode(), $headers);
    }

    private function handleErrorResponse(int $statusCode, string $body, string $path): void
    {
        $data = json_decode($body, true) ?? [];
        $message = $data['message'] ?? $data['error'] ?? 'Unknown error';

        switch ($statusCode) {
            case 401:
                throw new AuthenticationException($message, $statusCode);

            case 422:
                $errors = $data['errors'] ?? [];
                throw new ValidationException($message, $errors, $statusCode);

            case 429:
                $retryAfter = isset($data['retry_after']) ? (int) $data['retry_after'] : null;
                $exception = new RateLimitException($message, $retryAfter, $statusCode);

                if (isset($data['limit'])) {
                    $exception->setLimit((int) $data['limit']);
                }
                if (isset($data['remaining'])) {
                    $exception->setRemaining((int) $data['remaining']);
                }
                if (isset($data['reset_at'])) {
                    $exception->setResetAt((int) $data['reset_at']);
                }

                throw $exception;

            case 400:
            case 403:
            case 404:
            case 405:
            case 409:
                $exception = new ApiException($message, $statusCode);
                if (isset($data['error_code'])) {
                    $exception->setErrorCode($data['error_code']);
                }
                if (isset($data['errors'])) {
                    $exception->setErrors($data['errors']);
                }
                throw $exception;

            default:
                throw new ApiException($message, $statusCode);
        }
    }

    public function paginate(
        string $path,
        array $query = [],
        int $page = 1,
        int $perPage = 20
    ): PaginatedResponse {
        $query['page'] = $page;
        $query['per_page'] = $perPage;

        $response = $this->get($path, $query);

        if ($response instanceof PaginatedResponse) {
            return $response;
        }

        // Convert regular response to paginated if needed
        return new PaginatedResponse(
            $response->getData(),
            $response->getStatusCode(),
            $response->getHeaders()
        );
    }
}