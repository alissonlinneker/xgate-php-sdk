<?php

declare(strict_types=1);

namespace XGateGlobal\SDK\Exceptions;

class AuthenticationException extends XGateException
{
    public function __construct(
        string $message = 'Authentication failed',
        int $code = 401,
        ?\Exception $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }
}