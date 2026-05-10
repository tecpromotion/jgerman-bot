<?php

declare(strict_types=1);

/**
 * JGerman Notifyer Helper based on the Joomla! Framework
 *
 * @copyright  Copyright (C) 2020 J!German (www.jgerman.de) All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace joomlagerman\Helper;

use Joomla\Http\Http;
use Joomla\Http\HttpFactory;
use Joomla\Registry\Registry;
use joomlagerman\Enum\NotificationChannel;
use joomlagerman\Notification\RunSummary;

final class NotifyerHelper
{
	private readonly Registry $options;
	private readonly Http $http;

	public function __construct(Registry $options, ?Http $http = null)
	{
		$this->options = $options;
		$this->http    = $http ?? (new HttpFactory())->getHttp();
	}

	public function sendRunSummary(RunSummary $summary): void
	{
		foreach (NotificationChannel::cases() as $channel) {
			if (!$channel->isEnabled($this->options)) {
				continue;
			}

			$this->http->post(
				$channel->endpoint($this->options),
				$channel->summaryPayload($summary, $this->options)
			);
		}
	}
}
