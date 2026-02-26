COMPOSER ?= composer
PHP_CS_FIXER ?= vendor/bin/php-cs-fixer
PHP_CS_FIXER_CONFIG ?= .php-cs-fixer.dist.php
PHP_CS_FIXER_FLAGS ?= --quiet
PHPSTAN ?= vendor/bin/phpstan
PHPSTAN_CONFIG ?= phpstan.neon
PHPUNIT ?= vendor/bin/phpunit
PHPUNIT_CONFIG ?= phpunit.xml.dist

.DEFAULT_GOAL := help
.PHONY: help validate install cs-fix cs-check stan-check test audit pipeline pipeline-check

help: ## Show available targets
	@awk 'BEGIN {FS = ":.*## "}; /^[a-zA-Z0-9_.-]+:.*## / {printf "  %-14s %s\n", $$1, $$2}' $(MAKEFILE_LIST)

validate: ## Validate composer files
	$(COMPOSER) validate --strict

install: ## Install PHP dependencies
	$(COMPOSER) install --prefer-dist --no-progress

cs-fix: ## Run PHP CS Fixer
	$(PHP_CS_FIXER) fix --config=$(PHP_CS_FIXER_CONFIG) $(PHP_CS_FIXER_FLAGS)

cs-check: ## Run PHP CS Fixer in dry-run mode
	$(PHP_CS_FIXER) fix --dry-run --config=$(PHP_CS_FIXER_CONFIG) $(PHP_CS_FIXER_FLAGS)

stan-check: ## Run PHPStan analysis
	$(PHPSTAN) analyse --configuration=$(PHPSTAN_CONFIG)

test: ## Run PHPUnit tests
	$(PHPUNIT) --configuration=$(PHPUNIT_CONFIG)

audit: ## Run composer security audit
	$(COMPOSER) audit --abandoned=ignore

pipeline: cs-fix stan-check test audit ## Run full local pipeline

pipeline-check: cs-check stan-check test audit ## Run CI checks pipeline
