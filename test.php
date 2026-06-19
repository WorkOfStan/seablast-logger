<?php

declare(strict_types=1);

use Seablast\Logger\Logger;

require 'vendor/autoload.php';

$logDir = __DIR__ . '/log';
if (!is_dir($logDir) && !mkdir($logDir, 0750, true) && !is_dir($logDir)) {
    throw new RuntimeException(sprintf('Unable to create log directory "%s".', $logDir));
}
if (!is_writable($logDir)) {
    throw new RuntimeException(sprintf('Log directory "%s" is not writable.', $logDir));
}
$logFile = $logDir . '/error_log';

// Initialize the logger
$conf = [
    Logger::CONF_ERROR_LOG_MESSAGE_TYPE => 3,
    Logger::CONF_LOGGING_FILE => $logFile, // extension .log will be added automatically
    Logger::CONF_LOGGING_LEVEL => 0, // start with logging almost nothing for purpose of test looping
    Logger::CONF_LOGGING_LEVEL_PAGE_SPEED => 5,
    Logger::CONF_LOG_MONTHLY_ROTATION => true,
    Logger::CONF_LOG_PROFILING_STEP => 0.00048,
    Logger::CONF_MAIL_FOR_ADMIN_ENABLED => false,
];
$logger = new Logger($conf);

echo "<h1>Compare to what appears in the error log: {$conf[Logger::CONF_LOGGING_FILE]}" . date('Y-m') . ".log</h1>";
$severities = ['emergency', 'alert', 'critical', 'error', 'warning', 'notice', 'info', 'debug'];
// Loop through levels 1 to 5
for ($level = 1; $level <= 5; $level++) {
    // Set the logging level
    echo "<h2>logAtLeastToLevel({$level})</h2>";
    $logger->logAtLeastToLevel($level);

    foreach ($severities as $severity) {
        // Display all severities
        echo $severity . '<br>';

        // Only the allowed severity gets actually logged
        $logger->$severity("Log level limited to {$level}. Logged with severity level {$severity}.");
    }
}
