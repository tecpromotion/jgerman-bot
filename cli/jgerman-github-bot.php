<?php

declare(strict_types=1);

/**
 * JGerman GitHub Bot based on the Joomla! Framework
 *
 * @copyright  Copyright (C) 2020 J!German (www.jgerman.de) All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

use joomlagerman\Helper\Bootstrap;

if (PHP_SAPI !== 'cli') {
	echo 'This script needs to be called via CLI!' . PHP_EOL;
	exit;
}

error_reporting(-1);

require dirname(__DIR__) . '/includes/constants.php';

if (!file_exists(ROOT_PATH . '/vendor/autoload.php')) {
	exit(1);
}

require ROOT_PATH . '/vendor/autoload.php';

$services = new Bootstrap();

$services->log->writeLogMessage('Start JGerman GitHub Bot');
$services->notifier->sendLogNotification('Start JGerman GitHub Bot');

$currentRunDateTime = new DateTimeImmutable('now');
$lastRunDate        = $services->github->getLatestRunDateTime();

// Self-enforced once-per-day guard.
if ($currentRunDateTime->format('Y-m-d') === $lastRunDate->format('Y-m-d')) {
	$services->log->writeLogMessage('We only run once a day so exiting here.');
	$services->notifier->sendLogNotification('We only run once a day so exiting here.');
	$services->log->writeLogMessage('End JGerman GitHub Bot');
	$services->notifier->sendLogNotification('End JGerman GitHub Bot');
	exit;
}

$closedTranslationIssues = $services->github->getClosedAndMergedTranslationIssuesList($lastRunDate);

$services->log->writeLogMessage('We have ' . count($closedTranslationIssues) . ' closed translation issues since the last run.');
$services->notifier->sendLogNotification('We have ' . count($closedTranslationIssues) . ' closed translation issues since the last run.');

if ($closedTranslationIssues !== []) {
	$createdTranslationRequestIssues = 0;

	foreach ($closedTranslationIssues as $translationIssue) {
		$createdIssue = $services->github->createNewTranslationRequestIssueFromMergedTranslationIssue($translationIssue);

		if ($createdIssue === null) {
			continue;
		}

		/** @var object{title: string, html_url: string} $createdIssue */
		$services->notifier->sendMessageTemplateNotification([
			'title'    => $createdIssue->title,
			'issueUrl' => $createdIssue->html_url,
		]);
		$createdTranslationRequestIssues++;
	}

	$services->log->writeLogMessage('We have ' . $createdTranslationRequestIssues . ' translation request issues created.');
	$services->notifier->sendLogNotification('We have ' . $createdTranslationRequestIssues . ' translation request issues created.');
}

$services->log->writeLogMessage('Set the new latest run date to: ' . $currentRunDateTime->format('Y-m-d'));
$services->notifier->sendLogNotification('Set the new latest run date to: ' . $currentRunDateTime->format('Y-m-d'));
$services->github->setLatestRunDateTime($currentRunDateTime);
$services->log->writeLogMessage('End JGerman GitHub Bot');
$services->notifier->sendLogNotification('End JGerman GitHub Bot');
