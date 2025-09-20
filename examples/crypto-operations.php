<?php

require_once __DIR__ . '/../vendor/autoload.php';

use XGateGlobal\SDK\Client;
use XGateGlobal\SDK\Configuration;
use BcMath\Number;
use XGateGlobal\SDK\Utils\MoneyFormatter;

// Load environment variables
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

// Initialize client and money formatter
$client = new Client($config);
$formatter = new MoneyFormatter('en_US', 'USD');

try {
    echo "=== XGate Global SDK - Cryptocurrency Operations ===\n\n";

    // Authenticate
    $client->auth->login();
    echo "✓ Authenticated successfully\n\n";

    // 1. Get blockchain networks
    echo "1. Available Blockchain Networks:\n";
    $networks = $client->withdrawals->getBlockchainNetworks();

    foreach (array_slice($networks, 0, 5) as $network) {
        echo "   - {$network->name} ({$network->symbol}):\n";
        echo "     • Network: {$network->network}\n";
        if ($network->minWithdrawal) {
            echo "     • Min withdrawal: {$network->minWithdrawal}\n";
        }
        if ($network->withdrawalFee) {
            echo "     • Fee: {$network->withdrawalFee}\n";
        }
        if ($network->confirmations) {
            echo "     • Confirmations: {$network->confirmations}\n";
        }
        echo "\n";
    }

    // 2. Get crypto prices
    echo "2. Current Cryptocurrency Prices:\n";
    $symbols = ['BTC', 'ETH', 'USDT', 'BNB'];
    $prices = $client->crypto->getPrices($symbols, 'USD');

    foreach ($prices as $symbol => $price) {
        echo "   - {$symbol}: " . $formatter->format($price, 'USD') . "\n";
    }
    echo "\n";

    // 3. Get customer wallet
    $customerId = 'customer_' . uniqid();
    echo "3. Customer Crypto Wallet:\n";

    try {
        $wallet = $client->crypto->getWallet($customerId);
        echo "   - Address: {$wallet->address}\n";
        echo "   - Network: {$wallet->network}\n";
        echo "   - Balance: " . $formatter->formatCrypto($wallet->balance, 'USDT') . "\n";

        if ($wallet->hasBalance()) {
            echo "   - Wallet has balance\n";
        }
    } catch (\Exception $e) {
        echo "   - No wallet found for customer (this is normal for new customers)\n";
    }
    echo "\n";

    // 4. Get deposit address
    echo "4. Generate Crypto Deposit Address:\n";
    try {
        $depositInfo = $client->crypto->getDepositAddress(
            $customerId,
            'USDT',
            'BSC'
        );

        echo "   - Address: " . ($depositInfo['address'] ?? 'N/A') . "\n";
        echo "   - Network: " . ($depositInfo['network'] ?? 'BSC') . "\n";
        echo "   - Currency: " . ($depositInfo['currency'] ?? 'USDT') . "\n";

        if (isset($depositInfo['qr_code'])) {
            echo "   - QR Code available\n";
        }
    } catch (\Exception $e) {
        echo "   - Could not generate deposit address: " . $e->getMessage() . "\n";
    }
    echo "\n";

    // 5. Convert crypto to fiat
    echo "5. Crypto to Fiat Conversion:\n";
    $btcAmount = new Number('0.5');
    $conversion = $client->crypto->convertToFiat($btcAmount, 'BTC', 'USD');

    echo "   - {$conversion['from_amount']} {$conversion['from_currency']} = ";
    echo $formatter->format($conversion['to_amount'], $conversion['to_currency']) . "\n";
    echo "   - Rate: 1 {$conversion['from_currency']} = ";
    echo $formatter->format($conversion['rate'], $conversion['to_currency']) . "\n\n";

    // 6. Calculate network fees
    echo "6. Network Fees for Ethereum:\n";
    try {
        $fees = $client->crypto->getNetworkFees('ETH');

        echo "   - Low: " . $formatter->format($fees['low'], 'USD');
        echo " (~{$fees['estimated_time']['low']} minutes)\n";

        echo "   - Medium: " . $formatter->format($fees['medium'], 'USD');
        echo " (~{$fees['estimated_time']['medium']} minutes)\n";

        echo "   - High: " . $formatter->format($fees['high'], 'USD');
        echo " (~{$fees['estimated_time']['high']} minutes)\n";
    } catch (\Exception $e) {
        echo "   - Could not fetch network fees: " . $e->getMessage() . "\n";
    }
    echo "\n";

    // 7. Validate crypto address
    echo "7. Address Validation:\n";
    $addresses = [
        ['0x742d35Cc6634C0532925a3b844Bc9e7595f0bEb4', 'ETH'],
        ['TN3W4H6rK2ce4vX9YnFQHwKENnHjoxb3m9', 'TRX'],
        ['1A1zP1eP5QGefi2DMPTfTL5SLmv7DivfNa', 'BTC'],
    ];

    foreach ($addresses as [$address, $network]) {
        $isValid = $client->crypto->validateAddress($address, $network);
        echo "   - {$network} address: " . ($isValid ? '✓ Valid' : '✗ Invalid') . "\n";
        echo "     {$address}\n";
    }
    echo "\n";

    // 8. Create crypto withdrawal (example - will fail without real wallet)
    echo "8. Create Crypto Withdrawal (Example):\n";
    try {
        $withdrawal = $client->crypto->withdraw(
            amount: new Number('100.00'),
            wallet: '0x742d35Cc6634C0532925a3b844Bc9e7595f0bEb4',
            customerId: $customerId,
            cryptocurrency: 'USDT',
            network: 'BSC',
            metadata: [
                'note' => 'Test withdrawal',
                'reference' => 'REF-' . uniqid()
            ]
        );

        echo "   ✓ Withdrawal created:\n";
        echo "   - Transaction ID: {$withdrawal->id}\n";
        echo "   - Amount: " . $formatter->formatCrypto($withdrawal->amount, 'USDT') . "\n";
        echo "   - Network: {$withdrawal->blockchainNetwork}\n";
        echo "   - Status: {$withdrawal->status}\n";

    } catch (\Exception $e) {
        echo "   - Withdrawal example failed (expected): " . $e->getMessage() . "\n";
    }
    echo "\n";

    // 9. Get all crypto balances
    echo "9. All Crypto Balances for Customer:\n";
    try {
        $balances = $client->crypto->getBalances($customerId);

        if (empty($balances)) {
            echo "   - No crypto balances found\n";
        } else {
            foreach ($balances as $currency => $balance) {
                if ($balance->compare('0') > 0) {
                    echo "   - {$currency}: " . $formatter->formatCrypto($balance, $currency) . "\n";
                }
            }
        }
    } catch (\Exception $e) {
        echo "   - Could not fetch balances: " . $e->getMessage() . "\n";
    }

    echo "\n=== Crypto operations example completed! ===\n";

} catch (\XGateGlobal\SDK\Exceptions\AuthenticationException $e) {
    echo "❌ Authentication error: " . $e->getMessage() . "\n";
} catch (\XGateGlobal\SDK\Exceptions\ApiException $e) {
    echo "❌ API error: " . $e->getMessage() . "\n";
} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}