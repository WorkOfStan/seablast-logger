# Seablast Logger - Maintainer rules and validation

## Project

Maintain `seablast/logger`, a standalone PHP PSR-3 logger backed by `error_log()`. [README.md](README.md) owns installation, configuration, API behavior, and operational security; this file owns maintainer constraints and validation. Read the relevant contract before editing and update it when behavior changes.

Keep the Composer PHP constraint exactly `"php": ">=7.2 <8.6"` unless the user explicitly requests a supported-version change. Preserve the dependency ranges in [composer.json](composer.json), including supported PSR-3 interface versions.

## Classes and supporting files

| Class (linked to source)                                                     | Responsibility                                                                      |
| ---------------------------------------------------------------------------- | ----------------------------------------------------------------------------------- |
| [Seablast\Logger\ErrorLogFailureException](src/ErrorLogFailureException.php) | Signal a reported `error_log()` file/system/mail failure                            |
| [Seablast\Logger\Logger](src/Logger.php)                                     | PSR-3 methods, numeric filtering, context category, record formatting, destinations |
| [Seablast\Logger\LoggerTime](src/LoggerTime.php)                             | Timer baseline, elapsed seconds, formatted page-generation time                     |

[test.php](test.php) demonstrates file logging and runtime verbosity changes.
[phpunit.xml](phpunit.xml) configures local tests;
[conf/phpunit-github.xml](conf/phpunit-github.xml) supplies the CI variant.
[conf/phpstan.app.neon](conf/phpstan.app.neon) contains project analysis settings;
[the workflow](.github/workflows/polish-the-code.yml) defines CI integration.
[CLAUDE.md](CLAUDE.md) delegates shared instructions to this file.

## Change Rules

- Inspect `git status` first and preserve unrelated worktree changes.
- Never remove comments. Clarify them, translate them to English, or remove a TODO only when it is actually solved.
- Sort PHP imports, properties, and methods alphabetically, ignoring case; keep PHP lines within 120 characters. PHPCS runs in CI; no local PHPCS run is required.
- Append an English present-tense, verb-led entry at the end of the relevant `CHANGELOG.md` block. Update canonical documentation and affected examples together.
- Review security implications of log paths, email destinations, host lookups, and user-controlled text; preserve the invariants below.

## Behavior and security invariants

- Preserve [severity/category semantics](README.md#log-levels-and-context): fixed PSR-3 mapping, integer custom levels, separate category extraction, and the category-6 bypass based on configured verbosity rather than the runtime override.
- Do not cast arbitrary context values or search beyond the first fallback value. Context is not interpolated or serialized; preserve string/stringable message handling and controlled `Psr\Log\InvalidArgumentException` failures.
- Preserve [destination behavior](README.md#destinations-and-failures): automatic suffixes, empty-path fallback for type 3, nonzero type coercion, and failure exceptions. Admin email requires a string recipient and an admitted level-1 message; do not silently widen it to emergency (0) or all severe messages.
- Escape control characters in every untrusted text field, including message, user, script path, request URI, severity label, and resolved host. Keep each record on one line. Escaping does not redact URL queries or message secrets.
- Keep destination paths/recipients trusted and logs private. Never use the reverse-DNS host as authenticated identity or expose verbosity overrides directly to request parameters. Public deployment guidance: [log security](README.md#log-data-and-security).
- Preserve the [timing contract](README.md#runtime-adjustment): timer construction sets the baseline; admitted log calls update elapsed time and profiling gaps. Retain timer injection so output tests can use deterministic elapsed values.

## Commands

From the repository root, with PHP and Composer available on your PATH:

```sh
composer install
php vendor/bin/phpunit --do-not-cache-result -c phpunit.xml
```

Do not modify Composer or run `self-update`. Tests need filesystem access but no database.

## Validation

| Change                                                                                               | Regression entry points                                            |
| ---------------------------------------------------------------------------------------------------- | ------------------------------------------------------------------ |
| File output, normal verbosity, runtime override, context, invalid levels, control-character escaping | [tests/LoggerTest.php](tests/LoggerTest.php)                       |
| Elapsed time and formatted timing strings                                                            | [tests/LoggerTimeTest.php](tests/LoggerTimeTest.php)               |
| Deterministic elapsed-time fixture for output tests                                                  | [Seablast\Logger\Tests\FixedLoggerTime](tests/FixedLoggerTime.php) |

`LoggerTest` creates uniquely named `.tmp/phpunit-logger-*` directories and removes its own fixtures; it restores `$_SERVER` after each test. Add focused regressions when changing branches not covered by these tests, such as mail or monthly rotation.

For documentation-only changes, review the diff, verify class names and local links/anchors, lint PHP examples, and run `git diff --check`. Report actual checks and any skipped or blocked validation; runtime tests are unnecessary for prose edits.

## Files To Avoid

Do not inspect, lint, or recurse into `.tmp`, `vendor`, or build artifacts unless the user specifically asks for those generated files.
