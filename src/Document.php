<?php

declare(strict_types=1);

namespace Hizpark\Crawler;

class Document
{
    private string $lang;

    private string $title;

    private string $description;

    private string $keywords;

    private string $content;

    // 构造函数
    public function __construct(string $lang, string $title, string $description, string $keywords, string $content)
    {
        $this->lang        = $lang;
        $this->title       = $title;
        $this->description = $description;
        $this->keywords    = $keywords;
        $this->content     = $content;
    }

    public function getLang(): string
    {
        return $this->lang;
    }

    public function setLang(string $lang): void
    {
        $this->lang = $lang;
    }

    // Getter 和 Setter 方法
    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): void
    {
        $this->title = $title;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function setDescription(string $description): void
    {
        $this->description = $description;
    }

    public function getKeywords(): string
    {
        return $this->keywords;
    }

    public function setKeywords(string $keywords): void
    {
        $this->keywords = $keywords;
    }

    public function getContent(): string
    {
        return $this->content;
    }

    public function setContent(string $content): void
    {
        $this->content = $content;
    }
}
