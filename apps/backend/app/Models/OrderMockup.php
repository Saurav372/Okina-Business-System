<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderMockup extends Model
{
    protected $fillable = [
        'order_id',
        'stored_file_id',
        'display_name',
        'is_featured',
        'sort_order',
        'notes',
    ];

    protected $casts = [
        'is_featured' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function file(): BelongsTo
    {
        return $this->belongsTo(StoredFile::class, 'stored_file_id');
    }
}
