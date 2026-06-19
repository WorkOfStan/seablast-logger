<?php

declare(strict_types=1);

namespace Seablast\Logger\Tests;

use Seablast\Logger\LoggerTime;

/**
 * Helps LoggerTest.php assert log output with deterministic timestamps.
 */
class FixedLoggerTime extends LoggerTime
{
    /**
     * @return float
     */
    public function getmicrotime(): float
    {
        return 100.1234;
    }

    /**
     * @return float
     */
    public function getPageTimestamp(): float
    {
        return 100.0;
    }
}
