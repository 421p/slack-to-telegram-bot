<?php

use Assert\Assertion;
use Tarantool\Application;

require_once 'vendor/autoload.php';

$slackToken = getenv('SLACK_TOKEN');
$telegramToken = getenv('TELEGRAM_TOKEN');
$telegramTarget = getenv('TELEGRAM_TARGET');

Assertion::notNull($slackToken, 'Slack token is not set.');
Assertion::notNull($telegramToken, 'Telegram Bot token is not set.');
Assertion::notNull($telegramTarget, 'Telegram target (channel or chat) is not set.');

$app = new Application($slackToken, $telegramToken, $telegramTarget);

$app->run();
