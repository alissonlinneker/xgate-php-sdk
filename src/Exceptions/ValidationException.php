<?php

declare(strict_types=1);

namespace XGateGlobal\SDK\Exceptions;

class ValidationException extends XGateException
{
    private array $validationErrors = [];

    public function __construct(
        string $message = 'Validation failed',
        array $errors = [],
        int $code = 422,
        ?\Exception $previous = null
    ) {
        $this->validationErrors = $errors;
        parent::__construct($message, $code, $previous);
        
        if (!empty($errors)) {
            $this->setErrors($errors);
        }
    }

    public function getValidationErrors(): array
    {
        return $this->validationErrors;
    }

    public function hasFieldError(string $field): bool
    {
        return isset($this->validationErrors[$field]);
    }

    public function getFieldError(string $field): ?string
    {
        return $this->validationErrors[$field] ?? null;
    }
}