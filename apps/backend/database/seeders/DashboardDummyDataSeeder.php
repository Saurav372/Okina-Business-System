<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Order;
use App\Models\Quotation;
use App\Models\AuditLog;
use App\Enums\OrderStatus;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class DashboardDummyDataSeeder extends Seeder
{
    /**
     * Run the database seeds for quick dashboard testing.
     */
    public function run(): void
    {
        $user = User::where('email', 'test@example.com')->first() 
            ?? User::first() 
            ?? User::factory()->create(['name' => 'Saurav Nanda', 'email' => 'saurav@example.com']);

        // 1. Seed Orders for the last 6 calendar months
        for ($i = 5; $i >= 0; $i--) {
            $monthDate = Carbon::now()->subMonths($i);
            // Create 2-4 orders in each month
            $count = rand(2, 4);
            for ($j = 0; $j < $count; $j++) {
                Order::factory()->create([
                    'total_amount_minor' => rand(150000, 850000), // ₹1,500 to ₹8,500
                    'status' => OrderStatus::Confirmed->value(),
                    'placed_at' => $monthDate->copy()->startOfMonth()->addDays(rand(1, 27)),
                ]);
            }
        }

        // 2. Seed Quotations across pipeline statuses
        $statuses = [
            Quotation::STATUS_DRAFT,
            Quotation::STATUS_SENT,
            Quotation::STATUS_APPROVED,
            Quotation::STATUS_CONVERTED,
            Quotation::STATUS_EXPIRED,
        ];
        foreach ($statuses as $status) {
            $count = rand(3, 8);
            for ($j = 0; $j < $count; $j++) {
                Quotation::factory()->create([
                    'status' => $status,
                ]);
            }
        }

        // 3. Seed Audit logs for recent activity timeline
        $logs = [
            [
                'action' => 'orders.order_created',
                'summary' => 'Order OD-7832 created for custom crafting order',
                'occurred_at' => Carbon::now()->subMinutes(15),
                'actor_label_snapshot' => 'Saurav Nanda',
            ],
            [
                'action' => 'payments.payment_recorded',
                'summary' => 'Payment of ₹12,500.00 recorded for Invoice #209',
                'occurred_at' => Carbon::now()->subHours(2),
                'actor_label_snapshot' => 'Amit Sharma',
            ],
            [
                'action' => 'leads.created',
                'summary' => 'New CRM Lead "Inder Singh" added from Website portal',
                'occurred_at' => Carbon::now()->subHours(5),
                'actor_label_snapshot' => 'Rajesh Kumar',
            ],
            [
                'action' => 'orders.order_cancelled',
                'summary' => 'Order OD-5412 cancelled by customer request',
                'occurred_at' => Carbon::now()->subDays(1)->subHours(3),
                'actor_label_snapshot' => 'Saurav Nanda',
            ],
            [
                'action' => 'refunds.refund_approved',
                'summary' => 'Refund of ₹4,200.00 approved for Order OD-8910',
                'occurred_at' => Carbon::now()->subDays(3),
                'actor_label_snapshot' => 'Amit Sharma',
            ],
        ];

        foreach ($logs as $log) {
            AuditLog::create([
                'event_id' => (string) \Illuminate\Support\Str::uuid(),
                'action' => $log['action'],
                'module' => explode('.', $log['action'])[0],
                'summary' => $log['summary'],
                'occurred_at' => $log['occurred_at'],
                'actor_type' => \App\Enums\AuditActorType::USER,
                'subject_type' => 'unknown',
                'actor_label_snapshot' => $log['actor_label_snapshot'],
                'actor_user_id' => $user->id,
            ]);
        }
    }
}
