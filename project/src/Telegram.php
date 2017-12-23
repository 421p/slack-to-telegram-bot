<?php

namespace Tarantool;

class Telegram
{
    private $key;

    public function __construct(string $token)
    {
        $this->key = $token;
    }

    /**
     * @param string $target
     * @param string $message
     * @return mixed
     * @throws \Exception
     */
    public function sendMessage(string $target, string $message)
    {
        $curl = curl_init();

        curl_setopt_array($curl, [
            CURLOPT_URL => 'https://api.telegram.org/bot'.$this->key.'/sendMessage',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => json_encode([
                'chat_id' => $target,
                'text' => $message,
            ]),
            CURLOPT_HTTPHEADER => [
                "content-type: application/json",
            ],
        ]);

        $response = curl_exec($curl);
        $err = curl_error($curl);

        curl_close($curl);

        if ($err) {
            throw new \Exception($err);
        }

        return $response;
    }
}