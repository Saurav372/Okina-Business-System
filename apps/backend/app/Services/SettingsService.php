<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Database\Eloquent\Collection;

class SettingsService
{
    public function groups(): array
    {
        return collect($this->definitions())
            ->mapWithKeys(function (array $group, string $groupName): array {
                return [$groupName => [
                    'label' => $group['label'],
                    'description' => $group['description'],
                    'keys' => array_keys($group['settings']),
                ]];
            })
            ->all();
    }

    public function definition(string $group, ?string $key = null): ?array
    {
        $group = Setting::normalizeGroupName($group);
        $definitions = $this->definitions();

        if (! array_key_exists($group, $definitions)) {
            return null;
        }

        if ($key === null) {
            return $definitions[$group];
        }

        $key = Setting::normalizeKey($key);

        return $definitions[$group]['settings'][$key] ?? null;
    }

    public function default(string $group, string $key, mixed $fallback = null): mixed
    {
        $definition = $this->definition($group, $key);

        return $definition['default'] ?? $fallback;
    }

    public function get(string $group, string $key, mixed $fallback = null): mixed
    {
        $group = Setting::normalizeGroupName($group);
        $key = Setting::normalizeKey($key);

        $setting = Setting::query()->forKey($group, $key)->first();

        return $setting?->value ?? $this->default($group, $key, $fallback);
    }

    public function all(string $group): array
    {
        $group = Setting::normalizeGroupName($group);
        $definition = $this->definition($group);

        if ($definition === null) {
            return [];
        }

        $values = [];

        foreach (array_keys($definition['settings']) as $key) {
            $values[$key] = $this->get($group, $key);
        }

        return $values;
    }

    public function records(string $group): Collection
    {
        return Setting::query()
            ->forGroup($group)
            ->orderBy('key')
            ->get();
    }

    public function set(string $group, string $key, mixed $value, array $attributes = []): Setting
    {
        $group = Setting::normalizeGroupName($group);
        $key = Setting::normalizeKey($key);
        $definition = $this->definition($group, $key);

        if ($definition === null) {
            throw new \InvalidArgumentException("Unknown setting: {$group}.{$key}");
        }

        return Setting::query()->updateOrCreate(
            [
                'group_name' => $group,
                'key' => $key,
            ],
            [
                'value' => $value,
                'value_type' => $attributes['value_type'] ?? $definition['value_type'] ?? Setting::inferValueType($value),
                'description' => $attributes['description'] ?? $definition['description'] ?? null,
                'is_secret' => $attributes['is_secret'] ?? ($definition['is_secret'] ?? false),
            ],
        );
    }

    public function seedDefaults(): void
    {
        foreach ($this->definitions() as $groupName => $group) {
            foreach ($group['settings'] as $key => $definition) {
                Setting::query()->firstOrCreate(
                    [
                        'group_name' => $groupName,
                        'key' => $key,
                    ],
                    [
                        'value' => $definition['default'],
                        'value_type' => $definition['value_type'],
                        'description' => $definition['description'] ?? null,
                        'is_secret' => $definition['is_secret'] ?? false,
                    ],
                );
            }
        }
    }

    private function definitions(): array
    {
        return [
            Setting::GROUP_BUSINESS => [
                'label' => 'Business',
                'description' => 'Company identity and shared commercial defaults.',
                'settings' => [
                    'company_name' => [
                        'label' => 'Company name',
                        'value_type' => Setting::TYPE_STRING,
                        'default' => config('app.name', 'Okina Craft'),
                        'description' => 'Display name used across customer-facing content.',
                    ],
                    'legal_name' => [
                        'label' => 'Legal name',
                        'value_type' => Setting::TYPE_STRING,
                        'default' => null,
                        'description' => 'Registered legal entity name for invoices and policies.',
                    ],
                    'support_email' => [
                        'label' => 'Support email',
                        'value_type' => Setting::TYPE_STRING,
                        'default' => null,
                        'description' => 'Public support contact email address.',
                    ],
                    'support_phone' => [
                        'label' => 'Support phone',
                        'value_type' => Setting::TYPE_STRING,
                        'default' => null,
                        'description' => 'Public support phone number.',
                    ],
                    'default_currency' => [
                        'label' => 'Default currency',
                        'value_type' => Setting::TYPE_STRING,
                        'default' => 'INR',
                        'description' => 'Base currency used by catalog and orders.',
                    ],
                    'timezone' => [
                        'label' => 'Timezone',
                        'value_type' => Setting::TYPE_STRING,
                        'default' => config('app.timezone', 'Asia/Kolkata'),
                        'description' => 'Operational timezone for records and schedules.',
                    ],
                    'tax_inclusive_pricing' => [
                        'label' => 'Tax inclusive pricing',
                        'value_type' => Setting::TYPE_BOOLEAN,
                        'default' => false,
                        'description' => 'Whether displayed prices already include tax.',
                    ],
                ],
            ],
            Setting::GROUP_PAYMENT => [
                'label' => 'Payment',
                'description' => 'Payment gateway and online payment workflow defaults.',
                'settings' => [
                    'default_gateway' => [
                        'label' => 'Default gateway',
                        'value_type' => Setting::TYPE_STRING,
                        'default' => 'cashfree',
                        'description' => 'Primary online payment provider name.',
                    ],
                    'gateway_mode' => [
                        'label' => 'Gateway mode',
                        'value_type' => Setting::TYPE_STRING,
                        'default' => 'sandbox',
                        'description' => 'Current payment environment mode.',
                    ],
                    'online_payments_enabled' => [
                        'label' => 'Online payments enabled',
                        'value_type' => Setting::TYPE_BOOLEAN,
                        'default' => true,
                        'description' => 'Whether website checkout may start an online payment flow.',
                    ],
                    'manual_payment_enabled' => [
                        'label' => 'Manual payment enabled',
                        'value_type' => Setting::TYPE_BOOLEAN,
                        'default' => true,
                        'description' => 'Whether staff may record manual payments.',
                    ],
                    'cod_enabled' => [
                        'label' => 'Cash on delivery enabled',
                        'value_type' => Setting::TYPE_BOOLEAN,
                        'default' => false,
                        'description' => 'Whether cash on delivery is available.',
                    ],
                    'auto_capture_enabled' => [
                        'label' => 'Auto capture enabled',
                        'value_type' => Setting::TYPE_BOOLEAN,
                        'default' => false,
                        'description' => 'Whether successful gateway payments should be captured automatically.',
                    ],
                ],
            ],
            Setting::GROUP_NOTIFICATION => [
                'label' => 'Notification',
                'description' => 'Messaging and channel defaults for order and business events.',
                'settings' => [
                    'email_enabled' => [
                        'label' => 'Email enabled',
                        'value_type' => Setting::TYPE_BOOLEAN,
                        'default' => true,
                        'description' => 'Whether email notifications are allowed.',
                    ],
                    'order_emails_enabled' => [
                        'label' => 'Order emails enabled',
                        'value_type' => Setting::TYPE_BOOLEAN,
                        'default' => true,
                        'description' => 'Whether order lifecycle emails are sent.',
                    ],
                    'payment_emails_enabled' => [
                        'label' => 'Payment emails enabled',
                        'value_type' => Setting::TYPE_BOOLEAN,
                        'default' => true,
                        'description' => 'Whether payment status emails are sent.',
                    ],
                    'admin_alerts_enabled' => [
                        'label' => 'Admin alerts enabled',
                        'value_type' => Setting::TYPE_BOOLEAN,
                        'default' => true,
                        'description' => 'Whether admin alert notifications are enabled.',
                    ],
                    'from_name' => [
                        'label' => 'From name',
                        'value_type' => Setting::TYPE_STRING,
                        'default' => config('app.name', 'Okina Craft'),
                        'description' => 'Default sender name used in outbound messages.',
                    ],
                    'reply_to_email' => [
                        'label' => 'Reply-to email',
                        'value_type' => Setting::TYPE_STRING,
                        'default' => null,
                        'description' => 'Reply-to address for outbound messages.',
                    ],
                ],
            ],
            Setting::GROUP_SEO => [
                'label' => 'SEO',
                'description' => 'Public site metadata defaults.',
                'settings' => [
                    'site_title' => [
                        'label' => 'Site title',
                        'value_type' => Setting::TYPE_STRING,
                        'default' => config('app.name', 'Okina Craft'),
                        'description' => 'Default page title base.',
                    ],
                    'meta_description' => [
                        'label' => 'Meta description',
                        'value_type' => Setting::TYPE_STRING,
                        'default' => null,
                        'description' => 'Default site description for search engines.',
                    ],
                    'robots_index' => [
                        'label' => 'Robots index',
                        'value_type' => Setting::TYPE_BOOLEAN,
                        'default' => true,
                        'description' => 'Whether public pages should be indexable.',
                    ],
                    'robots_follow' => [
                        'label' => 'Robots follow',
                        'value_type' => Setting::TYPE_BOOLEAN,
                        'default' => true,
                        'description' => 'Whether public links should be followed by crawlers.',
                    ],
                    'og_image_path' => [
                        'label' => 'Open Graph image',
                        'value_type' => Setting::TYPE_STRING,
                        'default' => null,
                        'description' => 'Default image path used for social sharing.',
                    ],
                ],
            ],
            Setting::GROUP_UPLOAD => [
                'label' => 'Upload',
                'description' => 'Private file storage and upload validation defaults.',
                'settings' => [
                    'private_disk' => [
                        'label' => 'Private disk',
                        'value_type' => Setting::TYPE_STRING,
                        'default' => 'private',
                        'description' => 'Filesystem disk used for private uploads.',
                    ],
                    'max_file_size_mb' => [
                        'label' => 'Maximum file size',
                        'value_type' => Setting::TYPE_INTEGER,
                        'default' => 5,
                        'description' => 'Maximum accepted upload size in megabytes.',
                    ],
                    'preview_enabled' => [
                        'label' => 'Preview enabled',
                        'value_type' => Setting::TYPE_BOOLEAN,
                        'default' => true,
                        'description' => 'Whether image previews should be generated.',
                    ],
                    'allow_customer_uploads' => [
                        'label' => 'Customer uploads allowed',
                        'value_type' => Setting::TYPE_BOOLEAN,
                        'default' => true,
                        'description' => 'Whether customers may upload files.',
                    ],
                    'allow_pdf_uploads' => [
                        'label' => 'PDF uploads allowed',
                        'value_type' => Setting::TYPE_BOOLEAN,
                        'default' => true,
                        'description' => 'Whether PDF uploads are allowed.',
                    ],
                ],
            ],
            Setting::GROUP_INTEGRATION => [
                'label' => 'Integration',
                'description' => 'Third-party connection toggles and safe operational flags.',
                'settings' => [
                    'google_sheets_enabled' => [
                        'label' => 'Google Sheets enabled',
                        'value_type' => Setting::TYPE_BOOLEAN,
                        'default' => false,
                        'description' => 'Whether Sheets sync jobs should run.',
                    ],
                    'google_analytics_enabled' => [
                        'label' => 'Google Analytics enabled',
                        'value_type' => Setting::TYPE_BOOLEAN,
                        'default' => false,
                        'description' => 'Whether GA4 tracking is enabled.',
                    ],
                    'meta_pixel_enabled' => [
                        'label' => 'Meta Pixel enabled',
                        'value_type' => Setting::TYPE_BOOLEAN,
                        'default' => false,
                        'description' => 'Whether Meta Pixel tracking is enabled.',
                    ],
                    'slack_notifications_enabled' => [
                        'label' => 'Slack notifications enabled',
                        'value_type' => Setting::TYPE_BOOLEAN,
                        'default' => false,
                        'description' => 'Whether Slack notifications are enabled.',
                    ],
                    'webhook_events_enabled' => [
                        'label' => 'Webhook events enabled',
                        'value_type' => Setting::TYPE_BOOLEAN,
                        'default' => true,
                        'description' => 'Whether integration webhook events should be dispatched.',
                    ],
                ],
            ],
        ];
    }
}
