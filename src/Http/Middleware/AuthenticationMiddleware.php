<?php

declare(strict_types=1);

namespace XGateGlobal\SDK\Http\Middleware;

use Psr\Http\Message\RequestInterface;
use XGateGlobal\SDK\Auth\TokenManager;

class AuthenticationMiddleware
{
    public function __construct(
        private TokenManager $tokenManager
    ) {}

    public function __invoke(callable $handler): callable
    {
        return function (RequestInterface $request, array $options) use ($handler) {
            // Skip authentication for auth endpoints
            $path = $request->getUri()->getPath();
            if (str_contains($path, '/auth/')) {
                return $handler($request, $options);
            }

            // Get token from token manager
            $token = $this->tokenManager->getToken();
            
            if ($token) {
                $request = $request->withHeader('Authorization', 'Bearer ' . $token);
            }

            return $handler($request, $options);
        };
    }
}