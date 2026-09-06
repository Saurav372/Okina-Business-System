<?php

namespace App\Contracts;

use App\Models\StoredFile;
use Illuminate\Database\Eloquent\Model;

interface SeoableContract
{
    /**
     * Get the attached SEO model instance if available.
     */
    public function getSeo(): ?Model;

    /**
     * Fallback title if explicit meta_title is not set.
     */
    public function getSeoTitleFallback(): string;

    /**
     * Fallback description if explicit meta_description is not set.
     */
    public function getSeoDescriptionFallback(): ?string;

    /**
     * Fallback canonical URL if explicit canonical_url is not set.
     */
    public function getSeoCanonicalUrlFallback(): string;

    /**
     * Fallback StoredFile model for OpenGraph/Twitter image if explicit image is not set.
     */
    public function getSeoImageFallback(): ?StoredFile;

    /**
     * Hierarchical breadcrumbs array for SERP / Schema.org rendering.
     * Array structure: [['name' => 'Home', 'url' => '...'], ...]
     *
     * @return array<int, array{name: string, url: string}>
     */
    public function getSeoBreadcrumbs(): array;
}
