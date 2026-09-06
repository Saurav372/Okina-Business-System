<?php

namespace App\Support\Seo\Presenters;

use App\Contracts\SeoableContract;
use App\Models\ProductSeo;
use App\Models\StoredFile;
use App\Support\Seo\JsonLd\ProductSchemaGenerator;

class ProductSeoPresenter
{
    protected ?ProductSeo $seo;

    public function __construct(
        protected SeoableContract $subject
    ) {
        $seoModel = $this->subject->getSeo();
        $this->seo = $seoModel instanceof ProductSeo ? $seoModel : null;
    }

    public function metaTitle(): string
    {
        if ($this->seo && ! empty($this->seo->meta_title)) {
            return $this->seo->meta_title;
        }

        return $this->subject->getSeoTitleFallback();
    }

    public function metaDescription(): ?string
    {
        if ($this->seo && ! empty($this->seo->meta_description)) {
            return $this->seo->meta_description;
        }

        return $this->subject->getSeoDescriptionFallback();
    }

    public function focusKeyword(): ?string
    {
        return $this->seo?->focus_keyword;
    }

    public function canonical(): string
    {
        if ($this->seo && ! empty($this->seo->canonical_url)) {
            return $this->seo->canonical_url;
        }

        return $this->subject->getSeoCanonicalUrlFallback();
    }

    public function robotsIndex(): bool
    {
        return $this->seo?->robots_index ?? true;
    }

    public function robotsFollow(): bool
    {
        return $this->seo?->robots_follow ?? true;
    }

    public function robots(): string
    {
        $indexStr = $this->robotsIndex() ? 'index' : 'noindex';
        $followStr = $this->robotsFollow() ? 'follow' : 'nofollow';

        return "{$indexStr},{$followStr}";
    }

    public function ogTitle(): string
    {
        if ($this->seo && ! empty($this->seo->og_title)) {
            return $this->seo->og_title;
        }

        return $this->metaTitle();
    }

    public function ogDescription(): ?string
    {
        if ($this->seo && ! empty($this->seo->og_description)) {
            return $this->seo->og_description;
        }

        return $this->metaDescription();
    }

    public function ogImage(): ?array
    {
        if ($this->seo && $this->seo->ogImage) {
            return $this->formatFileArray($this->seo->ogImage);
        }

        $fallbackFile = $this->subject->getSeoImageFallback();
        if ($fallbackFile) {
            return $this->formatFileArray($fallbackFile);
        }

        return null;
    }

    public function twitterTitle(): string
    {
        if ($this->seo && ! empty($this->seo->twitter_title)) {
            return $this->seo->twitter_title;
        }

        return $this->ogTitle();
    }

    public function twitterDescription(): ?string
    {
        if ($this->seo && ! empty($this->seo->twitter_description)) {
            return $this->seo->twitter_description;
        }

        return $this->ogDescription();
    }

    public function twitterImage(): ?array
    {
        if ($this->seo && $this->seo->twitterImage) {
            return $this->formatFileArray($this->seo->twitterImage);
        }

        return $this->ogImage();
    }

    public function openGraph(): array
    {
        return [
            'title' => $this->ogTitle(),
            'description' => $this->ogDescription(),
            'image' => $this->ogImage(),
        ];
    }

    public function twitter(): array
    {
        return [
            'title' => $this->twitterTitle(),
            'description' => $this->twitterDescription(),
            'image' => $this->twitterImage(),
        ];
    }

    public function jsonLd(): array
    {
        return (new ProductSchemaGenerator)->generate($this->subject, $this);
    }

    public function jsonLdFormatted(): string
    {
        return json_encode(
            $this->jsonLd(),
            JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
        ) ?: '{}';
    }

    protected function formatFileArray(StoredFile $file): array
    {
        return [
            'id' => $file->id,
            'public_id' => $file->public_id,
            'original_filename' => $file->original_filename,
            'url' => route('files.preview', $file),
        ];
    }
}
