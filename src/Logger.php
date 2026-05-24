<?php

declare(strict_types=1);

namespace Seablast\Logger;

use Psr\Log\AbstractLogger;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Webmozart\Assert\Assert;

/**
 * A [PSR-3](http://www.php-fig.org/psr/psr-3/) compliant logger with adjustable verbosity.
 *
 * Note: Until PHP 7.3, the parameter #1 $message in methods implementing `Psr\Log\AbstractLogger` is of type `mixed`.
 */
class Logger extends AbstractLogger implements LoggerInterface
{
    // Define constants for configuration keys
    public const CONF_ERROR_LOG_MESSAGE_TYPE = 'error_log_message_type';
    public const CONF_LOGGING_FILE = 'logging_file';
    public const CONF_LOGGING_LEVEL = 'logging_level';
    public const CONF_LOGGING_LEVEL_NAME = 'logging_level_name';
    public const CONF_LOGGING_LEVEL_PAGE_SPEED = 'logging_level_page_speed';
    public const CONF_LOG_MONTHLY_ROTATION = 'log_monthly_rotation';
    public const CONF_LOG_PROFILING_STEP = 'log_profiling_step';
    public const CONF_MAIL_FOR_ADMIN_ENABLED = 'mail_for_admin_enabled';

    /** @var array<string,int> psr log levels to numbered severity */
    private const PSR_LEVEL_TO_LOGGING_LEVEL = [
        LogLevel::EMERGENCY => 0,
        LogLevel::ALERT => 1,
        LogLevel::CRITICAL => 1,
        LogLevel::ERROR => 2,
        LogLevel::WARNING => 3,
        LogLevel::NOTICE => 4,
        LogLevel::INFO => 4,
        LogLevel::DEBUG => 5,
    ];

    /** @var int 0 = send message to PHP's system logger; recommended is however 3 (append to file) */
    private $errorLogMessageType = 0;
    /** @var string if errorLogMessageType equals 3, message is appended to this file destination (path and name) */
    private $loggingFile = '';
    /** @var int verbosity: log up to this level, default=5 (debug) */
    private $loggingLevel = 5;
    /** @var array<int,string> Note: rename or renumber, if needed */
    private $loggingLevelName = [
        0 => 'unknown',
        1 => 'fatal',
        2 => 'error',
        3 => 'warning',
        4 => 'info',
        5 => 'debug',
        6 => 'speed',
    ];
    /** @var int the logging level to which page generation speed (error_number 6) is to be logged */
    private $loggingLevelPageSpeed = 5;
    /** @var bool false => use loggingFile as destination; true => adds .Y-m.log suffix for monthly rotation */
    private $logMonthlyRotation = true;
    /** @var bool|float prefix message that took longer than profiling step (float seconds) by SLOWSTEP */
    private $logProfilingStep = false;
    /** @var bool|string when string, treated as admin email for level <=1 notifications */
    private $mailForAdminEnabled = false;

    /** @var int */
    private $overrideLoggingLevel;
    /** @var float */
    private $runningTime = 0;
    /** @var LoggerTime */
    protected $time;
    /** @var string */
    private $user = 'unidentified';

    /**
     * Config is keyed by CONF_* string constants, so array<string,mixed> lets static analysis
     * reject positional integer-keyed config where array<mixed> would only describe values.
     * @param array<string,mixed> $conf
     * @param ?LoggerTime $time
     */
    public function __construct(array $conf = [], ?LoggerTime $time = null)
    {
        $this->time = ($time === null) ? (new LoggerTime()) : $time;

        // prefer explicit property defaults; only override when config key is provided
        if (isset($conf[self::CONF_ERROR_LOG_MESSAGE_TYPE])) {
            $v = $conf[self::CONF_ERROR_LOG_MESSAGE_TYPE];
            if (!is_int($v) && !is_numeric($v)) {
                throw new \Psr\Log\InvalidArgumentException('The error_log_message_type MUST be an integer.');
            }
            $this->errorLogMessageType = (int) $v;
        }
        if (isset($conf[self::CONF_LOGGING_FILE])) {
            Assert::string($conf[self::CONF_LOGGING_FILE], 'The logging_file MUST be a string.');
            $this->loggingFile = (string) $conf[self::CONF_LOGGING_FILE];
        }
        if (isset($conf[self::CONF_LOGGING_LEVEL])) {
            if (!is_int($conf[self::CONF_LOGGING_LEVEL])) {
                throw new \Psr\Log\InvalidArgumentException('The logging_level MUST be an integer.');
            }
            $this->loggingLevel = (int) $conf[self::CONF_LOGGING_LEVEL];
        }
        if (isset($conf[self::CONF_LOGGING_LEVEL_NAME])) {
            if (!is_array($conf[self::CONF_LOGGING_LEVEL_NAME])) {
                throw new \Psr\Log\InvalidArgumentException('The logging_level_name MUST be an array.');
            }
            # normalize to array<int,string>
            $normalized = [];
            foreach ($conf[self::CONF_LOGGING_LEVEL_NAME] as $k => $v) {
                Assert::string($v, 'Each logging level name MUST be a string.');
                $normalized[(int) $k] = (string) $v;
            }
            $this->loggingLevelName = $normalized;
        }
        if (isset($conf[self::CONF_LOGGING_LEVEL_PAGE_SPEED])) {
            Assert::integerish(
                $conf[self::CONF_LOGGING_LEVEL_PAGE_SPEED],
                'The logging_level_page_speed MUST be an integer.'
            );
            $this->loggingLevelPageSpeed = (int) $conf[self::CONF_LOGGING_LEVEL_PAGE_SPEED];
        }
        if (isset($conf[self::CONF_LOG_MONTHLY_ROTATION])) {
            $this->logMonthlyRotation = (bool) $conf[self::CONF_LOG_MONTHLY_ROTATION];
        }
        if (isset($conf[self::CONF_LOG_PROFILING_STEP])) {
            $v = $conf[self::CONF_LOG_PROFILING_STEP];
            if (!is_bool($v) && !is_float($v) && !is_int($v)) {
                throw new \Psr\Log\InvalidArgumentException('The log_profiling_step MUST be bool or float.');
            }
            $this->logProfilingStep = $v;
        }
        if (isset($conf[self::CONF_MAIL_FOR_ADMIN_ENABLED])) {
            $v = $conf[self::CONF_MAIL_FOR_ADMIN_ENABLED];
            if (!is_bool($v) && !is_string($v)) {
                throw new \Psr\Log\InvalidArgumentException(
                    'The mail_for_admin_enabled MUST be bool or string (email).'
                );
            }
            $this->mailForAdminEnabled = $v;
        }

        $this->overrideLoggingLevel = $this->loggingLevel;
    }

    /**
     * Class doesn't automatically use any GET parameter to override the set logging level,
     * as it could be used to flood the error log.
     * It is however possible to programmatically raise the logging level set in configuration.
     *
     * @param int $newLevel
     *
     * @return void
     */
    public function logAtLeastToLevel(int $newLevel): void
    {
        $this->overrideLoggingLevel = (int) $newLevel;
    }

    /**
     * @return float
     */
    public function getLastRunningTime(): float
    {
        return $this->runningTime;
    }

    /**
     * DI setter.
     *
     * @param int|string $user
     *
     * @return void
     */
    public function setUser($user): void
    {
        $this->user = (string) $user;
    }

    /**
     * System is unusable.
     *
     * @param mixed $message PSR-3 message string or stringable object.
     * @param array<int|string,mixed> $context
     *
     * @return void
     */
    public function emergency($message, array $context = []): void
    {
        $this->log(0, $message, $context);
    }

    /**
     * Action must be taken immediately.
     *
     * Example: Entire website down, database unavailable, etc. This should
     * trigger the SMS alerts and wake you up.
     *
     * @param mixed $message PSR-3 message string or stringable object.
     * @param array<int|string,mixed> $context
     *
     * @return void
     */
    public function alert($message, array $context = []): void
    {
        $this->log(1, $message, $context);
    }

    /**
     * Critical conditions.
     *
     * Example: Application component unavailable, unexpected exception.
     *
     * @param mixed $message PSR-3 message string or stringable object.
     * @param array<int|string,mixed> $context
     *
     * @return void
     */
    public function critical($message, array $context = []): void
    {
        $this->log(1, $message, $context);
    }

    /**
     * Runtime errors that do not require immediate action but should typically
     * be logged and monitored.
     *
     * @param mixed $message PSR-3 message string or stringable object.
     * @param array<int|string,mixed> $context
     *
     * @return void
     */
    public function error($message, array $context = []): void
    {
        $this->log(2, $message, $context);
    }

    /**
     * Exceptional occurrences that are not errors.
     *
     * Example: Use of deprecated APIs, poor use of an API, undesirable things
     * that are not necessarily wrong.
     *
     * @param mixed $message PSR-3 message string or stringable object.
     * @param array<int|string,mixed> $context
     *
     * @return void
     */
    public function warning($message, array $context = []): void
    {
        $this->log(3, $message, $context);
    }

    /**
     * Normal but significant events.
     *
     * @param mixed $message PSR-3 message string or stringable object.
     * @param array<int|string,mixed> $context
     *
     * @return void
     */
    public function notice($message, array $context = []): void
    {
        $this->log(4, $message, $context);
    }

    /**
     * Interesting events.
     *
     * Example: User logs in, SQL logs.
     *
     * @param mixed $message PSR-3 message string or stringable object.
     * @param array<int|string,mixed> $context
     *
     * @return void
     */
    public function info($message, array $context = []): void
    {
        $this->log(4, $message, $context);
    }

    /**
     * Detailed debug information.
     *
     * @param mixed $message PSR-3 message string or stringable object.
     * @param array<int|string,mixed> $context
     *
     * @return void
     */
    public function debug($message, array $context = []): void
    {
        $this->log(5, $message, $context);
    }

    /**
     * Error_log() modified to log necessary debug information by application to its own log.
     * Logs with an arbitrary verbosity level, e.g. debug info on production may be omitted.
     * Compliant with PSR-3 http://www.php-fig.org/psr/psr-3/
     *
     * Following gets written to log:
     * [Timestamp: d-M-Y H:i:s] [Logging level] [$error_number] [$_SERVER['SCRIPT_FILENAME']]
     * [username@gethostbyaddr($_SERVER['REMOTE_ADDR'])] [sec since page start] $message
     *
     * @param mixed $level int|string Error level
     * @param mixed $message Message to be logged; PSR-3 message string or stringable object.
     * @param array<int|string,mixed> $context OPTIONAL To enable error log filtering 'error_number' field expected
     *   or the first numeric element expected containing number of error category
     *
     * @return void
     *
     * <b>ERROR NUMBER LIST</b>
     *  0 Unspecified<br/>
     *  1-5 Reserved
     *  6 Speed<br/>
     *  7-9 Reserved<br/>
     *  10 Authentication<br/>
     *  11 MySQL<br/>
     *  12 Domain name<br/>
     *  13 Tampered URL or ID<br/>
     *  14 Improve this functionality<br/>
     *  15 Page was refreshed with the same URL therefore action imposed by URL is ignored<br/>
     *  16 Logging values<br/>
     *  17 Missing input value<br/>
     *  18 Setting of a system value<br/>
     *  19 Redirecting<br/>
     *  20 Facebook API<br/>
     *  21 HTTP communication<br/>
     *  22 E-mail<br/>
     *  23 Algorithm flow<br/>
     *  24 Third party API<br/>
     *  1001 Establish correct error_number
     */
    public function log($level, $message, array $context = []): void
    {
        //TODO add variable $line - it should always be called as basename(__FILE__)."#".__LINE__ ,
        //so it's clear which line of the source code triggered the call
        //if (!is_string($message)) {
        //    $message = 'wrong message type ' . gettype($message) . ': Logger->log(' . print_r($level, true) . ','
        //        . print_r($message, true) . ')';
        //    $this->error($message);
        //}
        $level = $this->normalizeLevel($level);
        $message = $this->normalizeMessage($message);

        // if context array is set then get the value of the 'error_number' field or the first element
        $error_number = $this->getContextErrorNumber($context);

        if (
            // log 0=unknown/default 1=fatal 2=error 3=warning 4=info 5=debug 6=speed according to $level
            (
                $level <= max($this->loggingLevel, $this->overrideLoggingLevel)
            )
            // or log page_speed everytime error_number equals 6 and
            // logging_level_page_speed has at least the severity of logging_level
            || (
                ($error_number === 6)
                && ($this->loggingLevelPageSpeed <= $this->loggingLevel)
            )
        ) {
            $RUNNING_TIME_PREVIOUS = $this->runningTime;
            if (
                (
                    (($this->runningTime = round($this->time->getmicrotime() - $this->time->getPageTimestamp(), 4))
                    - $RUNNING_TIME_PREVIOUS) > $this->logProfilingStep
                ) && $this->logProfilingStep
            ) {
                $message = 'SLOWSTEP ' . $message; //110812, PROFILING
            }

            $message_prefix = '[' . date('d-M-Y H:i:s') . '] [' . $this->getLoggingLevelName($level)
                . '] [' . $error_number . '] ['
                . ((isset($_SERVER['SCRIPT_FILENAME']) && is_string($_SERVER['SCRIPT_FILENAME'])) //
                ? $_SERVER['SCRIPT_FILENAME'] : 'no-script')
                . '] ['
                . $this->user . '@'
                // PHPUnit test (CLI) does not set REMOTE_ADDR
                // TODO what if gethostbyaddr can't resolve the IP? And wouldn't be faster to just log IP?
                . ((isset($_SERVER['REMOTE_ADDR']) && is_string($_SERVER['REMOTE_ADDR'])) //
                ? gethostbyaddr($_SERVER['REMOTE_ADDR']) : '-')
                . '] [' . $this->runningTime . '] ['
                // PHPUnit test (CLI) does not set REQUEST_URI
                . ((isset($_SERVER['REQUEST_URI']) && is_string($_SERVER['REQUEST_URI'])) //
                ? $_SERVER['REQUEST_URI'] : '-')
                . '] ';
            $result = true; //it could eventually be reset to false after calling error_log()
            // $logging_file not set and it should be
            if (($this->errorLogMessageType == 3) && !$this->loggingFile) {
                // so write into the default destination
                $result = error_log("{$message_prefix}(error: logging_file should be set!) {$message}");
            } else {
                $messageType = ($this->errorLogMessageType === 0)
                    ? $this->errorLogMessageType : 3;
                $result = $this->logMonthlyRotation
                    ? error_log(
                        $message_prefix . $message . (($messageType != 0) ? PHP_EOL : ''),
                        $messageType,
                        "{$this->loggingFile}." . date('Y-m') . '.log'
                    ) // writes into a monthly rotating file
                    : error_log(
                        $message_prefix . $message . PHP_EOL,
                        $messageType,
                        "{$this->loggingFile}.log"
                    ); // writes into one file
            }
            if ($result === false) {
                throw new ErrorLogFailureException('error_log() failed');
            }
            // mailto admin. 'mail_for_admin_enabled' has to be an email
            if (
                $level === 1 && $this->mailForAdminEnabled //
                && is_string($this->mailForAdminEnabled)
            ) {
                $result2 = error_log(
                    $message_prefix . $message . PHP_EOL,
                    1,
                    $this->mailForAdminEnabled
                );
                if ($result2 === false) {
                    throw new ErrorLogFailureException('error_log() mailing failed');
                }
            }
        }
    }

    /**
     * @param mixed $level
     *
     * @return int
     */
    private function normalizeLevel($level): int
    {
        if (is_string($level)) {
            if (array_key_exists($level, self::PSR_LEVEL_TO_LOGGING_LEVEL)) {
                return self::PSR_LEVEL_TO_LOGGING_LEVEL[$level];
            }
            throw new \Psr\Log\InvalidArgumentException('The log level "' . $level . '" is not supported.');
        }
        if (!is_int($level)) {
            throw new \Psr\Log\InvalidArgumentException(
                'The log level type "' . gettype($level) . '" is not supported.'
            );
        }

        return $level;
    }

    /**
     * @param mixed $message
     *
     * @return string
     */
    private function normalizeMessage($message): string
    {
        if (is_string($message)) {
            return $message;
        }

        if (is_object($message) && method_exists($message, '__toString')) {
            return (string) $message;
        }

        throw new \Psr\Log\InvalidArgumentException('The log message MUST be a string or stringable object.');
    }

    /**
     * @param array<int|string,mixed> $context
     *
     * @return int
     */
    private function getContextErrorNumber(array $context): int
    {
        if ($context === []) {
            return 0;
        }

        if (
            isset($context['error_number'])
            && (is_int($context['error_number']) || is_numeric($context['error_number']))
        ) {
            return (int) $context['error_number'];
        }

        $firstValue = reset($context);
        if (is_int($firstValue) || is_numeric($firstValue)) {
            return (int) $firstValue;
        }

        return 0;
    }

    /**
     * @return string
     */
    private function getLoggingLevelName(int $level): string
    {
        if (array_key_exists($level, $this->loggingLevelName)) {
            return $this->loggingLevelName[$level];
        }

        return $this->loggingLevelName[0] ?? 'unknown';
    }

    /** Alternative way:
      Logging levels
      Log level   Description                                                                       Set bit
      Warning     Identifies critical errors.                                                       None required
      Debug       Provides additional information for programmers and Technical Product Support.    0 (zero)
      Information Provides information on the health of the system.                                 1
      Trace       Provides detailed information on the execution of the code.                       2

      Log Mask values and logging levels
      LogMask   Bit value Messages included
      0         00000000  Warnings
      1         00000001  Warnings and Debug
      2         00000010  Warnings and Information
      3         00000011  Warnings, Debug and Information
      4         00000100  Warnings and Trace
      7         00000111  Warnings, Debug, Information and Trace
     */
}
