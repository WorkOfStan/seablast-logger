# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### `Added` for new features

### `Changed` for changes in existing functionality

### `Deprecated` for soon-to-be removed features

### `Removed` for now removed features

### `Fixed` for any bugfixes

### `Security` in case of vulnerabilities

## [2.0.6] - 2026-06-20

refactor: fix PSR-3 context handling

### Added

- Added `AGENTS.md` with repository workflow notes for future coding agents.
- Added PHPUnit coverage for `Logger` file output, verbosity filtering, unsupported PSR-3 levels, and non-numeric context values.

### Changed

- Clarified package and [README.md](README.md) descriptions around `error_log()`, speed-level behavior, PSR-3 context handling, and the supported PHP range.
- Documented why `Logger` constructor configuration is typed as `array<string,mixed>`.
- Describe `FixedLoggerTime` as a deterministic helper for `LoggerTest.php`.

### Removed

- Removed the no-op `LoggerTimeTest::tearDown()` hook.

### Fixed

- Fixed PSR-3 context handling so non-numeric context values no longer get cast while resolving the optional log error number.Allowing for `$logger->info('Failed request', ['exception' => $e]);` extra data.
- Fixed unsupported string log levels to throw `Psr\Log\InvalidArgumentException` instead of writing a secondary log entry.
- Fixed logging level names to fall back to `unknown` for numeric levels without a configured name.
- Fixed log message normalization so PHPStan can prove messages are strings before writing to `error_log()`.
- Move `FixedLoggerTime` into its own test helper file to satisfy PHPCS class-per-file rules.
- Fix PHPStan findings in logger tests after adding log-injection coverage.

### Security

- Use a runtime-created log directory for the demo logger destination.
- Warn that `logging_file` must stay trusted and outside the public web directory.
- Escape control characters in log-line fields to prevent forged log entries.
- Promote `webmozart/assert` to runtime dependencies so production installs include the assertion class used by `Logger`.

## [2.0.5] - 2026-03-08

fix: remove support below PHP/7.2, because of CVE-2026-24765

### Changed

- package limited to the tested PHP versions, i.e. "php": ">=7.2 <8.6"
- GitHub Actions version bump to super-linter v8.5.0
- Logger refactor: replaced legacy protected $conf array with explicit, typed class properties (errorLogMessageType, loggingFile, loggingLevel, loggingLevelName, loggingLevelPageSpeed, logMonthlyRotation, logProfilingStep, mailForAdminEnabled) to improve type safety and static analysis compatibility.
- Constructor now prefers property defaults and only overrides properties when config keys are present; config values are validated/narrowed before assignment to satisfy PHPStan and avoid unsafe casts.
- Introduced WebMozart Assert for concise runtime validations where appropriate.

### Added

- PHPStan-friendly type hints and PHPDoc improvements for Logger properties (e.g. array<int,string> for loggingLevelName).

### Security

- fix: remove support below PHP/7.2, because of CVE-2026-24765

## [2.0.4] - 2025-10-23

chore: add PHP/8.5 support

## [2.0.3] - 2025-03-09

chore: polish-the-code.yml chain of GitHub Actions

### Changed

- GitHub Actions combined to polish-the-code.yml (instead of linter.yml, php-composer-dependencies.yml, prettier-fix.yml, phpcbf.yml)
- GitHub Actions version bump

## [2.0.2] - 2024-12-10

refactor: Fix formatting with Prettier, remove obsolete code, and enforce stricter type checks.

### Added

- prettier-fix

### Changed

- update Logger.php limited result-exception scope
- more strict type checks

### Removed

- LoggerTime::getmicrotime() condition addressing PHP<5
- LoggerTimeTest::testGetmicrotime() testing Seablast\Logger\LoggerTime::getmicrotime as unnecessary

## [2.0.1] - 2024-08-10

ci: PHPUnit test for class LoggerTime

### Added

- PHPUnit test for class LoggerTime

## [2.0] - 2024-07-27

Stable version for `"php": "^7.1 || ^8.0"`. (As of PHP 7.1.0 visibility modifiers are allowed for class constants.)

## [1.0] - 2024-07-27

Stable version for `"php": "^5.3 || ^7.0"`

## [0.2] - 2024-07-23

### Added

- Class configuration is managed by an array where field names are defined as constants to enable IDE hints.

### Fixed

- argument `$level` of method `log` accepts also strings defined in Psr\Log\LogLevel as required by [PSR-3](https://www.php-fig.org/psr/psr-3/)

## [0.1] - 2024-07-12

- A [PSR-3](https://www.php-fig.org/psr/psr-3/) compliant logger with adjustable verbosity (based on Backyard\BackyardError)

[Unreleased]: https://github.com/WorkOfStan/seablast-logger/compare/v2.0.6...HEAD
[2.0.6]: https://github.com/WorkOfStan/seablast-logger/compare/v2.0.5...v2.0.6
[2.0.5]: https://github.com/WorkOfStan/seablast-logger/compare/v2.0.4...v2.0.5
[2.0.4]: https://github.com/WorkOfStan/seablast-logger/compare/v2.0.3...v2.0.4
[2.0.3]: https://github.com/WorkOfStan/seablast-logger/compare/v2.0.2...v2.0.3
[2.0.2]: https://github.com/WorkOfStan/seablast-logger/compare/v2.0.1...v2.0.2
[2.0.1]: https://github.com/WorkOfStan/seablast-logger/compare/v2.0...v2.0.1
[2.0]: https://github.com/WorkOfStan/seablast-logger/compare/v1.0...v2.0
[1.0]: https://github.com/WorkOfStan/seablast-logger/compare/v0.2...v1.0
[0.2]: https://github.com/WorkOfStan/seablast-logger/compare/v0.1...v0.2
[0.1]: https://github.com/WorkOfStan/seablast-logger/releases/tag/v0.1
