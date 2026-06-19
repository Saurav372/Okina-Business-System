<?php

namespace App\Models;

use Database\Factories\SettingFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

#[Fillable([
    'group_name',
    'key',
    'value',
    'value_type',
    'description',
    'is_secret',
])]
class Setting extends Model
{
    /** @use HasFactory<SettingFactory> */
    use HasFactory;

    public const GROUP_BUSINESS = 'business';

    public const GROUP_PAYMENT = 'payment';

    public const GROUP_NOTIFICATION = 'notification';

    public const GROUP_SEO = 'seo';

    public const GROUP_UPLOAD = 'upload';

    public const GROUP_INTEGRATION = 'integration';

    public const GROUPS = [
        self::GROUP_BUSINESS,
        self::GROUP_PAYMENT,
        self::GROUP_NOTIFICATION,
        self::GROUP_SEO,
        self::GROUP_UPLOAD,
        self::GROUP_INTEGRATION,
    ];

    public const GROUP_LABELS = [
        self::GROUP_BUSINESS => 'Business',
        self::GROUP_PAYMENT => 'Payment',
        self::GROUP_NOTIFICATION => 'Notification',
        self::GROUP_SEO => 'SEO',
        self::GROUP_UPLOAD => 'Upload',
        self::GROUP_INTEGRATION => 'Integration',
    ];

    public const TYPE_STRING = 'string';

    public const TYPE_BOOLEAN = 'boolean';

    public const TYPE_INTEGER = 'integer';

    public const TYPE_FLOAT = 'float';

    public const TYPE_ARRAY = 'array';

    public const TYPE_NULL = 'null';

    public const VALUE_TYPES = [
        self::TYPE_STRING,
        self::TYPE_BOOLEAN,
        self::TYPE_INTEGER,
        self::TYPE_FLOAT,
        self::TYPE_ARRAY,
        self::TYPE_NULL,
    ];

    protected function casts(): array
    {
        return [
            'value' => 'array',
            'is_secret' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (self $setting): void {
            $setting->group_name = self::normalizeGroupName($setting->group_name);
            $setting->key = self::normalizeKey($setting->key);
            $setting->value_type ??= self::inferValueType($setting->value);
        });
    }

    public function scopeForGroup(Builder $query, string $group): Builder
    {
        return $query->where('group_name', self::normalizeGroupName($group));
    }

    public function scopeForKey(Builder $query, string $group, string $key): Builder
    {
        return $query->where('group_name', self::normalizeGroupName($group))
            ->where('key', self::normalizeKey($key));
    }

    public function qualifiedKey(): string
    {
        return $this->group_name.'.'.$this->key;
    }

    public static function normalizeGroupName(string $group): string
    {
        return Str::of($group)->snake()->lower()->toString();
    }

    public static function normalizeKey(string $key): string
    {
        return Str::of($key)->snake()->lower()->toString();
    }

    public static function inferValueType(mixed $value): string
    {
        return match (true) {
            is_bool($value) => self::TYPE_BOOLEAN,
            is_int($value) => self::TYPE_INTEGER,
            is_float($value) => self::TYPE_FLOAT,
            is_array($value) => self::TYPE_ARRAY,
            $value === null => self::TYPE_NULL,
            default => self::TYPE_STRING,
        };
    }
}
