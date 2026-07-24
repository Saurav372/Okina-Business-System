<?php

namespace App\Models;

use App\Enums\InventoryLocation;
use App\Enums\WarehouseTransferStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WarehouseTransfer extends Model
{
    use HasFactory;

    protected $fillable = [
        'transfer_code',
        'product_sku_id',
        'source_location',
        'destination_location',
        'quantity',
        'status',
        'initiated_by_user_id',
        'completed_by_user_id',
        'shipped_at',
        'completed_at',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'product_sku_id' => 'integer',
            'quantity' => 'integer',
            'source_location' => InventoryLocation::class,
            'destination_location' => InventoryLocation::class,
            'status' => WarehouseTransferStatus::class,
            'initiated_by_user_id' => 'integer',
            'completed_by_user_id' => 'integer',
            'shipped_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function productSku(): BelongsTo
    {
        return $this->belongsTo(ProductSku::class, 'product_sku_id');
    }

    public function initiator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'initiated_by_user_id');
    }

    public function completer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'completed_by_user_id');
    }
}
