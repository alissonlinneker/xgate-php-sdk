# Contributing to XGate Global PHP SDK

First off, thank you for considering contributing to the XGate Global PHP SDK! It's people like you that make this SDK a great tool.

## Code of Conduct

This project and everyone participating in it is governed by our Code of Conduct. By participating, you are expected to uphold this code.

## How Can I Contribute?

### Reporting Bugs

Before creating bug reports, please check existing issues as you might find out that you don't need to create one. When you are creating a bug report, please include as many details as possible:

- **Use a clear and descriptive title** for the issue to identify the problem
- **Describe the exact steps which reproduce the problem** in as many details as possible
- **Provide specific examples to demonstrate the steps**
- **Describe the behavior you observed after following the steps**
- **Explain which behavior you expected to see instead and why**
- **Include code samples** which demonstrate the issue

### Suggesting Enhancements

Enhancement suggestions are tracked as GitHub issues. When creating an enhancement suggestion, please include:

- **Use a clear and descriptive title** for the issue
- **Provide a step-by-step description of the suggested enhancement**
- **Provide specific examples to demonstrate the steps**
- **Describe the current behavior** and **explain which behavior you expected to see instead**
- **Explain why this enhancement would be useful**

### Pull Requests

1. Fork the repo and create your branch from `develop`
2. If you've added code that should be tested, add tests
3. If you've changed APIs, update the documentation
4. Ensure the test suite passes
5. Make sure your code follows the existing code style
6. Issue that pull request!

## Development Setup

1. Clone the repository:
```bash
git clone https://github.com/xgate-global/php-sdk.git
cd php-sdk
```

2. Install dependencies:
```bash
composer install
```

3. Copy the environment file:
```bash
cp .env.example .env
```

4. Run tests:
```bash
composer test
```

## Coding Standards

We use PHP-CS-Fixer to maintain code style consistency:

```bash
# Check code style
composer cs:check

# Fix code style
composer cs:fix
```

## Static Analysis

We use PHPStan for static analysis:

```bash
composer phpstan
```

## Testing

### Running Tests

```bash
# Run all tests
composer test

# Run with coverage
composer test:coverage

# Run specific test suite
vendor/bin/phpunit --testsuite unit
vendor/bin/phpunit --testsuite integration
```

### Writing Tests

- Write unit tests for all new functionality
- Maintain test coverage above 80%
- Use descriptive test names
- Follow the existing test structure

Example test:

```php
public function testDepositCreationWithValidData(): void
{
    // Arrange
    $amount = new Number('100.00');
    $customerId = 'customer_123';

    // Act
    $result = $this->service->create($amount, $customerId, 'USD');

    // Assert
    $this->assertInstanceOf(Transaction::class, $result);
    $this->assertEquals('100.00', (string)$result->amount);
}
```

## PHP 8.4 Features

When contributing, make use of PHP 8.4 features where appropriate:

- Property hooks for validation
- Asymmetric visibility
- New array functions (array_find, array_all, array_any)
- BcMath\Number for financial calculations

## Documentation

- Update README.md for user-facing changes
- Add PHPDoc comments for all public methods
- Include code examples for new features
- Update CHANGELOG.md following [Keep a Changelog](https://keepachangelog.com/)

## Commit Messages

We follow conventional commits:

- `feat:` New feature
- `fix:` Bug fix
- `docs:` Documentation changes
- `style:` Code style changes (formatting, etc)
- `refactor:` Code refactoring
- `perf:` Performance improvements
- `test:` Test additions or corrections
- `chore:` Maintenance tasks

Examples:
```
feat: add support for webhook handling
fix: correct rate limiting calculation
docs: update installation instructions
```

## Review Process

1. All submissions require review
2. We use GitHub pull requests for this purpose
3. Reviewers will provide feedback
4. Make requested changes and push new commits
5. Your PR will be merged once approved

## Release Process

We use semantic versioning:

- MAJOR version for incompatible API changes
- MINOR version for backwards-compatible functionality
- PATCH version for backwards-compatible bug fixes

## Questions?

Feel free to contact the maintainers at developers@xgateglobal.com

Thank you for contributing!