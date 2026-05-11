<?php

declare(strict_types=1);

namespace ChangHorizon\Crawler\Tests;

use ChangHorizon\Crawler\Crawler;
use ChangHorizon\Crawler\Http\HttpClientInterface;
use ChangHorizon\Crawler\Storage\DocumentStorageInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class CrawlerTest extends TestCase
{
    public function testScrapesAndStoresValidPage(): void
    {
        // 模拟已处理页面记录
        /** @var array<string, bool> $processedMap */
        $processedMap = [];

        // DocumentStorageInterface 模拟，带状态管理
        $storage = $this->createMock(DocumentStorageInterface::class);
        $storage->method('isProcessed')
            ->willReturnCallback(function (string $url) use (&$processedMap) {
                return $processedMap[$url] ?? false;
            });
        $storage->method('save')
            ->willReturnCallback(function (string $url, $document) use (&$processedMap) {
                $processedMap[$url] = true;

                return true;
            });

        // LoggerInterface 模拟，log 返回 void，直接 stub 不用返回值
        $logger = $this->createMock(LoggerInterface::class);
        $logger->method('log')->willReturnCallback(fn () => null);

        // HttpClientInterface 模拟，get 返回 ?array，符合接口定义
        $httpClient = $this->createMock(HttpClientInterface::class);
        $httpClient->method('get')
            ->willReturn([
                'statusCode'  => 200,
                'contentType' => 'text/html',
                'content'     => <<<HTML
                        <html lang="en">
                            <head>
                                <title>Sub Page</title>
                                <meta name="description" content="Sub page description.">
                                <meta name="keywords" content="sub, page">
                            </head>
                            <body>
                                <main>
                                    <p>Sub page content</p>
                                    <a href="/subpage">Sub</a>
                                </main>
                            </body>
                        </html>
                    HTML,
            ]);

        // 使用匿名类覆写 extractLinks，模拟返回子链接
        $crawler = new class ('https://example.com', $httpClient, $storage, $logger) extends Crawler {
            /**
             * @return string[]
             */
            protected function extractLinks(string $html): array
            {
                return ['https://example.com/subpage'];
            }
        };

        $crawler->run();

        // 断言示例，至少爬取入口页和子页面都被处理
        $this->assertTrue($processedMap['https://example.com'] ?? false);
        $this->assertTrue($processedMap['https://example.com/subpage'] ?? false);
    }

    public function testSkipsAlreadyProcessedUrl(): void
    {
        $storage = $this->createMock(DocumentStorageInterface::class);
        $storage->method('isProcessed')->willReturn(true);

        $logger = $this->createStub(LoggerInterface::class);
        $logger->method('log');

        $httpClient = $this->createMock(HttpClientInterface::class);

        $crawler = new Crawler('https://example.com', $httpClient, $storage, $logger);

        $crawler->run();

        $this->expectNotToPerformAssertions();
    }

    public function testSkipsInvalidHttpResponse(): void
    {
        $storage = $this->createMock(DocumentStorageInterface::class);
        $storage->method('isProcessed')->willReturn(false);

        $logger = $this->createStub(LoggerInterface::class);
        $logger->method('log');

        $httpClient = $this->createMock(HttpClientInterface::class);
        // 回傳 null 代表失敗或無效回應
        $httpClient->method('get')->willReturn(null);

        $crawler = new Crawler('https://example.com', $httpClient, $storage, $logger);

        $crawler->run();

        $this->expectNotToPerformAssertions();
    }
}
