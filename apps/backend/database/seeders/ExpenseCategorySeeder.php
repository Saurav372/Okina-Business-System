<?php

namespace Database\Seeders;

use App\Models\ExpenseCategory;
use Illuminate\Database\Seeder;

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

        foreach ($categories as $category) {
            ExpenseCategory::query()->updateOrCreate(
                ['code' => $category['code']],
                [
                    'name' => $category['name'],
                    'description' => $category['description'],
                    'is_active' => true,
                ]
            );
        }
    }
}
