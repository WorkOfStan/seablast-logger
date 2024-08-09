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

## [2.0.1] - 2024-08-10
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

[Unreleased]: https://github.com/WorkOfStan/seablast-logger/compare/v2.0.1...HEAD
[2.0.1]: https://github.com/WorkOfStan/seablast-logger/compare/v2.0...v2.0.1
[2.0]: https://github.com/WorkOfStan/seablast-logger/compare/v1.0...v2.0
[1.0]: https://github.com/WorkOfStan/seablast-logger/compare/v0.2...v1.0
[0.2]: https://github.com/WorkOfStan/seablast-logger/compare/v0.1...v0.2
[0.1]: https://github.com/WorkOfStan/seablast-logger/releases/tag/v0.1
