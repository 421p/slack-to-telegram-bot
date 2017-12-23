<?php

namespace Tarantool;

use React\Promise\Deferred;
use React\Promise\Promise;
use Slack\RealTimeClient;

class PostProcessor
{
    private $mapping = [];
    private $logger;
    private $client;

    public function __construct(RealTimeClient $client, callable $logger)
    {
        $this->client = $client;
        $this->logger = $logger;
    }

    public function loadMappings()
    {
        if (($mpp = getenv('SLACK_CUSTOM_MAPPING')) !== null) {
            $tokens = array_map('trim', explode(',', $mpp));

            $this->log('Loading tokens: '.$mpp);

            foreach ($tokens as $token) {
                [$key, $value] = explode(':', $token);

                $this->mapping[$key] = $value;
            }

            $this->log('Loaded mapping:'.PHP_EOL.json_encode($this->mapping, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        }
    }

    public function process(string $message): Promise
    {
        $defer = new Deferred();

        $this->processMentions($message)->then(function ($message) use ($defer) {
            $defer->resolve($this->applyMappings($message));
        }, function ($err) use ($defer) {
            $defer->reject($err);
        });

        return $defer->promise();
    }

    private function processMentions(string $message): Promise
    {
        $defer = new Deferred();

        preg_match_all('/<@([^<>]+)>/', $message, $matches);

        $rawMentions = $matches[0];
        $mentions = $matches[1];

        if (count($mentions) === 0) {
            $defer->resolve($message);
        } else {
            \React\Promise\all(array_map(\Closure::fromCallable([$this, 'resolveName']), $mentions))->then(
                function (array $names) use ($defer, $message, $rawMentions) {
                    $defer->resolve(str_replace(
                        $rawMentions,
                        array_map(function ($name) {
                            return '@'.$name;
                        }, $names),
                        $message
                    ));
                }
                ,
                function ($err) use ($defer) {
                    $defer->reject($err);
                }
            );
        }

        return $defer->promise();
    }

    private function resolveName(string $id): Promise
    {
        $defer = new Deferred();

        $this->client->apiCall('users.info', ['user' => $id])->then(
            function ($user) use ($defer) {
                $defer->resolve($user['user']['name']);
            },
            function ($err) use ($defer) {
                $defer->reject(new \Exception('Error!'.PHP_EOL.json_encode($err, JSON_PRETTY_PRINT)));
            }
        );

        return $defer->promise();
    }

    private function applyMappings(string $message): string
    {
        return count($this->mapping) > 0 ? str_replace(array_keys($this->mapping), array_values($this->mapping), $message) : $message;
    }

    private function log(string $data)
    {
        ($this->logger)($data);
    }
}