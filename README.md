J!German Bot
======================================

### Description

This repo holds the code powering automation for the JGerman Team. As of today it has the following feature: Autocreate issues to the [joomlagerman/joomla](https://github.com/joomlagerman/joomla) repo when an language changing PR at the [joomla/joomla-cms](https://github.com/joomla/joomla-cms) repo got merged (using [@jgerman-bot](https://github.com/jgerman-bot))

## Requirements

- PHP 8.5 or higher
- Composer

## Initial Setup

- `cd /repo/path/jgerman-bot`
- `git clone git@github.com:joomlagerman/jgerman-bot.git .`
- `composer install --no-dev`
- `cp includes/constants.dist.php includes/constants.php`
- `nano includes/constants.php` (Add the access data)
- Setup an daily cronjob on this script: `php cli/jgerman-github-bot.php`

## Update Setup

- `cd /repo/path/jgerman-bot`
- `git reset --hard HEAD && git pull origin master`
- `composer install --no-dev`

## Upgrade Notes

The `NOTIFYER_*_ENABED` constants were renamed to `NOTIFYER_*_ENABLED` (typo fix).
After pulling the new version, update your existing `includes/constants.php`:

- `NOTIFYER_SLACK_ENABED` → `NOTIFYER_SLACK_ENABLED`
- `NOTIFYER_MATTERMOST_ENABED` → `NOTIFYER_MATTERMOST_ENABLED`
- `NOTIFYER_TELEGRAM_ENABED` → `NOTIFYER_TELEGRAM_ENABLED`

## Development

- Run tests: `composer test`
- Run static analysis: `composer stan`
- Check code style: `composer cs-check`
- Auto-fix code style: `composer cs-fix`

## Configuration

The Configuration is stored within the `includes/constants.php`. For an initial setup please see the `includes/constants.dist.php` file.


### GitHub Access Data
#### GITHUB_USERNAME

Holds user username that is used to connect to the GitHub API and who is used to create PRs

#### GITHUB_AUTHTOKEN

Holds the authtoken of the username to connect to the GitHub API

### GitHub Bot Configuration

#### GITHUB_SOURCE_OWNER

Holds the org name of the repo which should be watched by the bot

#### GITHUB_SOURCE_REPO

Holds the repository which should be watched by the bot

#### GITHUB_SOURCE_WATCHLABEL

Holds the label which should be watched within the mentiond source repo

#### GITHUB_TRANSLATION_OWNER

Holds the org name of the repo which the bot should be posting its issues at

#### GITHUB_TRANSLATION_REPO

Holds the repo where the bot should be posing its issues

#### GITHUB_TRANSLATION_LABEL

Holds the label that should be set for the issues created by the bot

#### GITHUB_TRANSLATION_ASSIGMENTS

Holds an array of github usernames that should be assigned to the issues that the bot creates

#### GITHUB_TRANSLATION_TEMPLATE_BODY

Holds a template of the body to be posted by the bot. This string allows the following codes which will be replaced before the issue is posted:
- `[sourcePullRequestUrl]` => The URL of the original PR against the source repo
- `[sourcePullDiff]` => The full diff of the original PR via an `<details>` tab

### Notifyer Config
#### NOTIFYER_SLACK_ENABLED

True or False whether the Slack notification should be anabled

#### NOTIFYER_SLACK_WEBHOOKURL

The webhook URL pointing to the slack channel where the message shoud be send to

#### NOTIFYER_SLACK_USERNAME

THe Slack username the message should be send as

#### NOTIFYER_MATTERMOST_ENABLED

True or False whether the Slack notification should be anabled

#### NOTIFYER_MATTERMOST_WEBHOOKURL

The webhook URL pointing to the mattermost channel where the message should be send to

#### NOTIFYER_TELEGRAM_ENABLED

True or False whether the Slack notification should be anabled

#### NOTIFYER_TELEGRAM_BOTTOKEN

The Telegram Channel Bot Token as which the message shoudl be send

#### NOTIFYER_TELEGRAM_CHATID

The Telegram ChatID the message should be send to

#### NOTIFYER_GITHUB_ISSUE_MESSAGE_TEMPLATE

The template of the message send to the channels as notification that a new issue has been created. This string allows the following codes which will be replaced before the message is send:
- `{title}` => The title of the issue created by the bot
- `{issueUrl}` => The issue URL created in the translation repo.
