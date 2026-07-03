@props([
    'align' => 'left'
])

@php
    $alignmentClasses = match($align) {
        'center' => 'text-center',
        'right' => 'text-right',
        default => 'text-left',
    };
@endphp

<td {{ $attributes->class([
    'px-[var(--spacing-4)] py-[var(--spacing-3)]',
    'whitespace-nowrap align-middle',
    $alignmentClasses
]) }}>
    {{ $slot }}
</td>
