# Drupal 12 Readiness CLI

[![CI](https://github.com/victorstack-ai/drupal-12-readiness-cli/actions/workflows/ci.yml/badge.svg)](https://github.com/victorstack-ai/drupal-12-readiness-cli/actions/workflows/ci.yml)
[![License: MIT](https://img.shields.io/badge/License-MIT-blue.svg)](LICENSE)
[![PHP Version](https://img.shields.io/badge/PHP-8.1%2B-8892BF.svg)](https://www.php.net/)

A CLI tool to help prepare Drupal projects for Drupal 12 by identifying deprecated code and legacy patterns. It combines **PHPStan-based deprecation analysis** with a dedicated **procedural Database API audit** to give you a comprehensive readiness report.

---

## Installation

```bash
composer require --dev victorstack-ai/drupal-12-readiness-cli
```

Or clone and install locally:

```bash
git clone https://github.com/victorstack-ai/drupal-12-readiness-cli.git
cd drupal-12-readiness-cli
composer install
```

## Usage

### Scan for General Deprecations (PHPStan)

This command uses PHPStan with `mglaman/phpstan-drupal` to identify deprecated class and method usages.

```bash
./bin/drupal-12-readiness scan /path/to/module
```

**Example output:**

```
Scanning /var/www/drupal/web/modules/custom/my_module for Drupal 12 deprecations...

 ------ -------------------------------------------------------
  Line   DeprecatedClass.php
 ------ -------------------------------------------------------
  10     Call to deprecated function file_create_url():
         Deprecated in drupal:9.3.0 and is removed from
         drupal:12.0.0. Use the appropriate method on
         \Drupal\Core\File\FileUrlGeneratorInterface instead.
 ------ -------------------------------------------------------

 [ERROR] Found 1 error

Scan completed with potential issues.
```

### Audit Database API Usage

Drupal 12 removes many deprecated procedural Database API functions (e.g., `db_query`, `db_select`). This command scans your code for these legacy calls.

```bash
./bin/drupal-12-readiness check:db-api /path/to/module
```

**Example output:**

```
Scanning /var/www/drupal/web/modules/custom/my_module for deprecated Database API usage...

 ! [WARNING] Found 2 instances of deprecated Database API usage in 3 files:

 ------------------------------------  -----------  ---------------------------------------------------  -----------------------------------------------
  Location                             Function     Context                                              Suggested Replacement
 ------------------------------------  -----------  ---------------------------------------------------  -----------------------------------------------
  deprecated_module.module:10          db_query     $result = db_query("SELECT nid FROM {node}...");     Use \Drupal::database()->query() or injected
                                                                                                         connection
  deprecated_module.module:13          db_select    $query = db_select('users', 'u');                    Use \Drupal::database()->select() or injected
                                                                                                         connection
 ------------------------------------  -----------  ---------------------------------------------------  -----------------------------------------------
```

### HTML Report Output

Both commands support an `--output=html` flag that generates a styled HTML report file, useful for sharing with team members or archiving.

```bash
# Generate an HTML report for the deprecation scan
./bin/drupal-12-readiness scan /path/to/module --output=html

# Generate an HTML report for the Database API audit
./bin/drupal-12-readiness check:db-api /path/to/module --output=html
```

**Example output:**

```
Scanning /var/www/drupal/web/modules/custom/my_module for deprecated Database API usage...

 [OK] HTML report written to: /var/www/drupal/web/modules/custom/my_module/drupal12-db-api-report.html
```

The generated HTML report includes:
- A summary with the total number of issues found
- A detailed table with file location, deprecated function, code context, and suggested replacement
- Metadata including the scanned path, number of files scanned, and generation timestamp

---

## What It Checks

### PHPStan Deprecation Scan (`scan`)

Uses PHPStan level 1 with `mglaman/phpstan-drupal` and `phpstan-deprecation-rules` to detect:

- Calls to deprecated Drupal functions and methods
- Usage of deprecated classes and interfaces
- References to deprecated constants
- Deprecated hook implementations
- Any code patterns flagged by Drupal's `@deprecated` annotations

### Procedural Database API Audit (`check:db-api`)

Detects usage of **30 deprecated procedural database functions** that are removed in Drupal 12:

| Deprecated Function | Replacement |
|---|---|
| `db_query()` | `\Drupal::database()->query()` |
| `db_select()` | `\Drupal::database()->select()` |
| `db_insert()` | `\Drupal::database()->insert()` |
| `db_update()` | `\Drupal::database()->update()` |
| `db_delete()` | `\Drupal::database()->delete()` |
| `db_merge()` | `\Drupal::database()->merge()` |
| `db_transaction()` | `\Drupal::database()->startTransaction()` |
| `db_close()` | Database connections are closed automatically |
| `db_next_id()` | Use sequences or auto-increment |
| `db_or()` | `\Drupal::database()->condition('or')` |
| `db_and()` | `\Drupal::database()->condition('and')` |
| `db_xor()` | `\Drupal::database()->condition('xor')` |
| `db_condition()` | `\Drupal::database()->condition()` |
| `db_like()` | `\Drupal::database()->escapeLike()` |
| `db_driver()` | `\Drupal::database()->driver()` |
| `db_escape_field()` | `\Drupal::database()->escapeField()` |
| `db_escape_table()` | `\Drupal::database()->escapeTable()` |
| `db_find_tables()` | `\Drupal::database()->schema()->findTables()` |
| `db_ignore_replica()` | `\Drupal\Core\Database\Database::ignoreTarget('default', 'replica')` |
| `db_rename_table()` | `\Drupal::database()->schema()->renameTable()` |
| `db_drop_table()` | `\Drupal::database()->schema()->dropTable()` |
| `db_add_field()` | `\Drupal::database()->schema()->addField()` |
| `db_drop_field()` | `\Drupal::database()->schema()->dropField()` |
| `db_field_exists()` | `\Drupal::database()->schema()->fieldExists()` |
| `db_index_exists()` | `\Drupal::database()->schema()->indexExists()` |
| `db_add_primary_key()` | `\Drupal::database()->schema()->addPrimaryKey()` |
| `db_drop_primary_key()` | `\Drupal::database()->schema()->dropPrimaryKey()` |
| `db_add_unique_key()` | `\Drupal::database()->schema()->addUniqueKey()` |
| `db_drop_unique_key()` | `\Drupal::database()->schema()->dropUniqueKey()` |
| `db_add_index()` | `\Drupal::database()->schema()->addIndex()` |
| `db_drop_index()` | `\Drupal::database()->schema()->dropIndex()` |
| `db_change_field()` | `\Drupal::database()->schema()->changeField()` |

### File Types Scanned

Both commands scan the following Drupal file types:

- `.php` -- Standard PHP files
- `.module` -- Drupal module files
- `.inc` -- Include files
- `.install` -- Install/update files
- `.theme` -- Theme files

---

## Requirements

- PHP 8.1+
- Composer

## Development

```bash
# Install dependencies
composer install

# Run tests
vendor/bin/phpunit

# Run code style checks
vendor/bin/phpcs

# Fix auto-fixable code style issues
vendor/bin/phpcbf
```

## License

This project is licensed under the MIT License. See the [LICENSE](LICENSE) file for details.
