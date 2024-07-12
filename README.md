# seablast-logger
A [PSR-3](http://www.php-fig.org/psr/psr-3/) compliant logger with adjustable verbosity.

The logging levels can be tailored to suit different environments.
For instance, in a development environment, the logger can be configured to log more detailed information compared to a production environment, all without changing your code.
Simply adjust the verbosity.

The `logging_level` is the most important setting. These parameters can be configured when instantiating the logger:
```php
$conf = array(
    // 0 = send message to PHP's system logger; recommended is however 3, i.e. append to the file destination set in the field 'logging_file'
    'error_log_message_type' => 0,
    // if error_log_message_type equals 3, the message is appended to this file destination (path and name)
    'logging_file' => '',
    // log up to the level set here, default=5 = debug
    'logging_level' => 5,
    // rename or renumber, if needed
    'logging_level_name' => array(0 => 'unknown', 1 => 'fatal', 'error', 'warning', 'info', 'debug', 'speed'),
    // the logging level to which the page generation speed (i.e. error_number 6) is to be logged
    'logging_level_page_speed' => 5,
    // false => use logging_file with log extension as destination; true => adds .Y-m.log to the logging file
    'log_monthly_rotation' => true,
    // prefix message that took longer than profiling step (float) sec from the previous one by SLOWSTEP
    'log_profiling_step' => false,
    // fatal error may just be written in log, on production, it is however recommended to set an e-mail, where to announce fatal errors
    'mail_for_admin_enabled' => false,
);
$logger = new Seablast\Logger\Logger($conf);
```
See [test.php](test.php) for usage.

By default the logger logs the following levels of information:
- fatal
- error
- warning
- info
- debug

And ignores
- speed

## Runtime adjustment
- method logAtLeastToLevel(int $level) may change the verbosity level above the level set when instatiating.
- method setUser(int|string $user) may add the user identification to the error messages

# Tracy\Logger::log wrapper
Since Nette\Tracy::v2.6.0, i.e. `"php": ">=7.1"` it is possible to use a PSR-3 adapter, allowing for integration of [seablast/logger](https://github.com/WorkOfStan/seablast-logger).

```php
$logger = new Seablast\Logger\Logger();
$tracyLogger = new Tracy\Bridges\Psr\PsrToTracyLoggerAdapter($logger);
Debugger::setLogger($tracyLogger);
Debugger::enable();
```
