<?php

namespace Database\Seeders;

use App\Models\ExpenseCategory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ExpenseCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            [
                'code' => 'shipping',
                'name' => 'Shipping & Logistics',
                'description' => 'Shipping, freight, delivery, and postage expenses',
            ],
            [
                'code' => 'raw_materials',
                'name' => 'Raw Materials & Manufacturing',
                'description' => 'Sourcing raw material goods, components, and direct manufacturing costs',
            ],
            [
                'code' => 'marketing',
                'name' => 'Marketing & Advertising',
                'description' => 'Online ads, offline ads, branding, and customer acquisition costs',
            ],
            [
                'code' => 'printing_supplies',
                'name' => 'Printing & Consumables',
                'description' => 'Inks, paper, packaging materials, and general printing supplies',
            ],
            [
                'code' => 'utilities',
                'name' => 'Utilities & Office Overhead',
                'description' => 'Electricity, internet, office rent, software subscriptions, and general operations',
            ],
            [
                'code' => 'other',
                'name' => 'Miscellaneous Expenses',
                'description' => 'Other business expenses not captured in predefined categories',
            ],
        ];

        foreach ($categories as $data) {
            // Use a raw query to avoid model event restrictions on code immutability.
            // On first run: insert via model (which generates public_id).
            // On subsequent runs: update only mutable fields via DB query.
            $existing = ExpenseCategory::withTrashed()->where('code', $data['code'])->first();

            if ($existing) {
                // Update only mutable fields (name, description, is_active)
                // Code is locked; skip model save() to avoid immutability guard.
                DB::table('expense_categories')
                    ->where('id', $existing->id)
                    ->update([
                        'name' => $data['name'],
                        'description' => $data['description'],
                        'is_active' => true,
                        'deleted_at' => null, // restore if soft-deleted
                        'updated_at' => now(),
                    ]);
            } else {
                ExpenseCategory::create([
                    'code' => $data['code'],
                    'name' => $data['name'],
                    'description' => $data['description'],
                    'is_active' => true,
                ]);
            }
        }
    }
}
