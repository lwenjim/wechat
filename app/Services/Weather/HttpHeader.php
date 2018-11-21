<?php

namespace App\Services\Weather;

/**
 * HTTP头常量
 */
class HttpHeader
{
    //请求Header Accept
    const HTTP_HEADER_ACCEPT = "Accept";
    //请求Body内容MD5 Header
    const HTTP_HEADER_CONTENT_MD5 = "Content-MD5";
    //请求Header Content-Type
    const HTTP_HEADER_CONTENT_TYPE = "Content-Type";
    //请求Header UserAgent
    const HTTP_HEADER_USER_AGENT = "User-Agent";
    //请求Header Date
    const HTTP_HEADER_DATE = "Date";
}
