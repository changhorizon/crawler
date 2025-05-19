<?php

declare(strict_types=1);

namespace Hizpark\Crawler\Http;

interface HttpClientInterface
{
    /**
     * 發送 GET 請求並回傳回應內容
     *
     * @return array{statusCode: int, contentType: string, content: string}|null
     */
    public function get(string $url): ?array;
}
