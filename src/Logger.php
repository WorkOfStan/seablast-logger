<?php

namespace Seablast\Logger;

use Psr\Log\AbstractLogger;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Seablast\Logger\LoggerTime;

/**
 * A [PSR-3](http://www.php-fig.org/psr/psr-3/) compliant logger with adjustable verbosity.
 */
class Logger extends AbstractLogger implements LoggerInterface
{
    // Define constants for configuration keys
    public const CONF_ERROR_LOG_MESSAGE_TYPE = 'error_log_message_type';
    const CONF_LOGGING_FILE = 'logging_file';
    const CONF_LOGGING_LEVEL = 'logging_level';
    const CONF_LOGGING_LEVEL_NAME = 'logging_level_name';
    const CONF_LOGGING_LEVEL_PAGE_SPEED = 'logging_level_page_speed';
    const CONF_LOG_MONTHLY_ROTATION = 'log_monthly_rotation';
    const CONF_LOG_PROFILING_STEP = 'log_profiling_step';
    const CONF_MAIL_FOR_ADMIN_ENABLED = 'mail_for_admin_enabled';

    /** @var array<mixed> int,string,bool,array */
    protected $conf = array();
    /** @var int */
    private $overrideLoggingLevel;
    /** @var float */
    private $runningTime = 0;
    /** @var LoggerTime */
    protected $time;
    /** @var string*/
    private $user = 'unidentified';

    /**
     *
     * @param array<mixed> $conf
     * @param LoggerTime $time
     */
    public function __construct(array $conf = array(), LoggerTime $time = null)
    {
        $this->time = ($time === null) ? (new LoggerTime()) : $time;
        $this->conf = array_merge(
            array( // default values
                // 0 = send message to PHP's system logger;
                // recommended is however 3, i.e. append to the file destination set in the field 'logging_file'
                self::CONF_ERROR_LOG_MESSAGE_TYPE => 0,
                // if error_log_message_type equals 3, the message is appended to this file destination (path and name)
                self::CONF_LOGGING_FILE => '',
                // verbosity: log up to the level set here, default=5 = debug
                self::CONF_LOGGING_LEVEL => 5,
                // rename or renumber, if needed
                self::CONF_LOGGING_LEVEL_NAME => array(
                    0 => 'unknown',
                    1 => 'fatal',
                    'error',
                    'warning',
                    'info',
                    'debug',
                    'speed'
                ),
                // the logging level to which the page generation speed (i.e. error_number 6) is to be logged
                self::CONF_LOGGING_LEVEL_PAGE_SPEED => 5,
                // false => use logging_file with log extension as destination
                // true => adds .Y-m.log to the logging file
                self::CONF_LOG_MONTHLY_ROTATION => true,
                // prefix message that took longer than profiling step (float) sec from the previous one by SLOWSTEP
                self::CONF_LOG_PROFILING_STEP => false,
                // A fatal error may be logged only.
                // However, in production, set up an email address to enable error notifications.
                self::CONF_MAIL_FOR_ADMIN_ENABLED => false,
            ),
            $conf
        );
        if (!is_int($this->conf[self::CONF_LOGGING_LEVEL])) {
            throw new \Psr\Log\InvalidArgumentException('The logging_level MUST be an integer.');
        }
        $this->overrideLoggingLevel = $this->conf[self::CONF_LOGGING_LEVEL];
        //@todo replace $this->conf by the class properties
    }

    /**
     * Class doesn't automatically use any GET parameter to override the set logging level,
     * as it could be used to flood the error log.
     * It is however possible to programmatically raise the logging level set in configuration.
     *
     * @param int $newLevel
     * @return void
     */
    public function logAtLeastToLevel(int $newLevel)
    {
        if (!is_int($newLevel)) {
            throw new \Psr\Log\InvalidArgumentException('The variable $newLevel is not an integer.');
        }
        $this->overrideLoggingLevel = (int) $newLevel;
    }

    /**
     * @return float
     */
    public function getLastRunningTime()
    {
        return $this->runningTime;
    }

    /**
     * DI setter.
     *
     * @param int|string $user
     * @return void
     */
    public function setUser($user)
    {
        $this->user = (string) $user;
    }

    /**
     * System is unusable.
     *
     * @param string $message
     * @param array<int> $context
     * @return void
     */
    public function emergency($message, array $context = array()): void
    {
        $this->log(0, $message, $context);
    }

    /**
     * Action must be taken immediately.
     *
     * Example: Entire website down, database unavailable, etc. This should
     * trigger the SMS alerts and wake you up.
     *
     * @param string $message
     * @param array<int> $context
     * @return void
     */
    public function alert($message, array $context = array()): void
    {
        $this->log(1, $message, $context);
    }

    /**
     * Critical conditions.
     *
     * Example: Application component unavailable, unexpected exception.
     *
     * @param string $message
     * @param array<int> $context
     * @return void
     */
    public function critical($message, array $context = array()): void
    {
        $this->log(1, $message, $context);
    }

    /**
     * Runtime errors that do not require immediate action but should typically
     * be logged and monitored.
     *
     * @param string $message
     * @param array<int> $context
     * @return void
     */
    public function error($message, array $context = array()): void
    {
        $this->log(2, $message, $context);
    }

    /**
     * Exceptional occurrences that are not errors.
     *
     * Example: Use of deprecated APIs, poor use of an API, undesirable things
     * that are not necessarily wrong.
     *
     * @param string $message
     * @param array<int> $context
     * @return void
     */
    public function warning($message, array $context = array()): void
    {
        $this->log(3, $message, $context);
    }

    /**
     * Normal but significant events.
     *
     * @param string $message
     * @param array<int> $context
     * @return void
     */
    public function notice($message, array $context = array()): void
    {
        $this->log(4, $message, $context);
    }

    /**
     * Interesting events.
     *
     * Example: User logs in, SQL logs.
     *
     * @param string $message
     * @param array<int> $context
     * @return void
     */
    public function info($message, array $context = array())
    {
        $this->log(4, $message, $context);
    }

    /**
     * Detailed debug information.
     *
     * @param string $message
     * @param array<int> $context
     * @return void
     */
    public function debug($message, array $context = array())
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
     * @param string $message Message to be logged
     * @param array<int> $context OPTIONAL To enable error log filtering 'error_number' field expected
     *   or the first element element expected containing number of error category
     *
     * @return void
     *
     * <b>ERROR NUMBER LIST</b>
     *  0 Unspecified<br/>
     *  1-5 Reserved
     *  6 Speed<br/>
     *  7-9 Reserved<br/>
     *  10 Authentization<br/>
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
     *
     */
    public function log($level, $message, array $context = array())
    {
        //TODO add variable $line - it should always be called as basename(__FILE__)."#".__LINE__ ,
        //so it's clear which line of the source code triggered the call
        if (!is_string($message)) {
            $message = "wrong message type " . gettype($message) . ": Logger->log(" . print_r($level, true) . ","
                . print_r($message, true) . ")";
            $this->error($message);
        }
        // psr log levels to numbered severity
        $psr2int = array(
            LogLevel::EMERGENCY => 0,
            LogLevel::ALERT     => 1,
            LogLevel::CRITICAL  => 1,
            LogLevel::ERROR     => 2,
            LogLevel::WARNING   => 3,
            LogLevel::NOTICE    => 4,
            LogLevel::INFO      => 4,
            LogLevel::DEBUG     => 5,
        );
        if (is_string($level)) {
            if (array_key_exists($level, $psr2int)) {
                $level = $psr2int[$level];
            } else {
                $this->error('level has unexpected string value ' . $level . ' message: ' . $message);
                $level = 0;
            }
        } elseif (!is_int($level)) {
            $this->error('level has unexpected type ' . gettype($level) . ' message: ' . $message);
            $level = 0;
        }

        // if context array is set then get the value of the 'error_number' field or the first element
        $error_number = ($context === array())
            ? 0
            : (isset($context['error_number']) ? (int) $context['error_number'] : (int) reset($context));

        $result = true; //it could eventually be reset to false after calling error_log()

        if (
            // log 0=unknown/default 1=fatal 2=error 3=warning 4=info 5=debug 6=speed according to $level
            (
                $level <= max(
                    array(
                        $this->conf[self::CONF_LOGGING_LEVEL],
                        $this->overrideLoggingLevel,
                    )
                )
            )
            // or log page_speed everytime error_number equals 6 and
            // logging_level_page_speed has at least the severity of logging_level
            || (
                ($error_number === 6)
                && ($this->conf[self::CONF_LOGGING_LEVEL_PAGE_SPEED] <= $this->conf[self::CONF_LOGGING_LEVEL])
            )
        ) {
            $RUNNING_TIME_PREVIOUS = $this->runningTime;
            if (
                (
                    (($this->runningTime = round($this->time->getmicrotime() - $this->time->getPageTimestamp(), 4))
                    - $RUNNING_TIME_PREVIOUS) > $this->conf[self::CONF_LOG_PROFILING_STEP]
                ) && $this->conf[self::CONF_LOG_PROFILING_STEP]
            ) {
                $message = "SLOWSTEP " . $message; //110812, PROFILING
            }

            $message_prefix = "[" . date("d-M-Y H:i:s") . "] [" . $this->conf[self::CONF_LOGGING_LEVEL_NAME][$level]
                . "] [" . $error_number . "] [" . $_SERVER['SCRIPT_FILENAME'] . "] ["
                . $this->user . "@"
                // PHPUnit test (CLI) does not set REMOTE_ADDR
                // TODO what if gethostbyaddr can't resolve the IP? And wouldn't be faster to log IP?
                . (isset($_SERVER['REMOTE_ADDR']) ? gethostbyaddr($_SERVER['REMOTE_ADDR']) : '-')
                . "] [" . $this->runningTime . "] ["
                // PHPUnit test (CLI) does not set REQUEST_URI
                . (isset($_SERVER["REQUEST_URI"]) ? $_SERVER["REQUEST_URI"] : '-')
                . "] ";
            // $logging_file not set and it should be
            if (($this->conf[self::CONF_ERROR_LOG_MESSAGE_TYPE] == 3) && !$this->conf[self::CONF_LOGGING_FILE]) {
                // so write into the default destination
                $result = error_log($message_prefix . "(error: logging_file should be set!) $message");
            } else {
                $messageType = ($this->conf[self::CONF_ERROR_LOG_MESSAGE_TYPE] == 0)
                    ? $this->conf[self::CONF_ERROR_LOG_MESSAGE_TYPE] : 3;
                $result = ($this->conf[self::CONF_LOG_MONTHLY_ROTATION])
                    ? error_log(
                        $message_prefix . $message . (($messageType != 0) ? (PHP_EOL) : ('')),
                        $messageType,
                        "{$this->conf[self::CONF_LOGGING_FILE]}." . date("Y-m") . ".log"
                    ) // writes into a monthly rotating file
                    : error_log(
                        $message_prefix . $message . PHP_EOL,
                        $messageType,
                        "{$this->conf[self::CONF_LOGGING_FILE]}.log"
                    ); // writes into one file
            }
            // mailto admin. 'mail_for_admin_enabled' has to be an email
            if ($level == 1 && $this->conf[self::CONF_MAIL_FOR_ADMIN_ENABLED]) {
                error_log($message_prefix . $message . PHP_EOL, 1, $this->conf[self::CONF_MAIL_FOR_ADMIN_ENABLED]);
            }
        }
        if ($result === false) {
            throw new ErrorLogFailureException('error_log() failed');
        }
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
