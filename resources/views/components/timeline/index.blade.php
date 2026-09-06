@props([
    'as' => 'ol',
])

<{{ $as }} {{ $attributes->class(['relative space-y-[var(--spacing-6)] w-full block']) }}>
    {{ $slot }}
</{{ $as }}>
