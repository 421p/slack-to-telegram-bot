# slack-to-telegram-bot
Bot for forwarding slack messages to telegram.

## Usage
Tested on PHP 7.1, 7.2

#### Configuration

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

#### Custom replacements

If you have different user names in telegram and slack but still want mention feature to be
working - custom replacement is available. Just set another env variable in format `subject:replacement`

Pairs can be separated by comma.

`SLACK_CUSTOM_MAPPING=@john:@jonny,@ivan:@not_ivan`

## Docker

There is docker image available:

`docker pull 421p/slack-to-telegram-bot`

docker-compose.yml example

```yml
version: '2'

services:
  bot:
    image: 421p/slack-to-telegram-bot
    restart: always
    environment:
      - SLACK_TOKEN=%SOME_TOKEN%
      - TELEGRAM_TOKEN=%SOME_TOKEN%
      - TELEGRAM_TARGET=%CHAT_ID%
      - SLACK_CUSTOM_MAPPING=@john:@jonny
```

## Depencencies
- [slackclient](https://github.com/sagebind/slack-client)

Install the dependencies via composer: `composer install`.

## License
Licensed under the [Unlicense](http://unlicense.org/).
Do with it whatever you want.
