<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'product_id',
    'file_id',
    'role',
    'alt_text',
    'sort_order',
])]
class ProductMedia extends Model
{
    public const ROLE_COVER = 'cover';

    public const ROLE_GALLERY = 'gallery';

    public const ROLES = [
        self::ROLE_COVER,
        self::ROLE_GALLERY,
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function file(): BelongsTo
    {
        return $this->belongsTo(StoredFile::class, 'file_id');
    }

    public function isCover(): bool
    {
        return $this->role === self::ROLE_COVER;
    }

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
        ];
    }
}
