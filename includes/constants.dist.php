<?php

declare(strict_types=1);

/**
 * JGerman Bot Configuration
 *
 * @copyright  Copyright (C) 2020 J!German (www.jgerman.de) All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

// Application constants
define('ROOT_PATH', dirname(__DIR__));

// GitHub Access Data
define('GITHUB_USERNAME', '');
define('GITHUB_AUTHTOKEN', '');

// GitHub Bot Configuration
define('GITHUB_SOURCE_OWNER', '');
define('GITHUB_SOURCE_REPO', '');
define('GITHUB_SOURCE_WATCHLABEL', '');
define('GITHUB_TRANSLATION_OWNER', '');
define('GITHUB_TRANSLATION_REPO', '');
define('GITHUB_TRANSLATION_LABEL', '');
define('GITHUB_TRANSLATION_ASSIGMENTS', []);
define('GITHUB_TRANSLATION_TEMPLATE_BODY', '');

// Notifyer Config
define('NOTIFYER_SLACK_ENABLED', false);
define('NOTIFYER_SLACK_WEBHOOKURL', '');
define('NOTIFYER_SLACK_USERNAME', '');
define('NOTIFYER_MATTERMOST_ENABLED', false);
define('NOTIFYER_MATTERMOST_WEBHOOKURL', '');
define('NOTIFYER_TELEGRAM_ENABLED', false);
define('NOTIFYER_TELEGRAM_BOTTOKEN', '');
define('NOTIFYER_TELEGRAM_CHATID', '');
define('NOTIFYER_GITHUB_ISSUE_MESSAGE_TEMPLATE', '');
