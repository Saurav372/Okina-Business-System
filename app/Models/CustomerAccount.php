<?php

namespace App\Models;

use Database\Factories\CustomerAccountFactory;
use Illuminate\Auth\Passwords\CanResetPassword as CanResetPasswordTrait;
use Illuminate\Contracts\Auth\CanResetPassword;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable([
    'customer_id',
    'email',
    'normalized_email',
    'password',
    'status',
    'email_verified_at',
    'last_login_at',
    'last_login_ip',
    'failed_login_attempts',
    'locked_until',
    'password_changed_at',
    'disabled_at',
])]
#[Hidden(['password', 'remember_token'])]
class CustomerAccount extends Authenticatable implements CanResetPassword
{
    /** @use HasFactory<CustomerAccountFactory> */
    use CanResetPasswordTrait, HasFactory, Notifiable;

    public const STATUS_PENDING_VERIFICATION = 'pending_verification';

    public const STATUS_ACTIVE = 'active';

    public const STATUS_SUSPENDED = 'suspended';

    public const STATUS_DISABLED = 'disabled';

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function canAccessCustomerAccount(): bool
    {
        return $this->status === self::STATUS_ACTIVE
            && $this->hasVerifiedEmail()
            && ! $this->isTemporarilyLocked();
    }

    public function isTemporarilyLocked(): bool
    {
        return $this->locked_until !== null && $this->locked_until->isFuture();
    }

    public static function normalizeEmail(string $email): string
    {
        return str($email)->trim()->lower()->toString();
    }

    public function sendPasswordResetNotification(#[\SensitiveParameter] $token): void
    {
        $this->notify(new \App\Notifications\CustomerResetPasswordNotification($token));
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'locked_until' => 'datetime',
            'last_login_at' => 'datetime',
            'password_changed_at' => 'datetime',
            'disabled_at' => 'datetime',
        ];
    }
}
