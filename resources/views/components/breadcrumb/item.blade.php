@props([
    'href'   => null,
    'active' => false
])

@php $isActive = $active || !$href; @endphp

<li class="bc-item flex items-center gap-2 {{ $isActive ? 'bc-item-active' : 'bc-item-inactive' }}">

    @if($isActive)
        <span
            aria-current="page"
            {{ $attributes->merge(['class' => 'flex items-center gap-1.5 text-sm font-semibold text-[color:var(--color-text-primary)] min-w-0']) }}
            title="{{ trim(strip_tags((string) $slot)) }}"
        >
            @if(isset($icon))
                <span class="flex-shrink-0 text-[color:var(--color-text-muted)]">{{ $icon }}</span>
            @endif
            <span class="bc-label">{{ $slot }}</span>
        </span>
    @else
        <a
            href="{{ $href }}"
            {{ $attributes->merge(['class' => 'flex items-center gap-1.5 text-sm font-medium text-[color:var(--color-text-muted)] hover:text-[color:var(--color-primary-600)] transition-colors rounded focus:outline-none focus-visible:ring-2 focus-visible:ring-[color:var(--color-primary-500)] min-w-0']) }}
            title="{{ trim(strip_tags((string) $slot)) }}"
        >
            @if(isset($icon))
                <span class="flex-shrink-0">{{ $icon }}</span>
            @endif
            <span class="bc-label">{{ $slot }}</span>
        </a>
    @endif

    <span class="bc-sep flex-shrink-0 flex items-center text-[color:var(--color-text-muted)]" aria-hidden="true">
        <!-- BREADCRUMB_SEPARATOR -->
    </span>
</li>
