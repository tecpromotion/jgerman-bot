<?php

declare(strict_types=1);

/**
 * JGerman GitHub Bot based on the Joomla! Framework
 *
 * @copyright  Copyright (C) 2020 J!German (www.jgerman.de) All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

use joomlagerman\Helper\Bootstrap;
use joomlagerman\Notification\RunStatus;
use joomlagerman\Notification\RunSummary;

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
$summary  = new RunSummary();

$services->log->writeLogMessage('Start JGerman GitHub Bot');

$currentRunDateTime = new DateTimeImmutable('now');
$lastRunDate        = $services->github->getLatestRunDateTime();

// Self-enforced once-per-day guard.
if ($currentRunDateTime->format('Y-m-d') === $lastRunDate->format('Y-m-d')) {
	$services->log->writeLogMessage('We only run once a day so exiting here.');
	$services->log->writeLogMessage('End JGerman GitHub Bot');

	$summary->setStatus(RunStatus::Noop);
	$summary->addLine('Already ran today — skipped.');
	$services->notifier->sendRunSummary($summary);
	exit;
}

$closedTranslationIssues = $services->github->getClosedAndMergedTranslationIssuesList($lastRunDate);
$closedCount             = count($closedTranslationIssues);

$services->log->writeLogMessage('We have ' . $closedCount . ' closed translation issues since the last run.');
$summary->addLine('Closed translation issues since last run: ' . $closedCount);

$createdTranslationRequestIssues = 0;

foreach ($closedTranslationIssues as $translationIssue) {
	$createdIssue = $services->github->createNewTranslationRequestIssueFromMergedTranslationIssue($translationIssue);

	if ($createdIssue === null) {
		continue;
	}

	/** @var object{title: string, html_url: string} $createdIssue */
	$summary->addCreatedIssue($createdIssue->title, $createdIssue->html_url);
	$createdTranslationRequestIssues++;
}

if ($closedCount > 0) {
	$services->log->writeLogMessage('We have ' . $createdTranslationRequestIssues . ' translation request issues created.');
	$summary->addLine('Created translation requests: ' . $createdTranslationRequestIssues);
}

$services->log->writeLogMessage('Set the new latest run date to: ' . $currentRunDateTime->format('Y-m-d'));
$summary->addLine('Last run date set to: ' . $currentRunDateTime->format('Y-m-d'));
$services->github->setLatestRunDateTime($currentRunDateTime);

$services->log->writeLogMessage('End JGerman GitHub Bot');
$services->notifier->sendRunSummary($summary);
