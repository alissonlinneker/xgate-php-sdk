<?php

declare(strict_types=1);

namespace XGateGlobal\SDK\Exceptions;

class RateLimitException extends XGateException
{
    private ?int $retryAfter = null;
    private ?int $limit = null;
    private ?int $remaining = null;
    private ?int $resetAt = null;

    public function __construct(
        string $message = 'Rate limit exceeded',
        int $retryAfter = null,
        int $code = 429,
        ?\Exception $previous = null
    ) {
        parent::__construct($message, $code, $previous);
        $this->retryAfter = $retryAfter;
    }

    public function getRetryAfter(): ?int
    {
        return $this->retryAfter;
    }

    public function setRetryAfter(int $seconds): self
    {
        $this->retryAfter = $seconds;
        return $this;
    }

    public function getLimit(): ?int
    {
        return $this->limit;
    }

    public function setLimit(int $limit): self
    {
        $this->limit = $limit;
        return $this;
    }

    public function getRemaining(): ?int
    {
        return $this->remaining;
    }

    public function setRemaining(int $remaining): self
    {
        $this->remaining = $remaining;
        return $this;
    }

    public function getResetAt(): ?int
    {
        return $this->resetAt;
    }

    public function setResetAt(int $timestamp): self
    {
        $this->resetAt = $timestamp;
        return $this;
    }
}