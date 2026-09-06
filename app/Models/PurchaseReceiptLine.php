<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseReceiptLine extends Model
{
    use HasFactory;

    protected $fillable = [
        'purchase_receipt_id',
        'vendor_order_item_id',
        'product_sku_id',
        'quantity_received',
    ];

    protected $casts = [
        'quantity_received' => 'integer',
    ];

    public function receipt(): BelongsTo
    {
        return $this->belongsTo(PurchaseReceipt::class, 'purchase_receipt_id');
    }

    public function vendorOrderItem(): BelongsTo
    {
        return $this->belongsTo(VendorOrderItem::class, 'vendor_order_item_id');
    }

    public function productSku(): BelongsTo
    {
        return $this->belongsTo(ProductSku::class, 'product_sku_id');
    }
}
