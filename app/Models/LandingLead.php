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
        'email',
        'product_type',
        'business_type',
        'quantity_range',
        'design_readiness',
        'delivery_date',
        'artwork_path',
        'notes',
        'city',
        'source',
        'page_url',
        'utm_source',
        'utm_medium',
        'utm_campaign',
        'utm_content',
        'status',
    ];
}
