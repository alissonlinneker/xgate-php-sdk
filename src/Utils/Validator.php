<?php

declare(strict_types=1);

namespace XGateGlobal\SDK\Utils;

use XGateGlobal\SDK\Exceptions\ValidationException;

class Validator
{
    /**
     * Validate email address
     *
     * @param string $email
     * @return bool
     * @throws ValidationException
     */
    public static function validateEmail(string $email): bool
    {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new ValidationException('Invalid email format: ' . $email);
        }
        return true;
    }

    /**
     * Validate URL
     *
     * @param string $url
     * @return bool
     * @throws ValidationException
     */
    public static function validateUrl(string $url): bool
    {
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            throw new ValidationException('Invalid URL format: ' . $url);
        }
        return true;
    }

    /**
     * Validate CPF (Brazilian document)
     *
     * @param string $cpf
     * @return bool
     * @throws ValidationException
     */
    public static function validateCPF(string $cpf): bool
    {
        $cpf = preg_replace('/[^0-9]/', '', $cpf);
        
        if (strlen($cpf) !== 11) {
            throw new ValidationException('CPF must have 11 digits');
        }

        // Check for known invalid CPFs
        if (preg_match('/^(\d)\1+$/', $cpf)) {
            throw new ValidationException('Invalid CPF');
        }

        // Validate check digits
        for ($t = 9; $t < 11; $t++) {
            $d = 0;
            for ($c = 0; $c < $t; $c++) {
                $d += $cpf[$c] * (($t + 1) - $c);
            }
            $d = ((10 * $d) % 11) % 10;
            if ($cpf[$t] != $d) {
                throw new ValidationException('Invalid CPF');
            }
        }

        return true;
    }

    /**
     * Validate CNPJ (Brazilian company document)
     *
     * @param string $cnpj
     * @return bool
     * @throws ValidationException
     */
    public static function validateCNPJ(string $cnpj): bool
    {
        $cnpj = preg_replace('/[^0-9]/', '', $cnpj);
        
        if (strlen($cnpj) !== 14) {
            throw new ValidationException('CNPJ must have 14 digits');
        }

        // Check for known invalid CNPJs
        if (preg_match('/^(\d)\1+$/', $cnpj)) {
            throw new ValidationException('Invalid CNPJ');
        }

        // Validate check digits
        $length = strlen($cnpj) - 2;
        $numbers = substr($cnpj, 0, $length);
        $digits = substr($cnpj, $length);
        
        $sum = 0;
        $pos = $length - 7;
        
        for ($i = $length; $i >= 1; $i--) {
            $sum += $numbers[$length - $i] * $pos--;
            if ($pos < 2) {
                $pos = 9;
            }
        }
        
        $result = $sum % 11 < 2 ? 0 : 11 - $sum % 11;
        
        if ($result != $digits[0]) {
            throw new ValidationException('Invalid CNPJ');
        }
        
        $length = $length + 1;
        $numbers = substr($cnpj, 0, $length);
        $sum = 0;
        $pos = $length - 7;
        
        for ($i = $length; $i >= 1; $i--) {
            $sum += $numbers[$length - $i] * $pos--;
            if ($pos < 2) {
                $pos = 9;
            }
        }
        
        $result = $sum % 11 < 2 ? 0 : 11 - $sum % 11;
        
        if ($result != $digits[1]) {
            throw new ValidationException('Invalid CNPJ');
        }

        return true;
    }

    /**
     * Validate phone number
     *
     * @param string $phone
     * @param string $country
     * @return bool
     * @throws ValidationException
     */
    public static function validatePhone(string $phone, string $country = 'BR'): bool
    {
        $phone = preg_replace('/[^0-9+]/', '', $phone);
        
        switch ($country) {
            case 'BR':
                // Brazilian phone format: +55 11 99999-9999
                if (!preg_match('/^\+?55?\d{10,11}$/', $phone)) {
                    throw new ValidationException('Invalid Brazilian phone format');
                }
                break;
            
            default:
                // Basic international format
                if (!preg_match('/^\+?\d{7,15}$/', $phone)) {
                    throw new ValidationException('Invalid phone format');
                }
        }

        return true;
    }

    /**
     * Validate cryptocurrency address
     *
     * @param string $address
     * @param string $network
     * @return bool
     * @throws ValidationException
     */
    public static function validateCryptoAddress(string $address, string $network): bool
    {
        switch (strtoupper($network)) {
            case 'BTC':
            case 'BITCOIN':
                // Bitcoin address formats
                if (!preg_match('/^(1|3|bc1)[a-zA-HJ-NP-Z0-9]{25,62}$/', $address)) {
                    throw new ValidationException('Invalid Bitcoin address');
                }
                break;
            
            case 'ETH':
            case 'ETHEREUM':
            case 'BSC':
            case 'POLYGON':
                // Ethereum-like address format
                if (!preg_match('/^0x[a-fA-F0-9]{40}$/', $address)) {
                    throw new ValidationException('Invalid Ethereum address');
                }
                break;
            
            case 'TRX':
            case 'TRON':
                // Tron address format
                if (!preg_match('/^T[a-zA-Z0-9]{33}$/', $address)) {
                    throw new ValidationException('Invalid Tron address');
                }
                break;
            
            default:
                // Basic validation for unknown networks
                if (strlen($address) < 20 || strlen($address) > 100) {
                    throw new ValidationException('Invalid address length');
                }
        }

        return true;
    }

    /**
     * Validate amount
     *
     * @param string|float $amount
     * @param float $min
     * @param float|null $max
     * @return bool
     * @throws ValidationException
     */
    public static function validateAmount(string|float $amount, float $min = 0, ?float $max = null): bool
    {
        $numericAmount = is_string($amount) ? (float) $amount : $amount;
        
        if (!is_numeric($amount) || $numericAmount < 0) {
            throw new ValidationException('Amount must be a positive number');
        }
        
        if ($numericAmount < $min) {
            throw new ValidationException("Amount must be at least $min");
        }
        
        if ($max !== null && $numericAmount > $max) {
            throw new ValidationException("Amount must not exceed $max");
        }

        return true;
    }

    /**
     * Validate UUID
     *
     * @param string $uuid
     * @return bool
     * @throws ValidationException
     */
    public static function validateUUID(string $uuid): bool
    {
        if (!preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i', $uuid)) {
            throw new ValidationException('Invalid UUID format');
        }
        return true;
    }

    /**
     * Validate array using PHP 8.4 array_all
     *
     * @param array $array
     * @param callable $callback
     * @return bool
     */
    public static function validateArray(array $array, callable $callback): bool
    {
        // Using PHP 8.4 array_all function
        return array_all($array, $callback);
    }

    /**
     * Find first invalid item using PHP 8.4 array_find
     *
     * @param array $array
     * @param callable $callback
     * @return mixed
     */
    public static function findInvalid(array $array, callable $callback): mixed
    {
        // Using PHP 8.4 array_find function
        return array_find($array, fn($item) => !$callback($item));
    }
}