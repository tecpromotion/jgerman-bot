# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Setup & Run

- PHP 8.5+ required
- Install dependencies: `composer install`
- Configure: `cp includes/constants.dist.php includes/constants.php` and fill in values
- Run the bot: `php cli/jgerman-github-bot.php` (intended to be triggered by a daily cron)

## Tooling

- `composer test` — PHPUnit suite (`tests/Unit/`)
- `composer stan` — PHPStan at `level: max`
- `composer cs-check` / `composer cs-fix` — PHP-CS-Fixer (PSR-12 + tab indent)

## Architecture

This is a single-purpose cron-driven PHP CLI bot. It mirrors merged language-changing PRs from one GitHub repo into translation-request issues on another, then notifies chat channels.

**Entry point flow** (`cli/jgerman-github-bot.php`):
1. Loads `includes/constants.php` (defines `ROOT_PATH` and all `GITHUB_*` / `NOTIFYER_*` config) and Composer autoload.
2. Instantiates `joomlagerman\Helper\Bootstrap`, which wires the configured constants into a typed Registry and constructs the three helpers (`github`, `log`, `notifier`).
3. Reads `data/lastrun.data`. If today's date matches, exits early — **the script self-enforces "run once per day"**, so multiple cron firings on the same day are a no-op.
4. Pulls closed issues since `lastrun` from the source repo with the watch label, filters to those actually merged, creates a translation-request issue in the translation repo for each, fires a notification per created issue, then writes today's date back to `lastrun.data`.

**Helpers** (`src/`, PSR-4 namespace `joomlagerman\Helper\`):
- `Bootstrap` — composes the three helpers from `includes/constants.php` so the CLI script stays thin and the wiring is testable.
- `GithubApiHelper` — wraps `Joomla\Github\Github`. Owns the lastrun + lastrelease state files in `data/`. Key non-obvious behaviors:
  - `getClosedAndMergedTranslationIssuesList()` filters by `closed_at == $since` (date-only) to avoid re-processing issues that just had a comment update.
  - `createNewTranslationRequestIssueFromMergedTranslationIssue()` fetches the raw diff via a hand-rolled HTTP call (the Joomla GitHub package can't request the `application/vnd.github.v3.diff` Accept header) and embeds it inside `<details>`. If issue creation throws (typically because the diff body is too large), it retries once **without** the diff. The auth token is pulled from the options Registry (`github.authtoken`), not a global constant.
  - Branch-label mapping is delegated to `joomlagerman\Enum\JoomlaBranch::labelFor()`. Joomla 5+ caps minors at `.4-dev` before bumping the major (`5.1-dev` … `5.4-dev`, then `6.0-dev`), but Joomla 3 and 4 had multi-digit minors (`3.10-dev`, `4.10-dev`) — the parser handles both forms.
  - PRs authored by `joomla-translation-bot` are skipped to avoid loops.
  - `pickLatestTag()` is a pure static helper (extracted for testability) that selects the highest `version_compare` tag matching a given `major.minor` branch.
- `NotifyerHelper` — fan-out to Slack, Mattermost, and Telegram via `joomlagerman\Enum\NotificationChannel`. Each channel case carries its own `isEnabled()`/`endpoint()`/`payload()` logic. Accepts an injected `Joomla\Http\Http` for testing; defaults to `(new HttpFactory())->getHttp()`.
- `LogHelper` — appends to `logs/YYYYMM_jgerman.log`.

**Persistent state** lives in `data/*.data` (gitignored): `lastrun.data` for the daily guard, `lastrelease<branch>.data` for release tracking helpers. Deleting `lastrun.data` is the way to force a same-day re-run.

## Conventions

- PHP 8.5+ platform target (`composer.json`); use modern idioms (`readonly`, constructor property promotion, `final`, `match`, enums, strict types).
- All PHP files start with `declare(strict_types=1);`.
- Tab indentation, LF line endings, final newline (`.editorconfig` + `.php-cs-fixer.dist.php`).
- `includes/constants.php` is gitignored — never commit it. Update `includes/constants.dist.php` (and `tools/phpstan-stubs/constants.stub.php` so static analysis sees the constant) whenever a new constant is introduced.
