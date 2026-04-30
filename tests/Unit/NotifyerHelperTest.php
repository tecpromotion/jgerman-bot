<?php

declare(strict_types=1);

namespace joomlagerman\Tests\Unit;

use Joomla\Http\Http;
use Joomla\Http\Response;
use Joomla\Registry\Registry;
use joomlagerman\Helper\NotifyerHelper;
use PHPUnit\Framework\TestCase;

final class NotifyerHelperTest extends TestCase
{
	public function testRoutesOnlyToEnabledChannels(): void
	{
		$registry = new Registry();
		$registry->set('slack.enabled', true);
		$registry->set('slack.webhookurl', 'https://example.invalid/slack');
		$registry->set('slack.username', 'jgerman-bot');
		$registry->set('mattermost.enabled', false);
		$registry->set('telegram.enabled', false);
		$registry->set('notifyer.messageTemplate', '{title} → {issueUrl}');

		$http = $this->createMock(Http::class);
		$http->expects(self::once())
			->method('post')
			->with(
				self::equalTo('https://example.invalid/slack'),
				self::callback(static function (array $payload): bool {
					return isset($payload['payload'])
						&& str_contains((string) $payload['payload'], '"username":"jgerman-bot"');
				})
			)
			->willReturn(new Response());

		$notifier = new NotifyerHelper($registry, $http);
		$notifier->sendLogNotification('hello');
	}

	public function testFansOutToAllThreeWhenAllEnabled(): void
	{
		$registry = new Registry();
		$registry->set('slack.enabled', true);
		$registry->set('slack.webhookurl', 'https://example.invalid/slack');
		$registry->set('slack.username', 'jgerman-bot');
		$registry->set('mattermost.enabled', true);
		$registry->set('mattermost.webhookurl', 'https://example.invalid/mm');
		$registry->set('telegram.enabled', true);
		$registry->set('telegram.botToken', 'TOKEN');
		$registry->set('telegram.chatId', '42');
		$registry->set('notifyer.messageTemplate', '{title}');

		$http = $this->createMock(Http::class);
		$http->expects(self::exactly(3))
			->method('post')
			->willReturn(new Response());

		$notifier = new NotifyerHelper($registry, $http);
		$notifier->sendLogNotification('hi');
	}

	public function testTemplateMessageSubstitutionWithMessageType(): void
	{
		$registry = new Registry();
		$registry->set('slack.enabled', true);
		$registry->set('slack.webhookurl', 'https://example.invalid/slack');
		$registry->set('slack.username', 'jgerman-bot');
		$registry->set('mattermost.enabled', false);
		$registry->set('telegram.enabled', false);
		$registry->set('notifyer.messageTemplate', '{title} -> {issueUrl}');

		$captured = '';

		$http = $this->createMock(Http::class);
		$http->expects(self::once())
			->method('post')
			->willReturnCallback(function ($url, array $payload) use (&$captured): Response {
				$captured = (string) $payload['payload'];

				return new Response();
			});

		$notifier = new NotifyerHelper($registry, $http);
		$notifier->sendMessageTemplateNotification(
			['title' => 'Big PR', 'issueUrl' => 'https://example.invalid/issues/1'],
			'INFO'
		);

		self::assertStringContainsString(
			'[jgerman-bot] - [INFO] - Big PR -> https:\/\/example.invalid\/issues\/1',
			$captured
		);
	}
}
