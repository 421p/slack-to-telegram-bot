<?php

namespace Tarantool;

use React\EventLoop\Factory;
use React\Promise\Deferred;
use Slack\RealTimeClient;

class Application
{
    private $loop;
    private $client;
    private $telegram;
    private $targetChat;
    private $processor;

    public function __construct($slackToken, $telegramToken, $telegramTarget)
    {
        $this->loop = Factory::create();
        $this->client = new RealTimeClient($this->loop);
        $this->client->setToken($slackToken);

        $this->telegram = new Telegram($telegramToken);
        $this->targetChat = $telegramTarget;

        $this->processor = new PostProcessor($this->client, \Closure::fromCallable([$this, 'log']));

        $this->setup();
    }

    public function run()
    {
        $this->loop->run();
    }

    private function setup()
    {
        $this->client->connect()->then(function () {
            $this->log('Connected!');
        });

        $this->client->on('message', \Closure::fromCallable([$this, 'onMessage']));
    }

    private function onMessage($data)
    {
        $this->log(
            'Received message: '
            .PHP_EOL
            .json_encode($data, JSON_PRETTY_PRINT)
        );

        if ($data['type'] === 'message' && !isset($data['subtype'])) {

            $defer = new Deferred();

            $userToken = $data['user'];
            $text = $data['text'];

            switch ($data['channel'][0]) {
                case 'G':

                    $this->client->apiCall('groups.info', ['channel' => $data['channel']])->then(function ($data) use ($defer) {
                        $channel = $data['group'];

                        $defer->resolve($channel);
                    }, function ($err) {
                        $this->log('Error!'.PHP_EOL.json_encode($err, JSON_PRETTY_PRINT));
                    });

                    break;
                case 'C':

                    $this->client->apiCall('channels.info', ['channel' => $data['channel']])->then(function ($data) use ($defer) {
                        $channel = $data['channel'];

                        $defer->resolve($channel);
                    }, function ($err) {
                        $this->log('Error!'.PHP_EOL.json_encode($err, JSON_PRETTY_PRINT));
                    });

                    break;
                default:
                    return;
            }

            $defer->promise()->then(function (array $channel) use ($userToken, $text) {
                $this->client->apiCall('users.info', ['user' => $userToken])->then(
                    function ($user) use ($channel, $text) {

                        $message = sprintf('@%s posted to #%s: %s', $user['user']['name'], $channel['name'], $text);

                        $this->processor->process($message)->then(
                            function ($message) {
                                $this->log('Prepared message for sending to telegram: '.$message);
                                $this->telegram->sendMessage($this->targetChat, $message);
                            },
                            function ($err) {
                                if ($err instanceof \Exception) {
                                    $this->log($err->getMessage());
                                } else {
                                    $this->log('Error!'.PHP_EOL.json_encode($err, JSON_PRETTY_PRINT));
                                }
                            }
                        );

                    },
                    function ($err) {
                        $this->log('Error!'.PHP_EOL.json_encode($err, JSON_PRETTY_PRINT));
                    }
                );
            });

        }
    }

    private function log(string $data)
    {
        echo sprintf('[%s] %s', date('H:i:s'), $data).PHP_EOL;
    }
}