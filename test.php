<?php

require 'vendor/autoload.php';

// Initialize the logger
$conf = array(
    'error_log_message_type' => 3,
    'logging_file' => './error_log',
    'logging_level' => 0, // for purpose of test looping
    'logging_level_page_speed' => 5,
    'log_monthly_rotation' => true,
    'log_profiling_step' => 0.001,
    'mail_for_admin_enabled' => false,
);
$logger = new Seablast\Logger\Logger($conf);

// Loop through levels 1 to 5
for ($level = 1; $level <= 5; $level++) {
    // Set the logging level
    echo "<h1>logAtLeastToLevel($level)</h1>";
    $logger->logAtLeastToLevel($level);

    foreach (['emergency', 'alert', 'critical', 'error', 'warning', 'notice', 'info', 'debug'] as $severity) {
        // Display all severities
        echo $severity . '<br>';

        // Only the allowed severity gets actually logged
        $logger->$severity("Log level limited to {$level}. Logged with severity level {$severity}.");
    }
}
