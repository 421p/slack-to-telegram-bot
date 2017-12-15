<?php

use Assert\Assertion;
use React\Promise\Deferred;
use Tarantool\PostProcessor;

require_once 'vendor/autoload.php';

$slackToken = getenv('SLACK_TOKEN');
$telegramToken = getenv('TELEGRAM_TOKEN');
$telegramTarget = getenv('TELEGRAM_TARGET');

Assertion::notNull($slackToken, 'Slack token is not set.');
Assertion::notNull($telegramToken, 'Telegram Bot token is not set.');
Assertion::notNull($telegramTarget, 'Telegram target (channel or chat) is not set.');

PostProcessor::loadMappings();

$loop = \React\EventLoop\Factory::create();

$client = new Slack\RealTimeClient($loop);
$client->setToken($slackToken);

$client->on('message', function ($data) use ($client, $telegramTarget, $telegramToken) {

    if ($data['type'] === 'message' && !isset($data['subtype'])) {

        $defer = new Deferred();

        $userToken = $data['user'];
        $text = $data['text'];

        switch ($data['channel'][0]) {
            case 'G':

                $client->apiCall('groups.info', ['channel' => $data['channel']])->then(function ($data) use ($defer) {
                    $channel = $data['group'];

                    $defer->resolve($channel);
                }, function ($err) {
                    echo 'ERROR.'.PHP_EOL;
                });

                break;
            case 'C':

                $client->apiCall('channels.info', ['channel' => $data['channel']])->then(function ($data) use ($defer) {
                    $channel = $data['channel'];

                    $defer->resolve($channel);
                }, function ($err) {
                    echo 'ERROR.'.PHP_EOL;
                });

                break;
            default:
                return;
        }

        $defer->promise()->then(function (array $channel) use ($userToken, $client, $text, $telegramTarget, $telegramToken) {
            $client->apiCall('users.info', ['user' => $userToken])->then(function ($user) use ($client, $channel, $text, $telegramTarget, $telegramToken) {

                $message = sprintf('@%s posted to #%s: %s', $user['user']['name'], $channel['name'], $text);

                $processed = PostProcessor::process($message);

                $curl = curl_init();

                curl_setopt_array($curl, [
                    CURLOPT_URL => "https://api.telegram.org/bot$telegramToken/sendMessage",
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_ENCODING => "",
                    CURLOPT_MAXREDIRS => 10,
                    CURLOPT_TIMEOUT => 30,
                    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                    CURLOPT_CUSTOMREQUEST => "POST",
                    CURLOPT_POSTFIELDS => json_encode([
                        'chat_id' => $telegramTarget,
                        'text' => $processed,
                    ]),
                    CURLOPT_HTTPHEADER => [
                        "content-type: application/json",
                    ],
                ]);

                $response = curl_exec($curl);
                $err = curl_error($curl);

                curl_close($curl);

                if ($err) {
                    echo "cURL Error #:".$err;
                } else {
                    echo $response;
                }

            }, function ($err) {
                echo 'ERROR.'.PHP_EOL;
            });
        });

    }

});

$client->connect()->then(function () {
    echo "Connected!\n";
});

$loop->run();
