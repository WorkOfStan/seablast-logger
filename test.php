<?php

use Seablast\Logger\Logger;

require 'vendor/autoload.php';

// Initialize the logger
$conf = [
    Logger::CONF_ERROR_LOG_MESSAGE_TYPE => 3,
    Logger::CONF_LOGGING_FILE => './error_log', // extension .log will be added automatically
    Logger::CONF_LOGGING_LEVEL => 0, // start with logging almost nothing for purpose of test looping
    Logger::CONF_LOGGING_LEVEL_PAGE_SPEED => 5,
    Logger::CONF_LOG_MONTHLY_ROTATION => true,
    Logger::CONF_LOG_PROFILING_STEP => 0.00048,
    Logger::CONF_MAIL_FOR_ADMIN_ENABLED => false,
];
$logger = new Logger($conf);

$severities = ['emergency', 'alert', 'critical', 'error', 'warning', 'notice', 'info', 'debug'];
// Loop through levels 1 to 5
for ($level = 1; $level <= 5; $level++) {
    // Set the logging level
    echo "<h1>logAtLeastToLevel($level)</h1>";
    $logger->logAtLeastToLevel($level);

    foreach ($severities as $severity) {
        // Display all severities
        echo $severity . '<br>';

        // Only the allowed severity gets actually logged
        $logger->$severity("Log level limited to {$level}. Logged with severity level {$severity}.");
    }
}
