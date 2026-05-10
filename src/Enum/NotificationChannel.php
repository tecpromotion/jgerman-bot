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
use joomlagerman\Notification\RunSummary;

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
	public function summaryPayload(RunSummary $summary, Registry $options): array
	{
		return match ($this) {
			self::Slack      => [
				'payload' => (string) json_encode(self::slackBody(
					$summary,
					self::stringOption($options, 'slack.username')
				)),
			],
			self::Mattermost => [
				'payload' => (string) json_encode(self::slackBody($summary, '')),
			],
			self::Telegram   => [
				'chat_id'                  => self::stringOption($options, 'telegram.chatId'),
				'parse_mode'               => 'HTML',
				'disable_web_page_preview' => 'true',
				'text'                     => self::telegramBody($summary),
			],
		};
	}

	/**
	 * Slack/Mattermost share the legacy `attachments` schema with `color`, `title`, `text`.
	 *
	 * @return  array<string, mixed>
	 */
	private static function slackBody(RunSummary $summary, string $username): array
	{
		$lines = $summary->lines();

		foreach ($summary->createdIssues() as $issue) {
			$lines[] = '• <' . $issue['url'] . '|' . $issue['title'] . '>';
		}

		$attachment = [
			'color'    => $summary->status()->color(),
			'title'    => 'JGerman GitHub Bot',
			'text'     => implode("\n", $lines),
			'fallback' => 'JGerman GitHub Bot run',
		];

		$body = ['attachments' => [$attachment]];

		if ($username !== '') {
			$body['username'] = $username;
		}

		return $body;
	}

	private static function telegramBody(RunSummary $summary): string
	{
		$lines = ['<b>JGerman GitHub Bot</b>'];

		foreach ($summary->lines() as $line) {
			$lines[] = htmlspecialchars($line, ENT_QUOTES | ENT_HTML5, 'UTF-8');
		}

		foreach ($summary->createdIssues() as $issue) {
			$lines[] = '• <a href="'
				. htmlspecialchars($issue['url'], ENT_QUOTES | ENT_HTML5, 'UTF-8')
				. '">'
				. htmlspecialchars($issue['title'], ENT_QUOTES | ENT_HTML5, 'UTF-8')
				. '</a>';
		}

		return implode("\n", $lines);
	}

	private static function stringOption(Registry $options, string $key): string
	{
		$value = $options->get($key);

		return is_string($value) ? $value : '';
	}
}
