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

### Fixed

- Resolved PHPStan errors by narrowing mixed config inputs and removing leftover references to the removed $conf property.
- Ensured php -l and phpunit pass locally across supported PHP versions.

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

[Unreleased]: https://github.com/WorkOfStan/seablast-logger/compare/v2.0.5...HEAD
[2.0.5]: https://github.com/WorkOfStan/seablast-logger/compare/v2.0.4...v2.0.5
[2.0.4]: https://github.com/WorkOfStan/seablast-logger/compare/v2.0.3...v2.0.4
[2.0.3]: https://github.com/WorkOfStan/seablast-logger/compare/v2.0.2...v2.0.3
[2.0.2]: https://github.com/WorkOfStan/seablast-logger/compare/v2.0.1...v2.0.2
[2.0.1]: https://github.com/WorkOfStan/seablast-logger/compare/v2.0...v2.0.1
[2.0]: https://github.com/WorkOfStan/seablast-logger/compare/v1.0...v2.0
[1.0]: https://github.com/WorkOfStan/seablast-logger/compare/v0.2...v1.0
[0.2]: https://github.com/WorkOfStan/seablast-logger/compare/v0.1...v0.2
[0.1]: https://github.com/WorkOfStan/seablast-logger/releases/tag/v0.1
