<?php

namespace App\Providers;

use App\Events\AuditEvent;
use App\Listeners\AuditEventListener;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        AuditEvent::class => [
            AuditEventListener::class,
        ],
    ];
}
