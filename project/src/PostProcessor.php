<?php

namespace Tarantool;

class PostProcessor
{

    private static $mapping = [];

    public static function loadMappings()
    {
        if (($mpp = getenv('SLACK_CUSTOM_MAPPING')) !== null) {
            $tokens = array_map('trim', explode(',', $mpp));

            echo 'Loading tokens: '.$mpp.PHP_EOL;

            foreach ($tokens as $token) {
                [$key, $value] = explode(':', $token);

                self::$mapping[$key] = $value;
            }

            echo 'Loaded mapping:'.PHP_EOL;
            echo json_encode(self::$mapping, JSON_PRETTY_PRINT).PHP_EOL;
        }
    }

    public static function process(string $message): string
    {
        if (count(self::$mapping) > 0) {
            $message = str_replace(array_keys(self::$mapping), array_values(self::$mapping), $message);
        }

        return $message;
    }
}