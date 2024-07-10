<?php

namespace Seablast\Logger;
  
use Tracy\ILogger;

/**
 * Usage: Tracy\Debugger::setLogger(new SeablastLogger);
 */
class SeablastLogger implements ILogger
{
	public function log($value, $priority = ILogger::INFO)
	{
		// sends a request to Seablast\Logger
	}
}
