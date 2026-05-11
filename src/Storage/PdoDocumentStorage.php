<?php

declare(strict_types=1);

namespace ChangHorizon\Crawler\Storage;

use ChangHorizon\Crawler\Document;
use PDO;

class PdoDocumentStorage implements DocumentStorageInterface
{
    public function __construct(
        private PDO $pdo,
        private bool $skipUnchanged = true, // 是否跳过未变化内容
    ) {
    }

    public function isProcessed(string $url): bool
    {
        $urlHash = $this->hashUrl($url);
        $stmt    = $this->pdo->prepare(
            'SELECT 1 FROM documents WHERE hash = ? LIMIT 1',
        );
        $stmt->execute([$urlHash]);

        return (bool)$stmt->fetch();
    }

    public function save(string $url, Document $document): bool
    {
        $urlHash     = $this->hashUrl($url);
        $contentHash = $this->calculateContentHash($document);

        // 检查内容是否需要更新
        if ($this->skipUnchanged && !$this->needsUpdate($urlHash, $contentHash)) {
            return false;
        }

        // 执行存储
        $stmt = $this->pdo->prepare(
            'INSERT INTO documents (
                url, hash, lang, title, content, keywords, description,
                crawled_time, content_hash
            ) VALUES (
                :url, :hash, :lang, :title, :content, :keywords, :description,
                NOW(), :content_hash
            ) ON DUPLICATE KEY UPDATE
                content = VALUES(content),
                crawled_time = VALUES(crawled_time),
                content_hash = VALUES(content_hash)',
        );

        return $stmt->execute([
            ':url'          => $url,
            ':hash'         => $urlHash,
            ':lang'         => $document->getLang(),
            ':title'        => mb_substr($document->getTitle(), 0, 512),
            ':content'      => $document->getContent(),
            ':keywords'     => mb_substr($document->getKeywords(), 0, 255),
            ':description'  => mb_substr($document->getDescription(), 0, 1024),
            ':content_hash' => $contentHash,
        ]);
    }

    private function needsUpdate(string $urlHash, string $contentHash): bool
    {
        $stmt = $this->pdo->prepare(
            'SELECT content_hash FROM documents WHERE hash = ? LIMIT 1',
        );
        $stmt->execute([$urlHash]);
        $existing = $stmt->fetch(PDO::FETCH_ASSOC);

        // 添加类型检查
        if (!is_array($existing) || !array_key_exists('content_hash', $existing)) {
            return true;
        }

        return $existing['content_hash'] !== $contentHash;
    }

    private function hashUrl(string $url): string
    {
        return hash('sha256', parse_url($url, PHP_URL_PATH) ?: $url);
    }

    private function calculateContentHash(Document $document): string
    {
        return hash('sha256', implode('|', [
            $document->getLang(),
            $document->getTitle(),
            $document->getContent(),
            $document->getKeywords(),
            $document->getDescription(),
        ]));
    }
}
