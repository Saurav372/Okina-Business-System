<?php

use App\Support\Breadcrumbs\BreadcrumbBuilder;
use App\Support\Breadcrumbs\BreadcrumbDefinition;
use Illuminate\Contracts\Console\Kernel;

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

$def = new BreadcrumbDefinition;
$builder = new BreadcrumbBuilder($def);

echo "5. Dynamic (admin.sales_orders.show with {order.public_id})\n";
$order = new class
{
    public $public_id = 'ORD-2026-999';
};
// Since admin.sales_orders.show doesn't exist in web.php, the route() function might fail.
// So we mock the Definition to use a route that exists (admin.expenses.show doesn't exist either).
// Let's use `admin.audit_logs.show` which exists, but it uses `audit_log` as parameter.

$reflection = new ReflectionProperty($builder, 'definition');
$reflection->setAccessible(true);
$mockDef = new class extends BreadcrumbDefinition
{
    public function items(): array
    {
        return [
            'admin.dashboard' => ['parent' => null, 'label' => 'Dashboard'],
            'admin.audit_logs.index' => ['parent' => 'admin.dashboard', 'label' => 'Audit Logs'],
            'admin.audit_logs.show' => ['parent' => 'admin.audit_logs.index', 'label' => '{audit_log.id}', 'fallback' => 'Log'],
        ];
    }
};
$reflection->setValue($builder, $mockDef);

$log = new class
{
    public $id = 'LOG-123';
};
dump($builder->build('admin.audit_logs.show', ['audit_log' => $log]));

echo "\n6. Fallback (missing property, uses fallback 'Log')\n";
$log2 = new class {};
dump($builder->build('admin.audit_logs.show', ['audit_log' => $log2]));
