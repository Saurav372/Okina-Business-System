<?php

namespace App\Models;

use Database\Factories\ProductCategoryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

#[Fillable([
    'name',
    'slug',
    'description',
    'status',
    'seo_title',
    'seo_description',
    'sort_order',
    'published_at',
])]
#[Hidden(['deleted_at'])]
class ProductCategory extends Model
{
    /** @use HasFactory<ProductCategoryFactory> */
    use HasFactory, SoftDeletes;

    protected static function booted(): void
    {
        static::creating(function (ProductCategory $category): void {
            $category->slug ??= Str::slug($category->name);
            $category->status ??= 'active';
        });
    }

    protected function casts(): array
    {
        return [
            'published_at' => 'datetime',
        ];
    }

    public function isPubliclyVisible(): bool
    {
        return $this->status === 'active'
            && $this->deleted_at === null
            && ($this->published_at === null || $this->published_at->isPast());
    }

    public function scopePubliclyVisible(Builder $query): Builder
    {
        return $query
            ->where('status', 'active')
            ->where(function (Builder $query): void {
                $query->whereNull('published_at')
                    ->orWhere('published_at', '<=', now());
            })
            ->whereNull('deleted_at');
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class, 'primary_category_id');
    }
}
