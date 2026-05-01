<?php

declare(strict_types=1);

/**
 * JGerman GitHub Bot Helper based on the Joomla! Framework
 *
 * @copyright  Copyright (C) 2020 J!German (www.jgerman.de) All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace joomlagerman\Helper;

use DateTimeImmutable;
use DateTimeInterface;
use Joomla\Github\Github;
use Joomla\Http\HttpFactory;
use Joomla\Registry\Registry;
use Joomla\Uri\Uri;
use joomlagerman\Enum\JoomlaBranch;

final class GithubApiHelper
{
	private readonly Github $github;
	private readonly Registry $options;
	private readonly string $dataRootPath;

	public function __construct(Registry $githubOptions, Registry $options)
	{
		$this->options = $options;

		if ($this->options->get('userAgent') === null) {
			$this->options->set('userAgent', 'JGerman-Bot/1.0');
		}

		$this->github       = new Github($githubOptions);
		$this->dataRootPath = ROOT_PATH . '/data/';
	}

	public function getOption(string $key): mixed
	{
		return $this->options->get($key);
	}

	public function getLatestRunDateTime(): DateTimeImmutable
	{
		$dataFileName = $this->getDateFileName('lastrun.data');

		if (!is_file($dataFileName)) {
			$now = new DateTimeImmutable('now');
			file_put_contents($dataFileName, $now->format('Y-m-d'));
		}

		$contents = file_get_contents($dataFileName);

		return new DateTimeImmutable($contents !== false ? $contents : 'now');
	}

	public function setLatestRunDateTime(DateTimeInterface $lastRunDateTime): void
	{
		$dataFileName = $this->getDateFileName('lastrun.data');

		if (is_file($dataFileName)) {
			unlink($dataFileName);
		}

		file_put_contents($dataFileName, $lastRunDateTime->format('Y-m-d'));
	}

	/**
	 * Get all closed and merged PRs with the translation label since a given timestamp.
	 *
	 * @return  list<object>
	 */
	public function getClosedAndMergedTranslationIssuesList(DateTimeInterface $since): array
	{
		$closedAndMerged = [];

		/** @var iterable<object{number: int, closed_at: string}> $closedIssues */
		$closedIssues = $this->getClosedTranslationIssuesList($since);

		foreach ($closedIssues as $issue) {
			$closedAt = new DateTimeImmutable($issue->closed_at);

			// Only the closedAt date — ignore comment/update bumps.
			if ($closedAt->format('Y-m-d') !== $since->format('Y-m-d')) {
				continue;
			}

			if ($this->github->pulls->isMerged(
				$this->optString('source.owner'),
				$this->optString('source.repo'),
				$issue->number
			)) {
				$closedAndMerged[] = $issue;
			}
		}

		return $closedAndMerged;
	}

	public function createNewTranslationRequestIssueFromMergedTranslationIssue(object $sourceTranslationIssue): ?object
	{
		/** @var object{number: int, title: string} $sourceTranslationIssue */
		$labels     = [$this->optString('translation.label')];
		$sourcePull = $this->getSourcePull($sourceTranslationIssue->number);

		/** @var object{base: object{ref: string}, user: object{login: string}, _links: object{html: object{href: string}}} $sourcePull */
		$labels[]    = JoomlaBranch::labelFor($sourcePull->base->ref);
		$assignments = $this->optAssignments();

		// Add label per top-level directory of changed translation files.
		foreach ($this->getChangedTranslationFilesByPR($sourceTranslationIssue->number) as $filename) {
			$path = explode('/', $filename);

			if (in_array($path[0], ['administrator', 'api', 'installation'], true)) {
				$labels[] = $path[0];
			}

			if ($path[0] === 'language') {
				$labels[] = 'site';
			}
		}

		$labels = array_values(array_unique($labels));

		// Skip echoes from the joomla-translation-bot to avoid loops.
		if ($sourcePull->user->login === 'joomla-translation-bot') {
			return null;
		}

		$body = $this->buildIssueBody($sourcePull, $this->getSourcePullDiff($sourceTranslationIssue->number));

		try {
			return $this->github->issues->create(
				$this->optString('translation.owner'),
				$this->optString('translation.repo'),
				$sourceTranslationIssue->title,
				$body,
				null,
				null,
				$labels,
				$assignments
			);
		} catch (\Exception) {
			// Retry without the diff — large diffs can blow the 65k issue body limit.
			return $this->github->issues->create(
				$this->optString('translation.owner'),
				$this->optString('translation.repo'),
				$sourceTranslationIssue->title,
				$this->buildIssueBody($sourcePull, ''),
				null,
				null,
				$labels,
				$assignments
			);
		}
	}

	public function getLatestGithubRelease(): object
	{
		return $this->github->repositories->releases->getLatest(
			$this->optString('translation.owner'),
			$this->optString('translation.repo')
		);
	}

	public function getLatestGithubReleaseByBranch(string $branch): ?object
	{
		/** @var iterable<string, object> $last5releases */
		$last5releases = $this->github->repositories->releases->getList(
			$this->optString('translation.owner'),
			$this->optString('translation.repo'),
			0,
			5
		);

		$tagNames = [];

		foreach ($last5releases as $tagName => $_release) {
			$tagNames[] = (string) $tagName;
		}

		$latestTag = self::pickLatestTag($tagNames, $branch);

		if ($latestTag === null) {
			return null;
		}

		return $this->github->repositories->releases->getByTag(
			$this->optString('translation.owner'),
			$this->optString('translation.repo'),
			$latestTag
		);
	}

	/**
	 * Pure helper for selecting the highest-version tag matching a "major.minor" branch.
	 *
	 * @param  list<string>  $tagNames
	 */
	public static function pickLatestTag(array $tagNames, string $branch): ?string
	{
		$candidates = array_values(array_filter(
			$tagNames,
			static function (string $tag) use ($branch): bool {
				$parts = explode('.', $tag);

				if (count($parts) < 2) {
					return false;
				}

				return $parts[0] . '.' . $parts[1] === $branch;
			}
		));

		if ($candidates === []) {
			return null;
		}

		usort($candidates, static fn (string $a, string $b): int => version_compare($b, $a));

		return $candidates[0];
	}

	public function getLatestPublishedRelease(string $branch): string
	{
		$dataFileName = $this->getDateFileName('lastrelease' . $branch . '.data');

		if (!is_file($dataFileName)) {
			file_put_contents($dataFileName, $branch . '.0.0');
		}

		$contents = file_get_contents($dataFileName);

		return $contents !== false ? trim($contents) : $branch . '.0.0';
	}

	public function setLatestPublishedRelease(string $branch, string $lastPublishedRelease): void
	{
		$dataFileName = $this->getDateFileName('lastrelease' . $branch . '.data');

		if (is_file($dataFileName)) {
			unlink($dataFileName);
		}

		file_put_contents($dataFileName, $lastPublishedRelease);
	}

	private function buildIssueBody(object $sourcePull, string $sourcePullDiff): string
	{
		/** @var object{_links: object{html: object{href: string}}} $sourcePull */
		$diffSection = $sourcePullDiff !== ''
			? PHP_EOL . '<details>' . PHP_EOL . '<summary>Click to expand the diff!</summary>' . PHP_EOL . PHP_EOL
				. '```diff' . PHP_EOL . $sourcePullDiff . PHP_EOL . '```' . PHP_EOL . '</details>' . PHP_EOL
			: '';

		$body = $this->optString('translation.templagebody');
		$body = str_replace('[sourcePullRequestUrl]', $sourcePull->_links->html->href, $body);

		return str_replace('[sourcePullDiff]', $diffSection, $body);
	}

	private function getClosedTranslationIssuesList(DateTimeInterface $since): mixed
	{
		$labels = urlencode($this->optString('source.watchlabel'));

		return $this->github->issues->getListByRepository(
			$this->optString('source.owner'),
			$this->optString('source.repo'),
			null,
			'closed',
			null,
			null,
			$labels,
			null,
			null,
			$since
		);
	}

	private function getSourcePull(int $pullrequestId): object
	{
		return $this->github->pulls->get(
			$this->optString('source.owner'),
			$this->optString('source.repo'),
			$pullrequestId
		);
	}

	/**
	 * Fetch the raw diff for a PR — the Joomla GitHub package can't request the diff Accept header,
	 * so we issue a manual HTTP call. The token is read from the options Registry, not a global.
	 *
	 * @link  https://developer.github.com/v3/pulls/#get-a-single-pull-request
	 * @link  https://developer.github.com/v3/media/#commits-commit-comparison-and-pull-requests
	 */
	private function getSourcePullDiff(int $pullrequestId): string
	{
		$uri = new Uri(
			'https://api.github.com/repos/'
			. $this->optString('source.owner')
			. '/'
			. $this->optString('source.repo')
			. '/pulls/'
			. $pullrequestId
		);

		$headers = [
			'Accept'        => 'application/vnd.github.v3.diff',
			'User-Agent'    => $this->optString('userAgent'),
			'Authorization' => 'token ' . $this->optString('github.authtoken'),
		];

		$response = (new HttpFactory())->getHttp()->get($uri->toString(), $headers);

		return (string) $response->getBody();
	}

	/**
	 * @link    https://docs.github.com/en/rest/pulls/pulls#list-pull-requests-files
	 * @return  list<string>
	 */
	private function getChangedTranslationFilesByPR(int $pullrequestId): array
	{
		/** @var iterable<object{filename: string}> $changedFilesByPR */
		$changedFilesByPR = $this->github->pulls->getFiles(
			$this->optString('source.owner'),
			$this->optString('source.repo'),
			$pullrequestId
		);

		$translationFiles = [];

		foreach ($changedFilesByPR as $value) {
			if (str_contains($value->filename, 'language/')) {
				$translationFiles[] = $value->filename;
			}
		}

		return $translationFiles;
	}

	private function getDateFileName(string $fileName): string
	{
		return $this->dataRootPath . $fileName;
	}

	private function optString(string $key): string
	{
		$value = $this->options->get($key);

		return is_string($value) ? $value : '';
	}

	/**
	 * @return  list<string>
	 */
	private function optAssignments(): array
	{
		$value = $this->options->get('translation.assigments');

		if (!is_array($value)) {
			return [];
		}

		return array_values(array_filter($value, 'is_string'));
	}
}
