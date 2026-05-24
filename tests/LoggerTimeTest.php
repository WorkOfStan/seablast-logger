<?php

namespace Seablast\Logger\Tests;

use PHPUnit\Framework\TestCase;
use Seablast\Logger\LoggerTime;

/**
 * Covers LoggerTime elapsed-time helpers.
 */
class LoggerTimeTest extends TestCase
{
    /** @var LoggerTime */
    protected $object;

    /**
     * Sets up a fresh timer fixture before each test.
     *
     * @return void
     */
    protected function setUp(): void
    {
        error_reporting(E_ALL); // incl E_NOTICE
        $this->object = new LoggerTime();
    }

    /**
     * @covers Seablast\Logger\LoggerTime::getRunningTime
     *
     * @return void
     */
    public function testGetRunningTime(): void
    {
        $expected = '0.0';

        //$this->assertEquals($expected, substr((string) $this->object->getRunningTime(), 0, 3));
        $this->assertEquals((float) $expected, (float) substr((string) $this->object->getRunningTime(), 0, 3));
    }

    /**
     * @covers Seablast\Logger\LoggerTime::pageGeneratedIn
     *
     * @return void
     */
    public function testPageGeneratedInDefault(): void
    {
        $expected = '0.0';

        //$this->assertEquals($expected, substr($this->object->pageGeneratedIn(), 0, 3));
        $this->assertEquals((float) $expected, (float) substr((string) $this->object->pageGeneratedIn(), 0, 3));
    }

    /**
     * @return void
     */
    public function testPageGeneratedInLangString(): void
    {
        $langStringPageGeneratedIn = "Page Generated in %s";
        $expected = 'Page Generated in 0';

        $this->assertEquals(
            $expected,
            substr($this->object->pageGeneratedIn($langStringPageGeneratedIn), 0, strlen($expected))
        );
    }
}
