<?php

declare(strict_types=1);

/**
 * JGerman GitHub Bot Helper based on the Joomla! Framework
 *
 * @copyright  Copyright (C) 2020 J!German (www.jgerman.de) All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace joomlagerman\Helper;

final class LogHelper
{
	private readonly string $logfile;

	public function __construct(string $logName)
	{
		$this->logfile = ROOT_PATH . '/logs/' . date('Ym') . '_' . $logName . '.log';
	}

	public function writeLogMessage(string $message, ?string $messageType = null): void
	{
		file_put_contents($this->logfile, $this->getLogMessage($message, $messageType), FILE_APPEND | LOCK_EX);
	}

	private function getLogMessage(string $message, ?string $messageType): string
	{
		if ($messageType !== null) {
			return '[' . date('d/m/Y H:i:s') . '] - [' . $messageType . '] - ' . $message . PHP_EOL;
		}

		return '[' . date('d/m/Y H:i:s') . '] - ' . $message . PHP_EOL;
	}
}
