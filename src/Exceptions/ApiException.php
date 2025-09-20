<?php

declare(strict_types=1);

namespace XGateGlobal\SDK\Exceptions;

class ApiException extends XGateException
{
    private ?string $requestId = null;
    private ?string $type = null;

    public function __construct(
        string $message = 'API error occurred',
        int $code = 500,
        ?\Exception $previous = null,
        array $context = []
    ) {
        parent::__construct($message, $code, $previous, $context);
    }

    public function getRequestId(): ?string
    {
        return $this->requestId;
    }

    public function setRequestId(string $requestId): self
    {
        $this->requestId = $requestId;
        return $this;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(string $type): self
    {
        $this->type = $type;
        return $this;
    }
}