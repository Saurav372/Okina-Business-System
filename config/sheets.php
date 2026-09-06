<?php

use App\Models\Customer;
use App\Models\InventoryMovement;
use App\Models\Order;
use App\Models\Payment;
use App\Models\VendorOrder;

return [
    'enabled' => env('GOOGLE_SHEETS_ENABLED', false),
    'spreadsheet_id' => env('GOOGLE_SHEETS_SPREADSHEET_ID'),
    'credentials' => [
        'client_email' => env('GOOGLE_SHEETS_CLIENT_EMAIL'),
        'private_key' => env('GOOGLE_SHEETS_PRIVATE_KEY'),
        'project_id' => env('GOOGLE_SHEETS_PROJECT_ID'),
        'client_id' => env('GOOGLE_SHEETS_CLIENT_ID'),
    ],
    'logging' => [
        'store_payloads' => env('GOOGLE_SHEETS_LOG_STORE_PAYLOADS', true),
        'prune_days' => env('GOOGLE_SHEETS_LOG_PRUNE_DAYS', 30),
    ],
    'entities' => [
        Order::class => [
            'sheet' => 'Orders',
            'unique_key' => 'public_id',
            'columns' => [
                'public_id' => 'Order ID',
                'customer_name' => 'Customer Name',
                'status' => 'Status',
                'total_amount' => 'Total Amount',
                'courier_name' => 'Courier',
                'created_at' => 'Created At',
            ],
        ],
        Payment::class => [
            'sheet' => 'Payments',
            'unique_key' => 'id',
            'columns' => [
                'id' => 'Payment ID',
                'order_public_id' => 'Order ID',
                'provider' => 'Provider',
                'method' => 'Method',
                'amount' => 'Amount',
                'status' => 'Status',
                'created_at' => 'Created At',
            ],
        ],
        InventoryMovement::class => [
            'sheet' => 'Inventory Movements',
            'unique_key' => 'id',
            'columns' => [
                'id' => 'Movement ID',
                'sku_code' => 'SKU',
                'type' => 'Type',
                'reason' => 'Reason',
                'quantity' => 'Quantity',
                'balance_on_hand' => 'Balance On Hand',
                'created_at' => 'Created At',
            ],
        ],
        Customer::class => [
            'sheet' => 'Customers',
            'unique_key' => 'public_id',
            'columns' => [
                'public_id' => 'Customer ID',
                'display_name' => 'Name',
                'email' => 'Email',
                'phone' => 'Phone',
                'status' => 'Status',
                'created_at' => 'Created At',
            ],
        ],
        VendorOrder::class => [
            'sheet' => 'Vendor Orders',
            'unique_key' => 'id',
            'columns' => [
                'id' => 'Vendor Order ID',
                'vendor_code' => 'Vendor Code',
                'status' => 'Status',
                'payment_status' => 'Payment Status',
                'total_amount' => 'Total Amount',
                'created_at' => 'Created At',
            ],
        ],
    ],
];
