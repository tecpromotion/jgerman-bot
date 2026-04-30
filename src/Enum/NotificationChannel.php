<?php

declare(strict_types=1);

/**
 * JGerman Bot Notification Channel
 *
 * @copyright  Copyright (C) 2020 J!German (www.jgerman.de) All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace joomlagerman\Enum;

use Joomla\Registry\Registry;

enum NotificationChannel: string
{
	case Slack      = 'slack';
	case Mattermost = 'mattermost';
	case Telegram   = 'telegram';

	public function isEnabled(Registry $options): bool
	{
		return $options->get($this->value . '.enabled') === true;
	}

	public function endpoint(Registry $options): string
	{
		return match ($this) {
			self::Slack, self::Mattermost => self::stringOption($options, $this->value . '.webhookurl'),
			self::Telegram                => 'https://api.telegram.org/bot'
				. self::stringOption($options, 'telegram.botToken') . '/sendMessage',
		};
	}

	/**
	 * @return  array<string, string>
	 */
	public function payload(string $message, Registry $options): array
	{
		return match ($this) {
			self::Slack => [
				'payload' => (string) json_encode([
					'username' => self::stringOption($options, 'slack.username'),
					'text'     => $message,
				]),
			],
			self::Mattermost => [
				'payload' => (string) json_encode(['text' => $message]),
			],
			self::Telegram => [
				'chat_id'                  => self::stringOption($options, 'telegram.chatId'),
				'parse_mode'               => 'HTML',
				'disable_web_page_preview' => 'true',
				'text'                     => $message,
			],
		};
	}

	private static function stringOption(Registry $options, string $key): string
	{
		$value = $options->get($key);

		return is_string($value) ? $value : '';
	}
}
