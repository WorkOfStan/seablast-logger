<?php

declare(strict_types=1);

namespace Seablast\Logger\Tests;

use PHPUnit\Framework\TestCase;
use Psr\Log\InvalidArgumentException;
use Seablast\Logger\Logger;
use Seablast\Logger\LoggerTime;

class LoggerTest extends TestCase
{
    /** @var string */
    private $tmpDir;

    /** @var array<string,mixed> */
    private $serverBackup;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        $this->tmpDir = dirname(__DIR__) . DIRECTORY_SEPARATOR . '.tmp' . DIRECTORY_SEPARATOR
            . 'phpunit-logger-' . str_replace('.', '', uniqid('', true));
        mkdir($this->tmpDir, 0777, true);

        $this->serverBackup = $_SERVER;
        unset($_SERVER['REMOTE_ADDR']);
        $_SERVER['SCRIPT_FILENAME'] = 'test-script.php';
        $_SERVER['REQUEST_URI'] = '/test-uri';
    }

    /**
     * @return void
     */
    protected function tearDown(): void
    {
        $_SERVER = $this->serverBackup;
        $this->removeDirectory($this->tmpDir);
    }

    /**
     * @return void
     */
    public function testInfoWritesToConfiguredFile(): void
    {
        $baseFile = $this->tmpDir . DIRECTORY_SEPARATOR . 'logger';
        $logger = $this->createFileLogger($baseFile, 4);
        $logger->setUser('user-42');

        $logger->info('Hello log', ['error_number' => 21]);

        $contents = file_get_contents($baseFile . '.log');
        self::assertIsString($contents);
        self::assertStringContainsString(
            '] [info] [21] [test-script.php] [user-42@-] [0.1234] [/test-uri] Hello log',
            $contents
        );
    }

    /**
     * @return void
     */
    public function testLogAtLeastToLevelRaisesVerbosity(): void
    {
        $baseFile = $this->tmpDir . DIRECTORY_SEPARATOR . 'logger';
        $logger = $this->createFileLogger($baseFile, 2);

        $logger->debug('Hidden debug');
        self::assertFalse(file_exists($baseFile . '.log'));

        $logger->logAtLeastToLevel(5);
        $logger->debug('Visible debug');

        $contents = file_get_contents($baseFile . '.log');
        self::assertIsString($contents);
        self::assertStringContainsString('Visible debug', $contents);
    }

    /**
     * @return void
     */
    public function testSpeedLevelIsIgnoredByDefault(): void
    {
        $baseFile = $this->tmpDir . DIRECTORY_SEPARATOR . 'logger';
        $logger = $this->createFileLogger($baseFile, 5);

        $logger->log(6, 'Speed level without page-speed error number');

        self::assertFalse(file_exists($baseFile . '.log'));
    }

    /**
     * @return void
     */
    public function testNonNumericContextDoesNotBreakPsrContext(): void
    {
        $baseFile = $this->tmpDir . DIRECTORY_SEPARATOR . 'logger';
        $logger = $this->createFileLogger($baseFile, 4);

        $logger->info('Context is safe', ['exception' => new \RuntimeException('boom')]);

        $contents = file_get_contents($baseFile . '.log');
        self::assertIsString($contents);
        self::assertStringContainsString('] [info] [0] [test-script.php]', $contents);
    }

    /**
     * @return void
     */
    public function testUnsupportedPsrLevelThrows(): void
    {
        $baseFile = $this->tmpDir . DIRECTORY_SEPARATOR . 'logger';
        $logger = $this->createFileLogger($baseFile, 5);

        $this->expectException(InvalidArgumentException::class);

        $logger->log('verbose', 'Unsupported PSR-3 level');
    }

    /**
     * @param string $baseFile
     * @param int $level
     *
     * @return Logger
     */
    private function createFileLogger(string $baseFile, int $level): Logger
    {
        return new Logger(
            [
                Logger::CONF_ERROR_LOG_MESSAGE_TYPE => 3,
                Logger::CONF_LOGGING_FILE => $baseFile,
                Logger::CONF_LOGGING_LEVEL => $level,
                Logger::CONF_LOG_MONTHLY_ROTATION => false,
            ],
            new FixedLoggerTime()
        );
    }

    /**
     * @return void
     */
    private function removeDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        $items = scandir($dir);
        if ($items === false) {
            return;
        }

        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }
            $path = $dir . DIRECTORY_SEPARATOR . $item;
            if (is_dir($path)) {
                $this->removeDirectory($path);
                continue;
            }
            @unlink($path);
        }

        for ($attempt = 0; $attempt < 3; $attempt++) {
            clearstatcache(true, $dir);
            if (!is_dir($dir) || @rmdir($dir)) {
                return;
            }
            usleep(10000);
        }
    }
}

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
