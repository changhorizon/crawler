<?php

declare(strict_types=1);

namespace Hizpark\Crawler\Http;

class CurlHttpClient implements HttpClientInterface
{
    private const DEFAULT_TIMEOUT = 5;
    private const MAX_REDIRECTS   = 3;
    private const USER_AGENT      = 'Mozilla/5.0 (compatible; HizparkCrawler/1.0)';

    public function get(string $url): ?array
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER         => true,
            CURLOPT_TIMEOUT        => self::DEFAULT_TIMEOUT,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS      => self::MAX_REDIRECTS,
            CURLOPT_USERAGENT      => self::USER_AGENT,
            CURLOPT_SSL_VERIFYPEER => false,
        ]);

        $rawResponse = curl_exec($ch);

        if (curl_errno($ch)) {
            curl_close($ch);

            return null;
        }

        if (!is_string($rawResponse)) {
            curl_close($ch);

            return null;
        }

        $statusCode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
        curl_close($ch);

        return [
            'statusCode'  => $statusCode,
            'contentType' => is_string($contentType) ? $contentType : '',
            'content'     => $rawResponse,
        ];
    }
}
