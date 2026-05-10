<?php

declare(strict_types=1);

namespace joomlagerman\Tests\Unit;

use Joomla\Http\Http;
use Joomla\Http\Response;
use Joomla\Registry\Registry;
use joomlagerman\Helper\NotifyerHelper;
use joomlagerman\Notification\RunStatus;
use joomlagerman\Notification\RunSummary;
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

		$http = $this->createMock(Http::class);
		$http->expects(self::once())
			->method('post')
			->with(
				self::equalTo('https://example.invalid/slack'),
				self::callback(static function (array $payload): bool {
					$body = json_decode((string) $payload['payload'], true);

					return is_array($body)
						&& ($body['username'] ?? null) === 'jgerman-bot'
						&& isset($body['attachments'][0]['color']);
				})
			)
			->willReturn(new Response());

		$summary = new RunSummary();
		$summary->addLine('hello');

		(new NotifyerHelper($registry, $http))->sendRunSummary($summary);
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

		$http = $this->createMock(Http::class);
		$http->expects(self::exactly(3))
			->method('post')
			->willReturn(new Response());

		(new NotifyerHelper($registry, $http))->sendRunSummary(new RunSummary());
	}

	public function testSlackPayloadCarriesColorAndCreatedIssueLinks(): void
	{
		$registry = new Registry();
		$registry->set('slack.enabled', true);
		$registry->set('slack.webhookurl', 'https://example.invalid/slack');
		$registry->set('slack.username', 'jgerman-bot');
		$registry->set('mattermost.enabled', false);
		$registry->set('telegram.enabled', false);

		$captured = '';

		$http = $this->createMock(Http::class);
		$http->expects(self::once())
			->method('post')
			->willReturnCallback(function ($url, array $payload) use (&$captured): Response {
				$captured = (string) $payload['payload'];

				return new Response();
			});

		$summary = new RunSummary();
		$summary->setStatus(RunStatus::Success);
		$summary->addLine('Closed translation issues since last run: 1');
		$summary->addCreatedIssue('Big PR', 'https://example.invalid/issues/1');

		(new NotifyerHelper($registry, $http))->sendRunSummary($summary);

		$body = json_decode($captured, true);

		self::assertIsArray($body);
		self::assertSame('jgerman-bot', $body['username']);
		self::assertSame('good', $body['attachments'][0]['color']);
		self::assertSame('JGerman GitHub Bot', $body['attachments'][0]['title']);
		self::assertStringContainsString(
			'<https://example.invalid/issues/1|Big PR>',
			$body['attachments'][0]['text']
		);
	}

	public function testNoopStatusYieldsWarningColor(): void
	{
		$registry = new Registry();
		$registry->set('slack.enabled', false);
		$registry->set('mattermost.enabled', true);
		$registry->set('mattermost.webhookurl', 'https://example.invalid/mm');
		$registry->set('telegram.enabled', false);

		$captured = '';

		$http = $this->createMock(Http::class);
		$http->expects(self::once())
			->method('post')
			->willReturnCallback(function ($url, array $payload) use (&$captured): Response {
				$captured = (string) $payload['payload'];

				return new Response();
			});

		$summary = new RunSummary();
		$summary->setStatus(RunStatus::Noop);
		$summary->addLine('Already ran today — skipped.');

		(new NotifyerHelper($registry, $http))->sendRunSummary($summary);

		$body = json_decode($captured, true);

		self::assertIsArray($body);
		self::assertArrayNotHasKey('username', $body);
		self::assertSame('warning', $body['attachments'][0]['color']);
	}

	public function testTelegramReceivesPlainTextWithLinks(): void
	{
		$registry = new Registry();
		$registry->set('slack.enabled', false);
		$registry->set('mattermost.enabled', false);
		$registry->set('telegram.enabled', true);
		$registry->set('telegram.botToken', 'TOKEN');
		$registry->set('telegram.chatId', '42');

		$captured = [];

		$http = $this->createMock(Http::class);
		$http->expects(self::once())
			->method('post')
			->willReturnCallback(function ($url, array $payload) use (&$captured): Response {
				$captured = $payload;

				return new Response();
			});

		$summary = new RunSummary();
		$summary->addLine('Closed translation issues since last run: 1');
		$summary->addCreatedIssue('Big PR', 'https://example.invalid/issues/1');

		(new NotifyerHelper($registry, $http))->sendRunSummary($summary);

		self::assertSame('42', $captured['chat_id']);
		self::assertSame('HTML', $captured['parse_mode']);
		self::assertStringContainsString('<b>JGerman GitHub Bot</b>', $captured['text']);
		self::assertStringContainsString(
			'<a href="https://example.invalid/issues/1">Big PR</a>',
			$captured['text']
		);
	}
}
