COMPOSER ?= composer
PHP_CS_FIXER ?= vendor/bin/php-cs-fixer
PHPSTAN ?= vendor/bin/phpstan

.DEFAULT_GOAL := help
.PHONY: help validate install cs-fix cs-check stan-check audit pipeline pipeline-check

help: ## Show available targets
	@awk 'BEGIN {FS = ":.*## "}; /^[a-zA-Z0-9_.-]+:.*## / {printf "  %-14s %s\n", $$1, $$2}' $(MAKEFILE_LIST)

validate: ## Validate composer files
	$(COMPOSER) validate --strict

install: ## Install PHP dependencies
	$(COMPOSER) install --prefer-dist --no-progress

cs-fix: ## Run PHP CS Fixer
	$(PHP_CS_FIXER) fix

cs-check: ## Run PHP CS Fixer in dry-run mode
	$(PHP_CS_FIXER) fix --dry-run

stan-check: ## Run PHPStan analysis
	$(PHPSTAN) analyse

audit: ## Run composer security audit
	$(COMPOSER) audit --abandoned=ignore

pipeline: cs-fix stan-check audit ## Run full local pipeline

pipeline-check: cs-check stan-check audit ## Run CI checks pipeline
