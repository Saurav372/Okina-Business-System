@props([
    'title',
    'description' => null,
    'size' => 'md',
    'variant' => 'default', // Prepared for future variants (e.g. search, warning, success)
])

@php
    // Sizing maps
    $sizes = [
        'sm' => [
            'wrapper' => 'py-6 px-4 space-y-3',
            'iconBox' => 'w-10 h-10',
            'icon' => 'w-5 h-5',
            'title' => 'text-sm',
            'description' => 'text-[13px]',
            'actionBox' => 'pt-2'
        ],
        'md' => [
            'wrapper' => 'py-10 px-6 space-y-4',
            'iconBox' => 'w-14 h-14',
            'icon' => 'w-6 h-6',
            'title' => 'text-base',
            'description' => 'text-sm',
            'actionBox' => 'pt-3'
        ],
        'lg' => [
            'wrapper' => 'py-16 px-8 space-y-5',
            'iconBox' => 'w-20 h-20',
            'icon' => 'w-10 h-10',
            'title' => 'text-xl',
            'description' => 'text-base',
            'actionBox' => 'pt-4'
        ],
    ];

    $currentSize = $sizes[$size] ?? $sizes['md'];

    // Future-proofing variants. Currently, 'default' uses neutral colors.
    $variants = [
        'default' => [
            'iconBox' => 'bg-[color:var(--color-surface-secondary)] border border-[color:var(--color-border)] text-[color:var(--color-text-muted)]',
            'title' => 'text-[color:var(--color-text-primary)]',
            'description' => 'text-[color:var(--color-text-muted)]',
        ],
    ];

    $currentVariant = $variants[$variant] ?? $variants['default'];
@endphp

<div {{ $attributes->class([
    'flex flex-col items-center justify-center text-center w-full',
    $currentSize['wrapper']
]) }}>
    
    <div class="flex items-center justify-center rounded-full shrink-0 {{ $currentSize['iconBox'] }} {{ $currentVariant['iconBox'] }}" aria-hidden="true" focusable="false">
        @if (isset($icon))
            <div class="flex items-center justify-center w-full h-full [&>svg]:{{ $currentSize['icon'] }} [&>svg]:text-current [&>svg]:stroke-current [&>svg]:fill-none">
                {{ $icon }}
            </div>
        @else
            <x-icons.inbox class="{{ $currentSize['icon'] }} text-current" aria-hidden="true" focusable="false" />
        @endif
    </div>
    
    <div class="flex flex-col items-center space-y-1.5 max-w-sm mx-auto">
        <h3 class="font-bold tracking-tight leading-none {{ $currentSize['title'] }} {{ $currentVariant['title'] }}">
            {{ $title }}
        </h3>
        
        @if ($description)
            <p class="font-medium leading-relaxed {{ $currentSize['description'] }} {{ $currentVariant['description'] }}">
                {{ $description }}
            </p>
        @endif
    </div>
    
    @if (isset($actions))
        <div class="flex items-center justify-center gap-3 flex-wrap w-full {{ $currentSize['actionBox'] }}">
            {{ $actions }}
        </div>
    @endif

</div>
