# seablast-logger

A [PSR-3](http://www.php-fig.org/psr/psr-3/) compliant logger with adjustable verbosity.

The logging level verbosity can be tailored to suit different environments.
For instance, in a development environment, the logger can be configured to log more detailed information compared to a production environment, all without changing your code.
Simply adjust the verbosity.

Internally, messages are written through PHP's `error_log()` function, either to PHP's system logger or to configured log files.

The `logging_level` controls verbosity. Lower numbers are more severe, and the logger writes messages whose level is less than or equal to the configured level. These parameters can be configured when instantiating the logger:

```php
use Seablast\Logger\Logger;
$conf = array(
    // THESE ARE THE DEFAULT SETTINGS
    // 0 = send message to PHP's system logger; 3 appends to the file destination set in 'logging_file'
    Logger::CONF_ERROR_LOG_MESSAGE_TYPE => 0,
    // if error_log_message_type equals 3, the message is appended to this file destination (path and name);
    // keep this path trusted and outside the public web directory
    Logger::CONF_LOGGING_FILE => '',
    // verbosity: log up to the level set here, default=5 = debug; level 6 = speed is ignored by default
    Logger::CONF_LOGGING_LEVEL => 5,
    // rename or renumber, if needed
    Logger::CONF_LOGGING_LEVEL_NAME => array(0 => 'unknown', 1 => 'fatal', 'error', 'warning', 'info', 'debug', 'speed'),
    // minimum configured verbosity needed to log page-speed messages, i.e. messages with error_number 6
    Logger::CONF_LOGGING_LEVEL_PAGE_SPEED => 5,
    // false => use logging_file with log extension as destination; true => adds .Y-m.log to the logging file
    Logger::CONF_LOG_MONTHLY_ROTATION => true,
    // prefix message that took longer than profiling step (float) sec from the previous one by SLOWSTEP
    Logger::CONF_LOG_PROFILING_STEP => false,
    // fatal error may just be written in log, on production, it is however recommended to set an e-mail, where to announce fatal errors
    Logger::CONF_MAIL_FOR_ADMIN_ENABLED => false,
);
$logger = new Logger($conf);
```

See [test.php](test.php) for usage.

Security note: `logging_file` is a filesystem destination. Keep it under application control, do not fill it directly from user input, environment values, or request data without validation, and prefer a directory outside the public web root so logs cannot be downloaded by clients.

When using `log()` directly, pass a standard PSR-3 string level such as `LogLevel::INFO` or a numeric level from `CONF_LOGGING_LEVEL_NAME`. The optional context key `error_number`, or the first numeric context value, selects the category written in the log prefix; other PSR-3 context values are left untouched and are not interpolated into the message.

By default the logger logs the following levels of information:

- fatal
- error
- warning
- info
- debug

And ignores

- speed

Note: Outputting log messages to the screen is not supported.

## Runtime adjustment

- method logAtLeastToLevel(int $level) may raise the verbosity level above the level set when instantiating.
- method setUser(int|string $user) may add the user identification to the error messages

## Tracy\Logger::log wrapper

Since Tracy 2.6.0 it is possible to use a PSR-3 adapter, allowing integration with [seablast/logger](https://github.com/WorkOfStan/seablast-logger). This package currently keeps `"php": ">=7.2 <8.6"`.

```php
$logger = new \Seablast\Logger\Logger();
$tracyLogger = new \Tracy\Bridges\Psr\PsrToTracyLoggerAdapter($logger);
Debugger::setLogger($tracyLogger);
Debugger::enable();
```
