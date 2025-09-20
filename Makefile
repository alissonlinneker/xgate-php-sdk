.PHONY: help install test cs phpstan ci clean docs

help:
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | awk 'BEGIN {FS = ":.*?## "}; {printf "\033[36m%-20s\033[0m %s\n", $$1, $$2}'

install: ## Install dependencies
	composer install

update: ## Update dependencies
	composer update

test: ## Run tests
	vendor/bin/phpunit --testdox

test-coverage: ## Run tests with coverage
	vendor/bin/phpunit --coverage-html coverage/

test-unit: ## Run unit tests only
	vendor/bin/phpunit --testsuite unit

test-integration: ## Run integration tests only
	vendor/bin/phpunit --testsuite integration

cs: ## Fix code style
	vendor/bin/php-cs-fixer fix

cs-check: ## Check code style
	vendor/bin/php-cs-fixer fix --dry-run --diff

phpstan: ## Run static analysis
	vendor/bin/phpstan analyse

phpstan-baseline: ## Generate PHPStan baseline
	vendor/bin/phpstan analyse --generate-baseline

ci: cs-check phpstan test ## Run CI checks

clean: ## Clean generated files
	rm -rf coverage/ .phpunit.cache/ .php-cs-fixer.cache var/ cache/ tmp/

docs: ## Generate documentation
	@echo "Documentation generation not configured yet"

docker-build: ## Build Docker image
	docker build -t xgate-php-sdk .

docker-test: ## Run tests in Docker
	docker run --rm xgate-php-sdk make test

validate: ## Validate composer.json
	composer validate --strict

security-check: ## Check for security vulnerabilities
	composer audit