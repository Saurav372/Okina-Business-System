<?php

namespace App\Support\Notifications;

readonly class NotificationEventDefinition
{
    /**
     * @param  array<int, string>  $recipients
     * @param  array<int, string>  $channels
     * @param  array<string, array<int, string>>  $channelSettings
     * @param  array<string, mixed>  $retry
     * @param  array<string, mixed>  $deduplication
     * @param  array<string, mixed>  $template
     * @param  array<int, string>  $references
     */
    public function __construct(
        public string $key,
        public string $name,
        public string $trigger,
        public array $recipients,
        public array $channels,
        public array $channelSettings,
        public array $retry,
        public array $deduplication,
        public array $template,
        public array $references = [],
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'key' => $this->key,
            'name' => $this->name,
            'trigger' => $this->trigger,
            'recipients' => $this->recipients,
            'channels' => $this->channels,
            'channel_settings' => $this->channelSettings,
            'retry' => $this->retry,
            'deduplication' => $this->deduplication,
            'template' => $this->template,
            'references' => $this->references,
        ];
    }
}
