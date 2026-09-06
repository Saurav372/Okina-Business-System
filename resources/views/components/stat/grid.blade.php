@props([
    'as' => 'div',
])

<{{ $as }} {{ $attributes->class(['@container w-full']) }}>
    <div class="grid grid-cols-1 @sm:grid-cols-2 @lg:grid-cols-3 @xl:grid-cols-4 gap-[var(--spacing-6)]">
        {{ $slot }}
    </div>
</{{ $as }}>
