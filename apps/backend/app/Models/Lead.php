<?php

namespace App\Models;

use Database\Factories\LeadFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

/**
 * Approved lead sources.
 */
const LEAD_SOURCES = [
    'website_bulk_enquiry',
    'manual',
    'phone',
    'whatsapp',
    'email',
    'referral',
    'import',
];

/**
 * Approved lead statuses.
 */
const LEAD_STATUSES = [
    'new',
    'assigned',
    'contacted',
    'qualified',
    'quoted',
    'won',
    'lost',
    'spam',
];

/**
 * Approved lead priorities.
 */
const LEAD_PRIORITIES = [
    'low',
    'normal',
    'high',
    'urgent',
];

#[Fillable([
    'public_id',
    'customer_id',
    'assigned_to_user_id',
    'created_by_user_id',
    'source',
    'source_detail',
    'status',
    'priority',
    'contact_name',
    'company_name',
    'email',
    'phone',
    'city',
    'state',
    'country_code',
    'interest_summary',
    'requirements',
    'product_interest',
    'utm_source',
    'utm_medium',
    'utm_campaign',
    'utm_content',
    'utm_term',
    'referrer_url',
    'landing_page_url',
    'last_contacted_at',
    'qualified_at',
    'lost_at',
    'lost_reason',
    'converted_at',
])]
#[Hidden(['deleted_at'])]
class Lead extends Model
{
    /** @use HasFactory<LeadFactory> */
    use HasFactory, SoftDeletes;

    public const SOURCES = LEAD_SOURCES;

    public const STATUSES = LEAD_STATUSES;

    public const PRIORITIES = LEAD_PRIORITIES;

    public const VALID_TRANSITIONS = [
        'new' => ['assigned', 'contacted', 'lost', 'spam'],
        'assigned' => ['contacted', 'qualified', 'lost', 'spam'],
        'contacted' => ['assigned', 'qualified', 'lost', 'spam'],
        'qualified' => ['quoted', 'lost', 'spam'],
        'quoted' => ['won', 'lost', 'spam'],
        'lost' => ['new'],
        'spam' => ['new'],
        'won' => [], // Terminal state
    ];

    public function canTransitionTo(string $targetStatus): bool
    {
        $allowed = self::VALID_TRANSITIONS[$this->status] ?? [];

        return in_array($targetStatus, $allowed, true);
    }

    protected static function booted(): void
    {
        static::creating(function (Lead $lead): void {
            $lead->public_id ??= 'LD-'.Str::upper(Str::random(12));
            $lead->status ??= 'new';
            $lead->priority ??= 'normal';
            $lead->country_code ??= 'IN';
        });
    }

    protected function casts(): array
    {
        return [
            'product_interest' => 'array',
            'last_contacted_at' => 'datetime',
            'qualified_at' => 'datetime',
            'lost_at' => 'datetime',
            'converted_at' => 'datetime',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'public_id';
    }

    public function isOpen(): bool
    {
        return ! in_array($this->status, ['won', 'lost', 'spam'], true);
    }

    public function isConverted(): bool
    {
        return $this->status === 'won' && $this->converted_at !== null;
    }

    public function hasContactRoute(): bool
    {
        return $this->email !== null
            || $this->phone !== null
            || $this->customer_id !== null;
    }

    // ------------------------------------------------------------------ scopes

    public function scopeOpen(Builder $query): Builder
    {
        return $query->whereNotIn('status', ['won', 'lost', 'spam']);
    }

    public function scopeAssignedTo(Builder $query, int $userId): Builder
    {
        return $query->where('assigned_to_user_id', $userId);
    }

    public function scopeByStatus(Builder $query, string $status): Builder
    {
        return $query->where('status', $status);
    }

    public function scopeBySource(Builder $query, string $source): Builder
    {
        return $query->where('source', $source);
    }

    public function scopeByPriority(Builder $query, string $priority): Builder
    {
        return $query->where('priority', $priority);
    }

    // --------------------------------------------------------------- relations

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to_user_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function quotations(): HasMany
    {
        return $this->hasMany(Quotation::class);
    }

    public function activities(): HasMany
    {
        return $this->hasMany(LeadActivity::class)->orderBy('occurred_at');
    }
}
