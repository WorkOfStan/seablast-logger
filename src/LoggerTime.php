<?php

namespace Seablast\Logger;

class LoggerTime
{
    /** @var ?float */
    private $pageTimestamp = null;

    public function __construct()
    {
        $this->pageTimestamp = $this->getmicrotime(); // initialisation
    }

    /**
     * Initiation of $page_timestamp must be the first thing a page will do
     * Store "time" for benchmarking.
     * Inspired by sb_functions.php in sphpblog
     *
     * @return float
     */
    public function getmicrotime()
    {
        if (version_compare((string) phpversion(), '5.0.0') == -1) {
            list($usec, $sec) = explode(' ', microtime());
            return ((float) $usec + (float) $sec);
        }
        return microtime(true);
    }

    /**
     *
     * @return float
     */
    public function getPageTimestamp()
    {
        if (is_null($this->pageTimestamp)) {
            $this->pageTimestamp = $this->getmicrotime(); // initialisation, so that it can't return null
        }
        return $this->pageTimestamp;
    }

    /**
     * Note: 111105, because $RUNNING_TIME got updated only when my_error_log makes a row
     *
     * @return float
     */
    public function getRunningTime()
    {
        return round($this->getmicrotime() - $this->pageTimestamp, 4);
    }

    /**
     * If called with 'Page Generated in %s seconds', it returns "Page Generated in x.xxxx seconds"
     *
     * @param string $langStringPageGeneratedIn instead of $backyardLangString['page_generated_in']
     * @return string
     */
    public function pageGeneratedIn($langStringPageGeneratedIn = '%s')
    {
        return str_replace(
            '%s',
            (string) round($this->getmicrotime() - $this->pageTimestamp, 4),
            $langStringPageGeneratedIn
        );
    }
}
