# Drupal 12 Readiness CLI

A CLI tool to help prepare Drupal projects for Drupal 12 by identifying deprecated code and legacy patterns.

## Installation

```bash
composer require --dev victorstack-ai/drupal-12-readiness-cli
```

## Usage

### Scan for General Deprecations (PHPStan)

This command uses PHPStan with `mglaman/phpstan-drupal` to identify deprecated class and method usages.

```bash
./bin/drupal-12-readiness scan /path/to/module
```

### Audit Database API Usage

Drupal 12 removes many deprecated procedural Database API functions (e.g., `db_query`, `db_select`). This command scans your code for these legacy calls.

```bash
./bin/drupal-12-readiness check:db-api /path/to/module
```

It checks for:
- `db_query` -> `\Drupal::database()->query()`
- `db_select` -> `\Drupal::database()->select()`
- `db_insert`, `db_update`, `db_delete`, etc.
- And 20+ other procedural wrappers.

## Requirements

- PHP 8.1+
