@props([
    'align' => 'left',
    'wrap' => false
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
    'align-middle',
    $wrap ? 'whitespace-normal' : 'whitespace-nowrap',
    $alignmentClasses
]) }}>
    {{ $slot }}
</td>
