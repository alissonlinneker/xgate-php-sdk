# XGate PHP SDK

[![PHP Version](https://img.shields.io/badge/PHP-%3E%3D8.4-8892BF.svg)](https://php.net)
[![License](https://img.shields.io/badge/license-Apache%202.0-blue.svg)](LICENSE)
[![Build Status](https://github.com/alissonlinneker/xgate-php-sdk/workflows/CI/badge.svg)](https://github.com/alissonlinneker/xgate-php-sdk/actions)

SDK PHP para integração com a API da XGate Global. Suporte completo para operações financeiras, criptomoedas e PIX.

## Pré-requisitos

- PHP 8.4+
- Composer
- Extensões: curl, json, mbstring, bcmath

## Instalação

```bash
composer require alissonlinneker/xgate-php-sdk
```

Ou adicione no seu `composer.json`:

```json
{
    "require": {
        "alissonlinneker/xgate-php-sdk": "^1.0"
    }
}
```

## Como usar

```php
use XGateGlobal\SDK\Client;
use XGateGlobal\SDK\Configuration;
use BcMath\Number;

// Configuração básica
$config = new Configuration([
    'email' => 'seu-email@exemplo.com',
    'password' => 'sua-senha'
]);

$client = new Client($config);

// Login (automático na primeira requisição)
$client->auth->login();

// Listar moedas disponíveis
$currencies = $client->deposits->getCurrencies();
foreach ($currencies as $currency) {
    echo "{$currency->symbol}: {$currency->name}\n";
}

// Criar depósito
$transaction = $client->deposits->create(
    new Number('100.50'),
    'customer_123',
    'USD'
);

printf("Transação criada: %s\n", $transaction->id);
```

## Exemplos práticos

### Operações com Crypto

```php
// Pegar carteira do cliente
$wallet = $client->crypto->getWallet('customer_123');

// Listar redes blockchain disponíveis
$networks = $client->withdrawals->getBlockchainNetworks();

// Saque em crypto
$withdrawal = $client->crypto->withdraw(
    amount: new Number('0.5'),
    wallet: '0x742d35Cc6634C0532925a3b844Bc9e7595f0bEb4',
    customerId: 'customer_123',
    cryptocurrency: 'USDT',
    network: 'BSC'
);

// Consultar preços
$prices = $client->crypto->getPrices(['BTC', 'ETH', 'USDT'], 'USD');
foreach ($prices as $symbol => $price) {
    echo "$symbol: $$price\n";
}

// Converter crypto para fiat
$conversion = $client->crypto->convertToFiat(
    new Number('0.5'),
    'BTC',
    'USD'
);
echo "0.5 BTC = ${$conversion['to_amount']} USD\n";
```

### Operações PIX

```php
// Consultar chaves PIX do cliente
$pixKeys = $client->pix->getKeys('customer_123');

// Cadastrar nova chave PIX
$key = $client->pix->registerKey(
    'customer_123',
    '12345678900',
    'cpf'
);

// Fazer saque via PIX
$withdrawal = $client->withdrawals->create(
    amount: new Number('500.00'),
    customerId: 'customer_123',
    currency: 'BRL',
    pixKey: '12345678900'
);

// Gerar QR Code PIX
$qrCode = $client->pix->generateQRCode(
    new Number('100.00'),
    'Pagamento pedido #123'
);

echo "QR Code: " . $qrCode['qrcode'] . "\n";
```

### Tratamento de erros

```php
use XGateGlobal\SDK\Exceptions\{
    AuthenticationException,
    ValidationException,
    RateLimitException,
    ApiException
};

try {
    $transaction = $client->deposits->create(
        new Number('100.00'),
        'customer_123',
        'USD'
    );
} catch (AuthenticationException $e) {
    error_log('Falha na autenticação: ' . $e->getMessage());
    // Tentar renovar token...
} catch (ValidationException $e) {
    echo "Dados inválidos: " . $e->getMessage() . "\n";

    if ($e->hasFieldError('amount')) {
        echo "Erro no campo amount: " . $e->getFieldError('amount') . "\n";
    }
} catch (RateLimitException $e) {
    $retry = $e->getRetryAfter();
    echo "Limite de requisições excedido. Aguarde $retry segundos.\n";
    sleep($retry);
    // Tentar novamente...
} catch (ApiException $e) {
    error_log(sprintf(
        "Erro na API: %s (Código: %s)",
        $e->getMessage(),
        $e->getErrorCode()
    ));
}
```

### Paginação

```php
// Listar depósitos com paginação
$result = $client->deposits->list(
    filters: ['status' => 'completed'],
    page: 1,
    perPage: 20
);

foreach ($result['items'] as $transaction) {
    echo "{$transaction->id}: R$ {$transaction->amount}\n";
}

// Próxima página
if ($result['pagination']['has_more']) {
    $nextPage = $client->deposits->list([], 2, 20);
}
```

### Logging

```php
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

$logger = new Logger('xgate');
$logger->pushHandler(new StreamHandler('path/to/xgate.log', Logger::DEBUG));

$client = new Client($config, $logger);
```

### Custom HTTP Client

```php
use GuzzleHttp\Client as GuzzleClient;

$httpClient = new GuzzleClient([
    'proxy' => 'tcp://localhost:8080',
    'verify' => false, // For development only
    'headers' => [
        'X-Custom-Header' => 'value'
    ]
]);

$config = new Configuration([
    'email' => 'your-email@example.com',
    'password' => 'your-password',
    'http_client' => $httpClient
]);

$client = new Client($config);
```

### Rate Limiting

```php
use XGateGlobal\SDK\Utils\RateLimiter;

// Create a rate limiter (60 requests per 60 seconds)
$rateLimiter = new RateLimiter(60, 60);

// Check if request is allowed
try {
    $rateLimiter->allow('api_key_123');

    // Make API request
    $transaction = $client->deposits->create(
        new Number('100.00'),
        'customer_123',
        'USD'
    );
} catch (RateLimitException $e) {
    echo "Rate limit exceeded. Wait {$e->getRetryAfter()} seconds\n";
}

// Get rate limit info
$info = $rateLimiter->getInfo('api_key_123');
echo "Remaining requests: {$info['remaining']}\n";
echo "Reset at: " . date('H:i:s', $info['reset_at']) . "\n";
```

### Money Formatting

```php
use XGateGlobal\SDK\Utils\MoneyFormatter;
use BcMath\Number;

$formatter = new MoneyFormatter('en_US', 'USD');

// Format currency
echo $formatter->format(new Number('1234.56')); // $1,234.56

// Format crypto
echo $formatter->formatCrypto(new Number('0.00000123'), 'BTC'); // 0.00000123 BTC

// Calculate fees
$result = $formatter->calculateFee(
    new Number('100.00'),
    new Number('2.5'), // 2.5%
    true // is percentage
);

echo "Amount: " . $formatter->format($result['amount']) . "\n";
echo "Fee: " . $formatter->format($result['fee']) . "\n";
echo "Total: " . $formatter->format($result['total']) . "\n";
```

### Validation

```php
use XGateGlobal\SDK\Utils\Validator;

// Validate CPF (Brazilian document)
try {
    Validator::validateCPF('123.456.789-00');
} catch (ValidationException $e) {
    echo "Invalid CPF: " . $e->getMessage();
}

// Validate crypto address
try {
    Validator::validateCryptoAddress(
        '0x742d35Cc6634C0532925a3b844Bc9e7595f0bEb4',
        'ETH'
    );
} catch (ValidationException $e) {
    echo "Invalid address: " . $e->getMessage();
}

// Validate amount
try {
    Validator::validateAmount('100.50', 10, 1000);
} catch (ValidationException $e) {
    echo "Invalid amount: " . $e->getMessage();
}
```

## Configuration Options

| Option | Type | Default | Description |
|--------|------|---------|-------------|
| email | string | required | Authentication email |
| password | string | required | Authentication password |
| base_url | string | https://api.xgateglobal.com | API base URL |
| timeout | int | 30 | Request timeout in seconds |
| retry_attempts | int | 3 | Number of retry attempts |
| cache_ttl | int | 3600 | Token cache TTL in seconds |
| verify_ssl | bool | true | SSL certificate verification |
| debug | bool | false | Enable debug mode |
| http_client | ClientInterface | null | Custom HTTP client |

## Environment Variables

You can use environment variables for configuration. Copy `.env.example` to `.env`:

```bash
cp .env.example .env
```

Then load configuration from environment:

```php
$config = new Configuration([
    'email' => $_ENV['XGATE_EMAIL'],
    'password' => $_ENV['XGATE_PASSWORD'],
    'base_url' => $_ENV['XGATE_BASE_URL'] ?? 'https://api.xgateglobal.com',
    'debug' => $_ENV['XGATE_DEBUG'] === 'true'
]);
```

## Testing

Run tests:

```bash
composer test
```

Run tests with coverage:

```bash
composer test:coverage
```

Run specific test suite:

```bash
vendor/bin/phpunit --testsuite unit
vendor/bin/phpunit --testsuite integration
```

## Code Quality

Check code style:

```bash
composer cs:check
```

Fix code style:

```bash
composer cs:fix
```

Run static analysis:

```bash
composer phpstan
```

Run all CI checks:

```bash
composer ci
```

## Recursos do PHP 8.4

O SDK usa os recursos mais recentes do PHP 8.4:

- **Property hooks** - Validação automática de propriedades
- **Asymmetric visibility** - Propriedades read-only públicas
- **BcMath\Number** - Cálculos financeiros precisos
- **array_find()** e **array_all()** - Novas funções de array

```php
// Validação automática com property hooks
$currency = new Currency();
$currency->symbol = 'usd'; // Converte automaticamente para 'USD'

// Cálculos precisos com BcMath
$amount = new Number('100.50');
$fee = new Number('2.5');
$total = $amount->add($fee); // 103.00
```

## Serviços disponíveis

- **Autenticação** - Login, refresh token, logout
- **Depósitos** - Criar, listar, cancelar, calcular taxas
- **Saques** - Fiat e crypto, múltiplas redes blockchain
- **Crypto** - Wallets, conversões, preços em tempo real
- **PIX** - QR Code, chaves PIX, transferências

## Contribuindo

Contribuições são bem-vindas! Por favor, abra uma issue primeiro para discutir mudanças maiores.

```bash
# Fork o projeto
git clone https://github.com/alissonlinneker/xgate-php-sdk.git
cd xgate-php-sdk

# Instalar dependências
composer install

# Rodar testes
composer test

# Verificar código
composer cs:check
composer phpstan
```

## Problemas?

Encontrou algum bug? Abra uma [issue](https://github.com/alissonlinneker/xgate-php-sdk/issues).

## Autor

**Alisson Linneker**
- GitHub: [@alissonlinneker](https://github.com/alissonlinneker)
- Email: alissonlinneker@gmail.com

## Licença

Apache 2.0 - veja o arquivo [LICENSE](LICENSE) para mais detalhes.

---

Desenvolvido com PHP 8.4 e muita ☕