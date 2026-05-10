<?php

declare(strict_types=1);

/**
 * JGerman Bot Run Summary
 *
 * @copyright  Copyright (C) 2020 J!German (www.jgerman.de) All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace joomlagerman\Notification;

final class RunSummary
{
	private RunStatus $status = RunStatus::Success;

	/** @var list<string> */
	private array $lines = [];

	/** @var list<array{title: string, url: string}> */
	private array $createdIssues = [];

	public function setStatus(RunStatus $status): void
	{
		$this->status = $status;
	}

	public function addLine(string $line): void
	{
		$this->lines[] = $line;
	}

	public function addCreatedIssue(string $title, string $url): void
	{
		$this->createdIssues[] = ['title' => $title, 'url' => $url];
	}

	public function status(): RunStatus
	{
		return $this->status;
	}

	/** @return list<string> */
	public function lines(): array
	{
		return $this->lines;
	}

	/** @return list<array{title: string, url: string}> */
	public function createdIssues(): array
	{
		return $this->createdIssues;
	}
}
