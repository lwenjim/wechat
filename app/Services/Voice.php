<?php

namespace App\Services;

class Voice
{
    private static $accessKeyId = "LTAILQbK6EKTgNfk";
    private static $accessKeySecret = "iNk6rAbsNA7bznWSCw7aR23W2kAN8a";

    public static function post($data)
    {
        $client = new \GuzzleHttp\Client();
        $method = "POST";
        $accept = "application/json";
        $date = gmdate("l d F Y H:i:s") . " GMT";
        $content_type = "audio/pcm; samplerate=16000";
        $content = self::base64md5(self::base64md5($data));
        $signature = self::getSign($method . "\n" . $accept . "\n" . $content . "\n" . $content_type . "\n" . $date);
        $response = $client->request('POST', "http://nlsapi.aliyun.com/recognize?model=chat&version=2", [
            'headers' => [
                'Authorization' => 'Dataplus ' . self::$accessKeyId . ":" . $signature,
                'Accept' => 'application/json',
                'Content-Type' => $content_type,
                'Date' => $date,
                'Content-Length' => mb_strlen($data),
            ],
            'body' => $data
        ]);
        return json_decode($response->getBody(), true);
    }

    private static function base64md5($str)
    {
        return base64_encode(md5($str, true));
    }

    private static function getSign($str)
    {
        return base64_encode(hash_hmac('sha1', $str, self::$accessKeySecret, true));
    }
}