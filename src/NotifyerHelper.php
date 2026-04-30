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

final class NotifyerHelper
{
	private readonly Registry $options;
	private readonly Http $http;

	public function __construct(Registry $options, ?Http $http = null)
	{
		$this->options = $options;
		$this->http    = $http ?? (new HttpFactory())->getHttp();
	}

	/**
	 * @param  array<string, string>  $messageData
	 */
	public function sendMessageTemplateNotification(array $messageData, ?string $messageType = null): void
	{
		$this->sendNotificationMessage(
			$this->getMessageTemplateNotificationMessage($messageData, $messageType)
		);
	}

	public function sendLogNotification(string $message): void
	{
		$this->sendNotificationMessage($message);
	}

	/**
	 * @param  array<string, string>  $messageData
	 */
	private function getMessageTemplateNotificationMessage(array $messageData, ?string $messageType): string
	{
		$template = $this->options->get('notifyer.messageTemplate');
		$message  = is_string($template) ? $template : '';

		foreach ($messageData as $key => $value) {
			$message = str_replace('{' . $key . '}', $value, $message);
		}

		if ($messageType !== null) {
			return '[jgerman-bot] - [' . $messageType . '] - ' . $message . PHP_EOL;
		}

		return '[jgerman-bot] - ' . $message . PHP_EOL;
	}

	private function sendNotificationMessage(string $message): void
	{
		foreach (NotificationChannel::cases() as $channel) {
			if (!$channel->isEnabled($this->options)) {
				continue;
			}

			$this->http->post(
				$channel->endpoint($this->options),
				$channel->payload($message, $this->options)
			);
		}
	}
}
