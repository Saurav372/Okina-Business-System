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
                'code' => 'SHIPPING_LOGISTICS',
                'name' => 'Shipping & Logistics',
                'description' => 'Shipping, freight, delivery, and postage expenses',
            ],
            [
                'code' => 'RAW_MATERIALS',
                'name' => 'Raw Materials & Manufacturing',
                'description' => 'Sourcing raw material goods, components, and direct manufacturing costs',
            ],
            [
                'code' => 'MARKETING_ADVERTISING',
                'name' => 'Marketing & Advertising',
                'description' => 'Online ads, offline ads, branding, and customer acquisition costs',
            ],
            [
                'code' => 'PRINTING_CONSUMABLES',
                'name' => 'Printing & Consumables',
                'description' => 'Inks, paper, packaging materials, and general printing supplies',
            ],
            [
                'code' => 'UTILITIES_OVERHEAD',
                'name' => 'Utilities & Office Overhead',
                'description' => 'Electricity, internet, office rent, software subscriptions, and general operations',
            ],
            [
                'code' => 'MISCELLANEOUS',
                'name' => 'Miscellaneous Expenses',
                'description' => 'Other business expenses not captured in predefined categories',
            ],
        ];

        foreach ($categories as $category) {
            ExpenseCategory::query()->firstOrCreate(
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
