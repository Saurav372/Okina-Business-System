<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LandingLead extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'phone',
        'product_type',
        'quantity_range',
        'delivery_date',
        'artwork_path',
        'notes',
        'city',
        'source',
        'utm_source',
        'utm_medium',
        'utm_campaign',
        'utm_content',
        'status',
    ];
}
