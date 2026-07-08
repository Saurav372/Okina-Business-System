@props([
    'text' => 'Loading...',
    'size' => 'sm',
    'intent' => 'primary',
])

<div {{ $attributes->merge(['class' => 'inline-flex items-center gap-2 text-sm font-medium text-[color:var(--color-text-secondary)]']) }}>
    <x-spinner :size="$size" :intent="$intent" />
    <span>{{ $slot->isEmpty() ? $text : $slot }}</span>
</div>
