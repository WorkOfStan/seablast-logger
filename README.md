# Seablast Logger

[![Total Downloads](https://img.shields.io/packagist/dt/seablast/logger.svg)](https://packagist.org/packages/seablast/logger)
[![Latest Stable Version](https://img.shields.io/packagist/v/seablast/logger.svg)](https://packagist.org/packages/seablast/logger)
[![Polish the code](https://github.com/WorkOfStan/seablast-logger/actions/workflows/polish-the-code.yml/badge.svg)](https://github.com/WorkOfStan/seablast-logger/actions/workflows/polish-the-code.yml)

Seablast Logger provides [PSR-3](http://www.php-fig.org/psr/psr-3/) logging for PHP applications through PHP's `error_log()`, with runtime-adjustable log verbosity and optional monthly log files.
It includes elapsed-time profiling, user identifiers, and Tracy integration; it can be used without the [Seablast](https://github.com/WorkOfStan/seablast) framework.

Maintainer rules and validation: [AGENTS.md](AGENTS.md).

## Installation

```sh
composer require seablast/logger
```

Requires PHP `>=7.2 <8.6`; compatible dependency ranges are in [composer.json](composer.json).
The logger implements the [PSR-3 interface](https://www.php-fig.org/psr/psr-3/).

## Usage

```php
require 'vendor/autoload.php';

use Seablast\Logger\Logger;

$logger = new Logger();
$logger->setUser(42);
$logger->info('Export completed');
```

By default, messages go to PHP's configured system logger at verbosity 5 (debug).
Choose a lower threshold for production without changing logging calls.
Screen output is not supported. [test.php](test.php) demonstrates verbosity changes and file output; its sample logs are written under the repository's `log/` directory.

## Configuration

Pass overrides to `new Logger($conf)`. This example lists all defaults:

```php
use Seablast\Logger\Logger;
$conf = array(
    // THESE ARE THE DEFAULT SETTINGS
    // 0 = send message to PHP's system logger; 3 appends to the file destination set in 'logging_file'
    Logger::CONF_ERROR_LOG_MESSAGE_TYPE => 0,
    // if error_log_message_type equals 3, the message is appended to this file destination (path and name);
    // keep this path trusted and outside the public web directory
    Logger::CONF_LOGGING_FILE => '',
    // verbosity: default 5 includes debug; level 6 is filtered unless the page-speed category bypass applies
    Logger::CONF_LOGGING_LEVEL => 5,
    // customize numeric labels if needed; this does not change PSR-3 severity mapping
    Logger::CONF_LOGGING_LEVEL_NAME => array(
        0 => 'unknown', 1 => 'fatal', 'error', 'warning', 'info', 'debug', 'speed'
    ),
    // minimum configured verbosity needed to log page-speed messages, i.e. messages with error_number 6
    Logger::CONF_LOGGING_LEVEL_PAGE_SPEED => 5,
    // false => append .log to logging_file; true => append .Y-m.log
    Logger::CONF_LOG_MONTHLY_ROTATION => true,
    // prefix message that took longer than profiling step (float) sec from the previous one by SLOWSTEP
    Logger::CONF_LOG_PROFILING_STEP => false,
    // set a trusted email address to also mail logged level-1 (alert/critical) messages; false disables mail
    Logger::CONF_MAIL_FOR_ADMIN_ENABLED => false,
);
$logger = new Logger($conf);
```

### Destinations and failures

| Setting | Behavior |
| --- | --- |
| `CONF_ERROR_LOG_MESSAGE_TYPE = 0` | PHP's system logger; the configured file destination is not used |
| `CONF_ERROR_LOG_MESSAGE_TYPE = 3` | Append to the configured file; an empty base path falls back to the system logger with a diagnostic |
| `CONF_LOG_MONTHLY_ROTATION = true` | A base path `/private/log/app` becomes `/private/log/app.Y-m.log` |
| `CONF_LOG_MONTHLY_ROTATION = false` | The same base path becomes `/private/log/app.log` |
| `CONF_MAIL_FOR_ADMIN_ENABLED = 'ops@example.com'` | Also email level-1 messages (alert/critical) that pass filtering; boolean `true` alone does not enable delivery |

Use message types 0 or 3; other nonzero values are currently coerced to file mode.
Supply a base path without `.log`, create its parent directory, and grant the PHP process write access. Monthly rotation selects a filename; it does not delete old files.
If `error_log()` reports failure, the logger throws [ErrorLogFailureException](src/ErrorLogFailureException.php), including for mail failures.

## Log levels and context

Lower numbers are more severe. Normal filtering admits levels at or below `max(configured verbosity, runtime override)`:

| PSR-3 method / string level | Numeric level | Default label |
| --- | --- | --- |
| `emergency` | 0 | `unknown` |
| `alert`, `critical` | 1 | `fatal` |
| `error` | 2 | `error` |
| `warning` | 3 | `warning` |
| `notice`, `info` | 4 | `info` |
| `debug` | 5 | `debug` |
| No PSR-3 equivalent; pass integer `6` | 6 | `speed` |

Default verbosity 5 admits levels 0-5 and normally ignores level 6. `log()` accepts standard PSR-3 strings or integer levels, not numeric strings or custom label names.
Changing `CONF_LOGGING_LEVEL_NAME` changes labels, not the PSR-3 mapping; unmapped integers use label 0, or `unknown` if absent. Unsupported string levels/types and messages other than strings or stringable objects throw `Psr\Log\InvalidArgumentException`.

Context selects a separate numeric category: a numeric `error_number` takes priority; otherwise only the **first context value** is checked for a number, then defaults to 0.
The logger does not search later values. Other context is neither interpolated into placeholders nor serialized; passing an `exception` does not write its stack trace.
Category numbers are listed in [Logger::log()](src/Logger.php).

Category **6** has an extra filtering exception: it logs when `CONF_LOGGING_LEVEL >= CONF_LOGGING_LEVEL_PAGE_SPEED`, regardless of message severity.
This compares the configured verbosity, not the runtime override. With defaults:

```php
$logger->log(6, 'Ordinary speed message'); // filtered: severity 6 exceeds verbosity 5
$logger->log(6, 'Page generation timing', ['error_number' => 6]); // written: category bypass
```

## Runtime adjustment

| Method / setting | Behavior |
| --- | --- |
| `Logger::logAtLeastToLevel(int $newLevel)` | Replace the runtime override; cannot reduce effective verbosity below the constructor setting |
| `Logger::setUser($user)` | Accept an integer or string identifier for subsequent log prefixes; defaults to `unidentified` |
| `Logger::getLastRunningTime()` | Seconds since the injected timer's start at the last log call that passed filtering; initially 0 |
| `CONF_LOG_PROFILING_STEP` | A positive threshold in seconds adds `SLOWSTEP` when time since the previous log call that passed filtering exceeds it; `false` disables it |
| `LoggerTime::getRunningTime()` | Current elapsed seconds, rounded to four decimal places |
| `LoggerTime::pageGeneratedIn(string $langStringPageGeneratedIn = '%s')` | Replace `%s` in a format string with elapsed seconds |

The optional second constructor argument is a `LoggerTime`. Its start is captured when the timer is constructed; construct it at request start if that is the desired baseline. The default timer starts with the logger, not automatically with PHP's request.

## Tracy\Logger::log wrapper

After loading Composer's autoloader, connect the logger through Tracy's PSR adapter (available since Tracy 2.6.0; installed versions follow `composer.json`):

```php
$logger = new \Seablast\Logger\Logger();
$tracyLogger = new \Tracy\Bridges\Psr\PsrToTracyLoggerAdapter($logger);
\Tracy\Debugger::setLogger($tracyLogger);
\Tracy\Debugger::enable();
```

## Log data and security

Each record includes timestamp, severity label, category, script filename, `user@host`, elapsed seconds, request URI, and message. The host is resolved from `REMOTE_ADDR` using reverse DNS; it is diagnostic metadata, not verified identity.
Request URIs include query strings. Control characters in text fields are escaped to prevent forged lines; this does not redact secrets.

Keep log paths and email recipients under application control. Validate any values coming from environment settings or request input. Store logs outside the public web root, restrict access to authorized operators, and manage retention externally.
Avoid secrets in messages and URLs; adding a value to context does not sanitize sensitive data already present in the message or request URI.
