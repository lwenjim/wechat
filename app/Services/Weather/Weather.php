<?php

namespace App\Services\Weather;

class Weather
{

    private static $appKey = "23487971";
    private static $appSecret = "eb3e01e3570b8f5fa7f463dffc39a154";
    private static $host = "http://ali-weather.showapi.com";
    private static $connectTimeout = 30000;//30 second
    private static $readTimeout = 80000;//80 second

    function getWeather($longitude, $latitude)
    {
        //域名后、query前的部分
        $path = "/gps-to-weather";
        $request = new HttpRequest($this::$host, $path, HttpMethod::GET, $this::$appKey, $this::$appSecret);
        //设定Content-Type，根据服务器端接受的值来设置
        $request->setHeader(HttpHeader::HTTP_HEADER_CONTENT_TYPE, ContentType::CONTENT_TYPE_TEXT);

        //设定Accept，根据服务器端接受的值来设置
        $request->setHeader(HttpHeader::HTTP_HEADER_ACCEPT, ContentType::CONTENT_TYPE_TEXT);
        //注意：业务query部分，如果没有则无此行；请不要、不要、不要做UrlEncode处理
        $request->setQuery("from", "5");
        $request->setQuery("need3HourForcast", "1");
        $request->setQuery("lat", "$latitude");
        $request->setQuery("lng", "$longitude");
        //指定参与签名的header
        $request->setSignHeader(SystemHeader::X_CA_TIMESTAMP);
        return HttpUtil::send($request, self::$readTimeout, self::$connectTimeout);
    }
}