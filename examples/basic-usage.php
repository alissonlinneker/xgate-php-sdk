<?php

require_once __DIR__ . '/../vendor/autoload.php';

use XGateGlobal\SDK\Client;
use XGateGlobal\SDK\Configuration;
use BcMath\Number;

// Load environment variables if using .env file
if (file_exists(__DIR__ . '/../.env')) {
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
    $dotenv->load();
}

// Create configuration
$config = new Configuration([
    'email' => $_ENV['XGATE_EMAIL'] ?? 'your-email@example.com',
    'password' => $_ENV['XGATE_PASSWORD'] ?? 'your-password',
    'base_url' => $_ENV['XGATE_BASE_URL'] ?? 'https://api.xgateglobal.com',
    'debug' => ($_ENV['XGATE_DEBUG'] ?? 'false') === 'true'
]);

// Initialize client
$client = new Client($config);

try {
    echo "XGate SDK - Exemplo básico\n";
    echo "==========================\n\n";

    // Autenticação
    echo "Fazendo login...\n";
    $token = $client->auth->login();
    echo "Login realizado com sucesso!\n\n";

    // Listar moedas
    echo "Moedas disponíveis:\n";
    $currencies = $client->deposits->getCurrencies();

    foreach (array_slice($currencies, 0, 5) as $currency) {
        $type = $currency->isCrypto ? '[Crypto]' : '[Fiat]';
        echo "  {$type} {$currency->symbol} - {$currency->name}\n";
    }
    echo "\n";

    // Criar depósito
    $customerId = 'customer_' . uniqid();
    $amount = new Number('100.50');

    echo "Criando depósito de $100.50 USD...\n";
    $transaction = $client->deposits->create(
        $amount,
        $customerId,
        'USD',
        ['order_id' => 'ORDER-' . uniqid()]
    );

    echo "Depósito criado!\n";
    echo "  ID: {$transaction->id}\n";
    echo "  Valor: \${$transaction->amount}\n";
    echo "  Status: {$transaction->status}\n\n";

    // Verificar status
    $updatedTransaction = $client->deposits->get($transaction->id);

    if ($updatedTransaction->isPending()) {
        echo "Transação aguardando processamento...\n";
    } elseif ($updatedTransaction->isCompleted()) {
        echo "Transação concluída!\n";
    }

    // Calcular taxas
    echo "\nTaxas para depósito de $1000:\n";
    $fees = $client->deposits->calculateFees(new Number('1000.00'), 'USD');
    echo "  Valor: \${$fees['amount']}\n";
    echo "  Taxa: \${$fees['fee']}\n";
    echo "  Total: \${$fees['total']}\n\n";

    // Listar transações
    $deposits = $client->deposits->list(['customerId' => $customerId]);

    if (!empty($deposits['items'])) {
        echo "Transações do cliente:\n";
        foreach ($deposits['items'] as $deposit) {
            echo "  - {$deposit->id}: \${$deposit->amount} ({$deposit->status})\n";
        }
    }

    echo "\nFim do exemplo!\n";

} catch (\XGateGlobal\SDK\Exceptions\AuthenticationException $e) {
    echo "❌ Authentication error: " . $e->getMessage() . "\n";
    echo "Please check your credentials in the .env file\n";
} catch (\XGateGlobal\SDK\Exceptions\ValidationException $e) {
    echo "❌ Validation error: " . $e->getMessage() . "\n";
    if ($e->getValidationErrors()) {
        foreach ($e->getValidationErrors() as $field => $error) {
            echo "   - {$field}: {$error}\n";
        }
    }
} catch (\XGateGlobal\SDK\Exceptions\ApiException $e) {
    echo "❌ API error: " . $e->getMessage() . "\n";
    if ($e->getErrorCode()) {
        echo "   Error code: " . $e->getErrorCode() . "\n";
    }
} catch (\Exception $e) {
    echo "❌ Unexpected error: " . $e->getMessage() . "\n";
    echo "   File: " . $e->getFile() . ":" . $e->getLine() . "\n";
}