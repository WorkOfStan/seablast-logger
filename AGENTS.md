# AGENTS.md

## Project

`seablast/logger` is a PHP library that provides a PSR-3 compliant logger backed by PHP's `error_log()` with numeric verbosity controls.

Keep the Composer PHP constraint exactly as `"php": ">=7.2 <8.6"` unless the user explicitly asks for a supported-version change.

## Change Rules

- Never remove comments. You may update comments for clarity, translate them to English, or remove a `TODO` only when the `TODO` is actually solved.
- Update `CHANGELOG.md` in English for notable changes.
- Keep docs and examples aligned with `src/Logger.php`, especially logging levels, file rotation, and PHP version support.
- Check security implications when changing logging behavior. Treat log destination paths, email destinations, host lookups, and user-controlled log text carefully.

## Commands

Install dependencies with Composer on Windows like this:

```powershell
$env:COMPOSER_CACHE_DIR = "$PWD\.composer-cache"
php "C:\ProgramData\ComposerSetup\bin\composer.phar" install
```

Do not modify Composer itself and do not run `composer self-update`.

Run PHPUnit without using the result cache:

```powershell
& .\vendor\bin\phpunit --do-not-cache-result -c phpunit.xml
```

On Windows, do not run `.sh` helper scripts directly from PowerShell. Use Git Bash explicitly, for example:

```powershell
& "C:\Program Files\Git\bin\bash.exe" -lc "./blast.sh phpstan"
& "C:\Program Files\Git\bin\bash.exe" -lc "./blast.sh phpstan-remove"
```

## Files To Avoid

Do not inspect, lint, or recurse into `.tmp`, `.pytest-tmp`, `.pytest_cache`, `.venv`, `vendor`, or build artifacts unless the user specifically asks for those generated files.
