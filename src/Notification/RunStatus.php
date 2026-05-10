<?php

declare(strict_types=1);

/**
 * JGerman Bot Run Status
 *
 * @copyright  Copyright (C) 2020 J!German (www.jgerman.de) All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace joomlagerman\Notification;

enum RunStatus: string
{
	case Success = 'success';
	case Noop    = 'noop';
	case Error   = 'error';

	public function color(): string
	{
		return match ($this) {
			self::Success => 'good',
			self::Noop    => 'warning',
			self::Error   => 'danger',
		};
	}
}
