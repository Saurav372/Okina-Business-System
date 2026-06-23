<?php

namespace Database\Factories;

use App\Models\Customer;
use App\Models\Lead;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Lead>
 */
class LeadFactory extends Factory
{
    private const SOURCES = [
        'website_bulk_enquiry',
        'manual',
        'phone',
        'whatsapp',
        'email',
        'referral',
        'import',
    ];

    private const STATUSES = [
        'new',
        'assigned',
        'contacted',
        'qualified',
        'quoted',
        'won',
        'lost',
        'spam',
    ];

    private const PRIORITIES = [
        'low',
        'normal',
        'high',
        'urgent',
    ];

    public function definition(): array
    {
        return [
            'source' => $this->faker->randomElement(self::SOURCES),
            'source_detail' => $this->faker->optional(0.4)->sentence(3),
            'status' => 'new',
            'priority' => 'normal',
            'contact_name' => $this->faker->name(),
            'company_name' => $this->faker->optional(0.5)->company(),
            'email' => $this->faker->optional(0.8)->safeEmail(),
            'phone' => $this->faker->optional(0.7)->numerify('##########'),
            'city' => $this->faker->optional(0.6)->city(),
            'state' => $this->faker->optional(0.6)->state(),
            'country_code' => 'IN',
            'interest_summary' => $this->faker->optional(0.5)->sentence(8),
            'requirements' => $this->faker->optional(0.4)->paragraph(),
            'product_interest' => null,
        ];
    }

    /**
     * Lead captured from the website bulk enquiry form.
     */
    public function websiteBulkEnquiry(): static
    {
        return $this->state([
            'source' => 'website_bulk_enquiry',
            'utm_source' => $this->faker->randomElement(['google', 'facebook', 'instagram', null]),
            'utm_medium' => $this->faker->optional(0.6)->randomElement(['cpc', 'organic', 'social']),
            'utm_campaign' => $this->faker->optional(0.5)->slug(3),
            'referrer_url' => $this->faker->optional(0.5)->url(),
            'landing_page_url' => $this->faker->optional(0.7)->url(),
            'product_interest' => [
                ['product_name' => $this->faker->words(2, true), 'quantity' => $this->faker->numberBetween(25, 500)],
            ],
        ]);
    }

    /**
     * Lead created manually by staff.
     */
    public function manual(): static
    {
        return $this->state([
            'source' => 'manual',
        ]);
    }

    /**
     * Assign the lead to a specific user.
     */
    public function assignedTo(User $user): static
    {
        return $this->state([
            'assigned_to_user_id' => $user->id,
            'status' => 'assigned',
        ]);
    }

    /**
     * Lead linked to an existing customer (qualified state).
     */
    public function qualified(?Customer $customer = null): static
    {
        $customer ??= Customer::factory()->create();

        return $this->state([
            'customer_id' => $customer->id,
            'status' => 'qualified',
            'qualified_at' => now()->subDays(fake()->numberBetween(1, 14)),
        ]);
    }

    /**
     * Lead marked as won (converted).
     */
    public function won(?Customer $customer = null): static
    {
        $customer ??= Customer::factory()->create();

        return $this->state([
            'customer_id' => $customer->id,
            'status' => 'won',
            'qualified_at' => now()->subDays(fake()->numberBetween(10, 30)),
            'converted_at' => now()->subDays(fake()->numberBetween(1, 9)),
        ]);
    }

    /**
     * Lead marked as lost.
     */
    public function lost(): static
    {
        return $this->state([
            'status' => 'lost',
            'lost_at' => now()->subDays(fake()->numberBetween(1, 14)),
            'lost_reason' => $this->faker->randomElement([
                'Budget too low',
                'Went with competitor',
                'Timeline mismatch',
                'No response',
                'Requirements changed',
            ]),
        ]);
    }

    /**
     * Lead flagged as spam.
     */
    public function spam(): static
    {
        return $this->state([
            'status' => 'spam',
            'contact_name' => null,
            'email' => null,
        ]);
    }

    /**
     * Lead with high priority.
     */
    public function highPriority(): static
    {
        return $this->state(['priority' => 'high']);
    }

    /**
     * Lead with urgent priority.
     */
    public function urgent(): static
    {
        return $this->state(['priority' => 'urgent']);
    }

    /**
     * Lead created by a specific user.
     */
    public function createdBy(User $user): static
    {
        return $this->state(['created_by_user_id' => $user->id]);
    }
}
