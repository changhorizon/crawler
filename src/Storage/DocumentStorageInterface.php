<?php

declare(strict_types=1);

namespace Hizpark\Crawler\Storage;

use Hizpark\Crawler\Document;

interface DocumentStorageInterface
{
    /**
     * 保存或更新文檔
     */
    public function save(string $url, Document $document): bool;

    /**
     * 檢查URL是否已處理過
     */
    public function isProcessed(string $url): bool;
}
