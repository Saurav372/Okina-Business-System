<?php

use App\Http\Controllers\Api\CartController;
use App\Http\Controllers\Api\CustomerApiController;
use App\Http\Controllers\Api\PaymentWebhookController;
use App\Http\Controllers\Api\ProductCustomizationController;
use App\Http\Controllers\Api\PublicCatalogController;
use App\Http\Controllers\Api\PublicLeadController;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Support\Facades\Route;

Route::get('/health', function () {
    return ['status' => 'ok'];
});

Route::prefix('catalog')->group(function () {
    Route::get('/categories', [PublicCatalogController::class, 'categories']);
    Route::get('/categories/{category:slug}/products', [PublicCatalogController::class, 'categoryProducts']);
    Route::get('/products', [PublicCatalogController::class, 'products']);
    Route::get('/products/{product:slug}', [PublicCatalogController::class, 'product']);
    Route::get('/products/{product:slug}/customization-options', [ProductCustomizationController::class, 'show']);
    Route::post('/products/{product:slug}/design-upload', [ProductCustomizationController::class, 'store'])
        ->middleware('auth:customer');
    Route::get('/products/{product:slug}/design-preview/{preview_file}', [ProductCustomizationController::class, 'preview'])
        ->middleware('signed')
        ->name('catalog.products.mockup-preview');
    Route::post('/products/{product:slug}/design-preview/{preview_file}/link', [ProductCustomizationController::class, 'previewLink'])
        ->middleware('auth:customer')
        ->name('catalog.products.mockup-preview-link');
    Route::post('/leads', [PublicLeadController::class, 'store'])->name('api.catalog.leads.store');
});

Route::middleware('web')->withoutMiddleware(ValidateCsrfToken::class)->prefix('cart')->group(function () {
    Route::get('/', [CartController::class, 'index']);
    Route::get('/validation', [CartController::class, 'validation']);
    Route::post('/checkout/validation', [CartController::class, 'checkoutValidation'])->middleware('auth:customer');
    Route::post('/checkout', [CartController::class, 'checkout'])->middleware('auth:customer');
    Route::post('/items', [CartController::class, 'store']);
    Route::patch('/items/{cartItem}', [CartController::class, 'update']);
    Route::delete('/items/{cartItem}', [CartController::class, 'destroy']);
});

Route::middleware(['web', 'customer.access'])->withoutMiddleware(ValidateCsrfToken::class)->prefix('customer')->group(function () {
    Route::get('/session', [CustomerApiController::class, 'session']);
    Route::post('/logout', [CustomerApiController::class, 'logout']);
    Route::get('/profile', [CustomerApiController::class, 'profile']);
    Route::get('/addresses', [CustomerApiController::class, 'addresses']);
    Route::post('/addresses', [CustomerApiController::class, 'storeAddress']);
    Route::put('/addresses/{address}', [CustomerApiController::class, 'updateAddress']);
    Route::delete('/addresses/{address}', [CustomerApiController::class, 'destroyAddress']);
    Route::post('/addresses/{address}/default', [CustomerApiController::class, 'setDefaultAddress']);
    Route::get('/orders', [CustomerApiController::class, 'orders']);
    Route::get('/orders/{order}', [CustomerApiController::class, 'orderDetail']);
    Route::post('/orders/{order}/reorder', [CustomerApiController::class, 'reorder']);
});

Route::prefix('webhooks')->group(function () {
    Route::post('/payments/cashfree', [PaymentWebhookController::class, 'cashfree']);
});
