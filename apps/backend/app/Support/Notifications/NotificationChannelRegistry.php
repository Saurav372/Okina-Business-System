<?php

namespace App\Support\Notifications;

use App\Support\Notifications\Channels\DatabaseChannel;
use App\Support\Notifications\Channels\EmailChannel;
use App\Support\Notifications\Channels\NotificationChannel;
use App\Support\Notifications\Channels\SmsChannel;
use App\Support\Notifications\Channels\WhatsappChannel;
use Illuminate\Contracts\Container\Container;
use InvalidArgumentException;

class NotificationChannelRegistry
{
    /**
     * Default mapping of channels to concrete driver classes.
     *
     * @var array<string, string>
     */
    protected array $channelMap = [
        'email' => EmailChannel::class,
        'sms' => SmsChannel::class,
        'whatsapp' => WhatsappChannel::class,
        'database' => DatabaseChannel::class,
    ];

    /**
     * Create registry instance.
     */
    public function __construct(protected Container $container) {}

    /**
     * Resolve the concrete channel driver class.
     *
     *
     * @throws InvalidArgumentException
     */
    public function driver(string $channel): NotificationChannel
    {
        // Resolve class using custom config first, falling back to default mapping
        $class = config("notifications.drivers.{$channel}") ?? ($this->channelMap[$channel] ?? null);

        if (! $class) {
            throw new InvalidArgumentException("Unsupported notification channel driver: {$channel}");
        }

        return $this->container->make($class);
    }
}
