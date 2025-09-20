<?php

declare(strict_types=1);

namespace XGateGlobal\SDK\Exceptions;

class NetworkException extends XGateException
{
    private ?string $url = null;
    private ?int $statusCode = null;
    private ?string $responseBody = null;

    public function __construct(
        string $message = 'Network error occurred',
        int $code = 0,
        ?\Exception $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }

    public function getUrl(): ?string
    {
        return $this->url;
    }

    public function setUrl(string $url): self
    {
        $this->url = $url;
        return $this;
    }

    public function getStatusCode(): ?int
    {
        return $this->statusCode;
    }

    public function setStatusCode(int $statusCode): self
    {
        $this->statusCode = $statusCode;
        return $this;
    }

    public function getResponseBody(): ?string
    {
        return $this->responseBody;
    }

    public function setResponseBody(string $body): self
    {
        $this->responseBody = $body;
        return $this;
    }
}