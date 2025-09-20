<?php

declare(strict_types=1);

namespace XGateGlobal\SDK\Exceptions;

use Exception;

class XGateException extends Exception
{
    protected array $context = [];
    protected ?string $errorCode = null;
    protected ?array $errors = null;

    public function __construct(
        string $message = '',
        int $code = 0,
        ?Exception $previous = null,
        array $context = []
    ) {
        parent::__construct($message, $code, $previous);
        $this->context = $context;
    }

    public function setErrorCode(string $errorCode): self
    {
        $this->errorCode = $errorCode;
        return $this;
    }

    public function getErrorCode(): ?string
    {
        return $this->errorCode;
    }

    public function setErrors(array $errors): self
    {
        $this->errors = $errors;
        return $this;
    }

    public function getErrors(): ?array
    {
        return $this->errors;
    }

    public function getContext(): array
    {
        return $this->context;
    }

    public function setContext(array $context): self
    {
        $this->context = $context;
        return $this;
    }

    public function addContext(string $key, mixed $value): self
    {
        $this->context[$key] = $value;
        return $this;
    }

    public function toArray(): array
    {
        return [
            'message' => $this->getMessage(),
            'code' => $this->getCode(),
            'error_code' => $this->errorCode,
            'errors' => $this->errors,
            'context' => $this->context,
        ];
    }
}