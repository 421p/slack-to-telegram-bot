# slack-to-telegram-bot
Bot for forwarding slack messages to telegram.

## Usage
Tested on PHP 7.1.

For configuration, set the following environment variables:
```
$ export SLACK_TOKEN=''     # Slack bot token
$ export TELEGRAM_TOKEN=''  # Telegram bot token
$ export TELEGRAM_TARGET='' # Target chat
```
For the target chat, see http://stackoverflow.com/questions/32423837/telegram-bot-how-to-get-a-group-chat-id-ruby-gem-telegram-bot.

Run with:
```
php bot.php # <- You could not have guessed that!
```

## Depencencies
- [slackclient](https://github.com/sagebind/slack-client)

Install the dependencies via composer: `composer install`.

## License
Licensed under the [Unlicense](http://unlicense.org/).
Do with it whatever you want.
