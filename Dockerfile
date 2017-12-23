FROM php:7.2.0-cli-stretch

ADD project /app

WORKDIR /php

ENTRYPOINT ["php", "/app/bot.php"]