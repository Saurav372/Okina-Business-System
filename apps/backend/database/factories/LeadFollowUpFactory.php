<?php

namespace Database\Factories;

use App\Enums\LeadFollowUpStatus;
use App\Models\Lead;
use App\Models\LeadFollowUp;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<LeadFollowUp>
 */
class LeadFollowUpFactory extends Factory
{
    public function definition(): array
    {
        return [
            'lead_id' => Lead::factory(),
            'assigned_to_user_id' => null,
            'status' => LeadFollowUpStatus::PENDING->value,
            'due_at' => now()->addDays(2),
            'completed_at' => null,
            'completed_by_user_id' => null,
            'snoozed_until' => null,
            'subject' => $this->faker->sentence(4),
            'notes' => $this->faker->paragraph(),
            'notification_key' => null,
            'created_by_user_id' => null,
        ];
    }

    public function pending(): static
    {
        return $this->state([
            'status' => LeadFollowUpStatus::PENDING->value,
        ]);
    }

    public function completed(?User $user = null): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => LeadFollowUpStatus::COMPLETED->value,
            'completed_at' => now(),
            'completed_by_user_id' => $user ? $user->id : User::factory(),
        ]);
    }

    public function snoozed($until = null): static
    {
        return $this->state([
            'status' => LeadFollowUpStatus::SNOOZED->value,
            'snoozed_until' => $until ?? now()->addDays(3),
        ]);
    }

    public function cancelled(): static
    {
        return $this->state([
            'status' => LeadFollowUpStatus::CANCELLED->value,
        ]);
    }

    public function overdue(): static
    {
        return $this->state([
            'status' => LeadFollowUpStatus::PENDING->value,
            'due_at' => now()->subDays(2),
        ]);
    }

    public function withNotificationKey(?string $key = null): static
    {
        return $this->state([
            'notification_key' => $key ?? 'notif_'.Str::random(10),
        ]);
    }
}
