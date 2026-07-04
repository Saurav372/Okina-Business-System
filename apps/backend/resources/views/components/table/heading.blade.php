@props([
    'sortable' => false,
    'direction' => null,
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

<th scope="col" {{ $attributes->class([
    'px-[var(--spacing-4)] py-[var(--spacing-3)]',
    'font-semibold uppercase tracking-wider',
    $wrap ? 'whitespace-normal' : 'whitespace-nowrap',
    $alignmentClasses
]) }}>
    @if($sortable)
        <button type="button" class="group inline-flex items-center gap-1 uppercase tracking-wider {{ $align === 'right' ? 'justify-end' : ($align === 'center' ? 'justify-center' : 'justify-start') }} focus:outline-none focus-visible:ring-[length:var(--focus-ring-width)] focus-visible:ring-[color:var(--focus-ring-color)] rounded-[var(--radius-sm)]">
            <span>{{ $slot }}</span>
            
            <span class="ml-1 flex-none flex items-center">
                @if ($direction === 'asc')
                    <x-icons.chevron-up class="w-4 h-4 text-[color:var(--color-primary)]" />
                @elseif ($direction === 'desc')
                    <x-icons.chevron-down class="w-4 h-4 text-[color:var(--color-primary)]" />
                @else
                    <!-- Inactive sort indicator (appears on hover) -->
                    <x-icons.chevron-down class="w-4 h-4 opacity-0 group-hover:opacity-50 transition-opacity duration-[var(--motion-fast)]" />
                @endif
            </span>
        </button>
    @else
        {{ $slot }}
    @endif
</th>
