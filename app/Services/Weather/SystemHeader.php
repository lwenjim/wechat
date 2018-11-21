<?php

namespace App\Services\Weather;

/**
 * 系统HTTP头常量
 */
class SystemHeader
{
    //签名Header
    const X_CA_SIGNATURE = "X-Ca-Signature";
    //所有参与签名的Header
    const X_CA_SIGNATURE_HEADERS = "X-Ca-Signature-Headers";
    //请求时间戳
    const X_CA_TIMESTAMP = "X-Ca-Timestamp";
    //请求放重放Nonce,15分钟内保持唯一,建议使用UUID
    const X_CA_NONCE = "X-Ca-Nonce";
    //APP KEY
    const X_CA_KEY = "X-Ca-Key";
}
