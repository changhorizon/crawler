# Crawler

> Effortless recursive web crawling for comprehensive site indexing

![License](https://img.shields.io/github/license/changhorizon/crawler?style=flat-square)
![Latest Version](https://img.shields.io/packagist/v/changhorizon/crawler?style=flat-square)
![PHP Version](https://img.shields.io/badge/php-8.2--8.4-blue?style=flat-square)
![Static Analysis](https://img.shields.io/badge/static_analysis-PHPStan-blue?style=flat-square)
![Tests](https://img.shields.io/badge/tests-PHPUnit-brightgreen?style=flat-square)
[![codecov](https://codecov.io/gh/changhorizon/crawler/branch/main/graph/badge.svg)](https://codecov.io/gh/changhorizon/crawler)
![CI](https://github.com/changhorizon/crawler/actions/workflows/ci.yml/badge.svg?style=flat-square)

A high-performance PHP web crawler library designed to recursively scrape all pages of a single website.This library is ideal for building site search, content analysis, SEO data collection, and similar projects. It is designed to be simple, extensible, and serve as a solid foundation for web crawling and data extraction.

## ✨ 特性

- **递归爬取**：自动提取页面内链接，广度优先遍历站点所有可访问页面
- **内容解析**：提取页面标题、关键词、描述以及正文文本，过滤无用标签和脚本
- **增量更新**：通过内容哈希值检测页面变更，避免重复写入数据库，提高效率
- **日志记录**：内置 PSR-3 日志接口，方便集成多种日志方案，实时跟踪爬取过程
- **多语言支持**：自动识别页面语言（lang属性），便于多语站点索引和处理
- **持久化存储**：采用 PDO 操作 MySQL，支持页面数据入库，方便后续搜索索引或分析
- **可配置延时**：支持爬取间隔时间设置，降低对目标站点的压力


## 📦 安装

```bash
composer require changhorizon/crawler
```

## 📂 目录结构

```txt
src
├── Crawler.php
├── Document.php
├── Http
│   ├── CurlHttpClient.php
│   └── HttpClientInterface.php
└── Storage
    ├── DocumentStorageInterface.php
    └── PdoDocumentStorage.php
```

## 🚀 用法示例

### 示例 1：基础爬取示例

```php
use ChangHorizon\Crawler\Crawler;
use ChangHorizon\Crawler\Http\CurlHttpClient;
use ChangHorizon\Crawler\Storage\PdoDocumentStorage;
use Psr\Log\NullLogger;

$startUrl = 'https://example.com';

$httpClient = new CurlHttpClient();
$storage = new PdoDocumentStorage($pdoConnection); // $pdoConnection 为已初始化的 PDO 实例
$logger = new NullLogger(); // 可替换为任何 PSR-3 实现，如 Monolog

$crawler = new Crawler($startUrl, $httpClient, $storage, $logger);
$crawler->run();
```

### 示例 2：自定义爬取间隔

```php
$crawlDelay = 3;

$crawler = new Crawler($startUrl, $httpClient, $storage, $logger, $crawlDelay);
$crawler->run();
```

## 📐 接口说明

### Crawler::__construct

> 构造函数，创建一个爬虫实例。

```php
public function __construct(
    string $startUrl,
    HttpClientInterface $httpClient,
    DocumentStorageInterface $storage,
    LoggerInterface $logger,
    int $crawlDelay = 1,
);
```

- **$startUrl**：起始 URL。
- **$httpClient**：实现 `HttpClientInterface` 的 HTTP 客户端
- **$storage**：实现 `DocumentStorageInterface` 的文档存储实现
- **$logger**：实现 `LoggerInterface`（PSR-3 标准）的日志处理器
- **$crawlDelay**：每次抓取之间的间隔，单位为秒

### Crawler::run

> 启动爬虫任务。

```php
public function run(): void
```

### Crawler::fetchUrlContent

> 获取 HTML 页面正文内容，过滤掉响应头，若请求无效返回 null。

```php
private function fetchUrlContent(string $url): ?string
```

### Crawler::scrapePage

> 抓取并解析页面，存储内容，并递归抓取内部链接。

```php
private function scrapePage(string $url): void
```

### Crawler::extractLinks

> 从 HTML 中提取合法的绝对链接。

```php
private function extractLinks(string $html): array
```

### Crawler::resolveUrl

> 将相对链接解析为绝对 URL，仅保留与起始 URL 同源的链接。

```php
private function resolveUrl(string $href): ?string
```

### ChangHorizon\Crawler\Storage\DocumentStorageInterface

> 定义用于文档存储的接口，包含存储与状态检测方法。

```php
interface DocumentStorageInterface
{
    /**
     * 保存或更新文档内容。
     *
     * @param string $url
     * @param Document $document
     * @return bool 返回 true 表示内容发生变化已保存
     */
    public function save(string $url, Document $document): bool;

    /**
     * 检查指定 URL 是否已处理过。
     *
     * @param string $url
     * @return bool
     */
    public function isProcessed(string $url): bool;
}
```

### ChangHorizon\Crawler\Http\HttpClientInterface

> 定义用于发起 HTTP 请求的接口。

```php
interface HttpClientInterface
{
    /**
     * 发送 GET 请求。
     *
     * @param string $url
     * @return array|null 包含 statusCode, contentType 和 content，若失败则为 null
     */
    public function get(string $url): ?array;
}
```

**备注：** 所有接口实现均来自包内组件，适合用于扩展和替换。爬虫本体逻辑通过 `DocumentStorageInterface` 和 `HttpClientInterface` 解耦，便于测试与复用。

## 🔍 静态分析

使用 PHPStan 工具进行静态分析，确保代码的质量和一致性：

```bash
composer stan
```

## 🎯 代码风格

使用 PHP-CS-Fixer 工具检查代码风格：

```bash
composer cs:chk
```

使用 PHP-CS-Fixer 工具自动修复代码风格问题：

```bash
composer cs:fix
```

## ✅ 单元测试

执行 PHPUnit 单元测试：

```bash
composer test
```

执行 PHPUnit 单元测试并生成代码覆盖率报告：

```bash
composer test:coverage
```

## 🤝 贡献指南

欢迎 Issue 与 PR，建议遵循以下流程：

1. Fork 仓库
2. 创建新分支进行开发
3. 提交 PR 前请确保测试通过、风格一致
4. 提交详细描述

## 📜 License

MIT License. See the [LICENSE](LICENSE) file for details.
