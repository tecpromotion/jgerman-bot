<?php

declare(strict_types=1);

/**
 * JGerman Bot Branch helper
 *
 * @copyright  Copyright (C) 2020 J!German (www.jgerman.de) All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace joomlagerman\Enum;

final class JoomlaBranch
{
	/**
	 * Build the human-readable label (e.g. "Joomla! 4.10") for a target branch like "4.10-dev".
	 */
	public static function labelFor(string $targetBranch): string
	{
		$stripped = (string) preg_replace('/-.*$/', '', $targetBranch);
		$parts    = explode('.', $stripped);
		$major    = $parts[0] !== '' ? $parts[0] : $targetBranch;
		$minor    = $parts[1] ?? '0';

		return 'Joomla! ' . $major . '.' . $minor;
	}
}
