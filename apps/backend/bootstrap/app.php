<?php

use App\Http\Middleware\EnsureCustomerAccountAccess;
use App\Http\Middleware\EnsureDashboardAccess;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        api: __DIR__.'/../routes/api.php',
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'customer.access' => EnsureCustomerAccountAccess::class,
            'dashboard.access' => EnsureDashboardAccess::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*')
                || $request->is('admin/leads*')
                || $request->is('admin/orders*')
                || $request->is('admin/products/*/media/reorder')
                || $request->is('admin/quotations*')
                || $request->is('admin/payments*')
                || $request->is('admin/refunds*')
                || $request->is('admin/expense-categories*')
                || $request->is('admin/expenses*')
                || $request->is('admin/vendors*')
                || $request->is('admin/purchase-orders*'),
        );
    })->create();
