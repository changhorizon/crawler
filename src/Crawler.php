<?php

declare(strict_types=1);

namespace ChangHorizon\Crawler;

use ChangHorizon\Crawler\Http\HttpClientInterface;
use ChangHorizon\Crawler\Storage\DocumentStorageInterface;
use DOMDocument;
use DOMNode;
use Psr\Log\LoggerInterface;

class Crawler
{
    private string $startUrl;

    private DocumentStorageInterface $storage;

    private LoggerInterface $logger;

    private int $crawlDelay;

    private HttpClientInterface $httpClient;

    /**
     */
    public function __construct(
        string $startUrl,
        HttpClientInterface $httpClient,
        DocumentStorageInterface $storage,
        LoggerInterface $logger,
        int $crawlDelay = 1,
    ) {
        $this->startUrl   = $startUrl;
        $this->httpClient = $httpClient;
        $this->storage    = $storage;
        $this->logger     = $logger;
        $this->crawlDelay = $crawlDelay;
    }

    /**
     * 运行爬虫
     */
    public function run(): void
    {
        $this->log('任務開始');
        $this->log("入口地址: {$this->startUrl}");
        $startTime = microtime(true);
        $this->scrapePage($this->startUrl);
        $endTime  = microtime(true);
        $duration = $endTime - $startTime;
        $this->log('任務完成');
        $this->log('運轉時長: ' . round($duration, 2) . ' 秒');
    }

    /**
     * 获取页面内容并验证链接是否有效
     *
     * @param string $url 要爬取的 URL
     *
     * @return string|null 页面内容（无 Header 部分），若请求失败或内容无效则返回 null
     */
    private function fetchUrlContent(string $url): ?string
    {
        $response = $this->httpClient->get($url);

        if ($response === null) {
            return null;
        }

        if ($response['statusCode'] !== 200) {
            return null;
        }

        if (strpos($response['contentType'], 'text/html') === false) {
            return null;
        }

        return $this->extractHtmlContent($response['content']);
    }

    /**
     * 提取 HTML 内容（移除 Response Header）
     *
     * @param string $response 包含 Header 和 Body 的原始响应
     *
     * @return string 提取后的 HTML 内容
     */
    private function extractHtmlContent(string $response): string
    {
        $parts = preg_split("/\r\n\r\n|\n\n|\r\r/", $response, -1, PREG_SPLIT_NO_EMPTY);

        if ($parts === false) {
            return '';
        }
        $html = end($parts);

        return is_string($html) ? $html : '';
    }

    /**
     * 爬取单个页面
     */
    private function scrapePage(string $url): void
    {
        if ($this->storage->isProcessed($url)) {
            $this->log("URL已处理: $url");

            return;
        }

        $html = $this->fetchUrlContent($url);

        if ($html === null) {
            $this->log("無效鏈接，忽略: $url");

            return;
        }

        $document = $this->parsePageContent($html);

        if ($this->storage->save($url, $document)) {
            $this->log("内容已存储: $url");
        } else {
            $this->log("内容未变化，跳过存储: $url");
        }

        if ($this->crawlDelay > 0) {
            sleep($this->crawlDelay);
        }

        $links = $this->extractLinks($html);

        foreach ($links as $link) {
            $this->scrapePage($link);
        }
    }

    /**
     * 解析页面内容
     */
    private function parsePageContent(string $html): Document
    {
        $dom = new DOMDocument();

        // 防止无效 HTML 触发警告
        libxml_use_internal_errors(true);
        @$dom->loadHTML($html);
        libxml_clear_errors();

        // 获取meta
        $keywords    = '';
        $description = '';

        foreach ($dom->getElementsByTagName('meta') as $meta) {
            $name = strtolower($meta->getAttribute('name'));
            switch ($name) {
                case 'keywords':
                    $keywords = $meta->getAttribute('content') ?: $keywords;
                    break;
                case 'description':
                    $description = $meta->getAttribute('content') ?: $description;
                    break;
            }
        }

        // 提取语言
        $htmlNode = $dom->getElementsByTagName('html')->item(0);
        $lang     = $htmlNode ? $htmlNode->getAttribute('lang') : '';

        // 提取标题
        $titleNode = $dom->getElementsByTagName('title')->item(0);
        $title     = $titleNode ? ($titleNode->textContent ?? '') : '';

        // 提取正文
        $mainNode = $dom->getElementsByTagName('main')->item(0);

        if ($mainNode) {
            $content = $this->getTextContent($mainNode, true);
        } else {
            // 降级提取 <body> 标签内容
            $bodyNode = $dom->getElementsByTagName('body')->item(0);
            $content  = $bodyNode ? $this->getTextContent($bodyNode, true) : '';
        }

        return new Document($lang, $title, $description, $keywords, $content);
    }

    /**
     * 获取节点文本
     */
    private function getTextContent(DOMNode $node, bool $fastMode = false): string
    {
        if ($fastMode) {
            $ownerDoc = $node->ownerDocument;

            if ($ownerDoc === null) {
                return '';
            }

            $htmlContent = $ownerDoc->saveHTML($node);

            if ($htmlContent === false) {
                return '';
            }

            $htmlContent = preg_replace('/<script.*?>.*?<\/script>/is', '', $htmlContent) ?: '';
            $htmlContent = preg_replace('/<style.*?>.*?<\/style>/is', '', $htmlContent) ?: '';

            $content = strip_tags($htmlContent);
            $content = preg_replace('/\s+/', ' ', $content) ?: '';

            return trim($content);
        }

        $content = '';

        // 忽略不需要的标签
        $ignoredTags = ['script', 'style', 'noscript'];

        foreach ($node->childNodes as $child) {
            if ($child->nodeType === XML_TEXT_NODE) {
                // 直接获取文本节点内容
                $content .= trim($child->textContent) . ' ';
            } elseif ($child->nodeType === XML_ELEMENT_NODE && !in_array($child->nodeName, $ignoredTags, true)) {
                // 递归获取子节点内容
                $content .= $this->getTextContent($child, $fastMode);
            }
        }

        return $content;
    }

    /**
     * 提取页面中的链接
     *
     * @return string[] 返回链接列表
     */
    private function extractLinks(string $html): array
    {
        $dom = new DOMDocument();
        @$dom->loadHTML($html);  // 忽略警告

        $links = [];

        foreach ($dom->getElementsByTagName('a') as $link) {
            $href    = $link->getAttribute('href');
            $fullUrl = $this->resolveUrl($href);

            // 只有在获取的链接有效且不为 null 时，才添加
            if ($fullUrl !== null) {
                $links[] = $fullUrl;
            }
        }

        return $links;
    }

    /**
     * 解析链接地址为绝对 URL，并确保链接属于起始域名
     *
     * @param string $href <a> 标签中的原始 href 属性值
     *
     * @return string|null 解析后的绝对 URL，若链接无效或不属于当前域名则返回 null
     */
    private function resolveUrl(string $href): ?string
    {
        $parsedStart = parse_url($this->startUrl);
        $scheme      = $parsedStart['scheme'] ?? 'http';
        $host        = $parsedStart['host']   ?? '';
        $basePath    = rtrim(dirname($parsedStart['path'] ?? '/'), '/') . '/';

        if (strpos($href, 'http') === 0) {
            $absoluteUrl = $href;
        } else {
            $absoluteUrl = $scheme . '://' . $host;
            $absoluteUrl .= (strpos($href, '/') === 0) ? $href : $basePath . $href;
        }

        // 标准化 URL
        $absoluteUrl = $this->normalizeUrl($absoluteUrl);

        // 检查是否属于起始域名
        return strpos($absoluteUrl, $this->startUrl) === 0 ? $absoluteUrl : null;
    }

    /**
     * 规范化 URL，去掉末尾的斜杠等
     *
     * @param string $url 原始 URL
     *
     * @return string 规范化后的 URL
     */
    private function normalizeUrl(string $url): string
    {
        // 去掉 URL 末尾的斜杠
        return rtrim($url, '/');
    }

    /**
     * 记录日志
     */
    private function log(string $message, string $level = 'info'): void
    {
        $this->logger->log($level, $message);
        echo date('Y-m-d H:i:s') . " - $message" . PHP_EOL;
    }
}
