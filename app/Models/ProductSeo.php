<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'product_id',
    'meta_title',
    'meta_description',
    'focus_keyword',
    'canonical_url',
    'robots_index',
    'robots_follow',
    'og_title',
    'og_description',
    'og_image_id',
    'twitter_title',
    'twitter_description',
    'twitter_image_id',
])]
class ProductSeo extends Model
{
    use HasFactory, SoftDeletes;

    protected function casts(): array
    {
        return [
            'robots_index' => 'boolean',
            'robots_follow' => 'boolean',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function ogImage(): BelongsTo
    {
        return $this->belongsTo(StoredFile::class, 'og_image_id');
    }

    public function twitterImage(): BelongsTo
    {
        return $this->belongsTo(StoredFile::class, 'twitter_image_id');
    }
}
