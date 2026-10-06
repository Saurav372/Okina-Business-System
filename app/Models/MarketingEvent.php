<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class MarketingEvent extends Model
{
    use HasFactory;

    public $timestamps = false; // Only has created_at

    protected $fillable = [
        'event_id',
        'event_name',
        'source',
        'landing_page_slug',
        'utm_source',
        'utm_medium',
        'utm_campaign',
        'utm_content',
        'status',
        'payload',
        'ip_address',
        'user_agent',
        'created_at',
    ];

    protected $casts = [
        'payload' => 'array',
        'created_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->event_id)) {
                $model->event_id = (string) Str::uuid();
            }
            if (empty($model->created_at)) {
                $model->created_at = now();
            }
        });
    }
}
