@props([
    'intent' => 'neutral', // neutral, primary, success, danger, warning, info
    'appearance' => 'light', // solid, light, outline
    'size' => 'md', // sm, md
    'dot' => false,
    'rounded' => null,
])

@php
    $intentClasses = [
        'neutral' => [
            'solid' => 'bg-[color:var(--color-neutral-600)] text-white border-transparent',
            'light' => 'bg-[color:var(--color-neutral-100)] text-[color:var(--color-text-primary)] border-[color:var(--color-border)] dark:bg-[color:var(--color-neutral-800)] dark:text-[color:var(--color-neutral-200)] dark:border-[color:var(--color-neutral-700)]',
            'outline' => 'bg-transparent border border-[color:var(--color-border)] text-[color:var(--color-text-secondary)] dark:text-[color:var(--color-neutral-300)] dark:border-[color:var(--color-neutral-700)]',
        ],
        'primary' => [
            'solid' => 'bg-[color:var(--color-primary)] text-white border-transparent',
            'light' => 'bg-[color:var(--color-primary-50)] text-[color:var(--color-primary-700)] border-[color:var(--color-primary-100)] dark:bg-[color:var(--color-primary-950)]/30 dark:text-[color:var(--color-primary-300)] dark:border-[color:var(--color-primary-800)]/30',
            'outline' => 'bg-transparent border border-[color:var(--color-primary)] text-[color:var(--color-primary)] dark:text-[color:var(--color-primary-400)] dark:border-[color:var(--color-primary-800)]',
        ],
        'success' => [
            'solid' => 'bg-[color:var(--color-success)] text-white border-transparent',
            'light' => 'bg-[color:var(--color-success-50)] text-[color:var(--color-success-700)] border-[color:var(--color-success-100)] dark:bg-[color:var(--color-success-950)]/30 dark:text-[color:var(--color-success-300)] dark:border-[color:var(--color-success-800)]/30',
            'outline' => 'bg-transparent border border-[color:var(--color-success)] text-[color:var(--color-success)] dark:text-[color:var(--color-success-400)] dark:border-[color:var(--color-success-800)]',
        ],
        'danger' => [
            'solid' => 'bg-[color:var(--color-danger)] text-white border-transparent',
            'light' => 'bg-[color:var(--color-danger-50)] text-[color:var(--color-danger-700)] border-[color:var(--color-danger-100)] dark:bg-[color:var(--color-danger-950)]/30 dark:text-[color:var(--color-danger-300)] dark:border-[color:var(--color-danger-800)]/30',
            'outline' => 'bg-transparent border border-[color:var(--color-danger)] text-[color:var(--color-danger)] dark:text-[color:var(--color-danger-400)] dark:border-[color:var(--color-danger-800)]',
        ],
        'warning' => [
            'solid' => 'bg-[color:var(--color-warning)] text-white border-transparent',
            'light' => 'bg-[color:var(--color-warning-50)] text-[color:var(--color-warning-700)] border-[color:var(--color-warning-100)] dark:bg-[color:var(--color-warning-950)]/30 dark:text-[color:var(--color-warning-300)] dark:border-[color:var(--color-warning-800)]/30',
            'outline' => 'bg-transparent border border-[color:var(--color-warning)] text-[color:var(--color-warning)] dark:text-[color:var(--color-warning-400)] dark:border-[color:var(--color-warning-800)]',
        ],
        'info' => [
            'solid' => 'bg-[color:var(--color-info)] text-white border-transparent',
            'light' => 'bg-[color:var(--color-primary-50)] text-[color:var(--color-primary-700)] border-[color:var(--color-primary-100)] dark:bg-[color:var(--color-primary-950)]/30 dark:text-[color:var(--color-primary-300)] dark:border-[color:var(--color-primary-800)]/30',
            'outline' => 'bg-transparent border border-[color:var(--color-info)] text-[color:var(--color-info)] dark:text-[color:var(--color-primary-400)] dark:border-[color:var(--color-primary-800)]',
        ],
    ];

    $sizes = [
        'sm' => 'h-5 px-2 text-[10px] gap-1',
        'md' => 'h-6 px-2.5 text-xs gap-1.5',
    ];

    $radii = [
        'none' => 'rounded-none',
        'sm' => 'rounded-[var(--radius-sm)]',
        'md' => 'rounded-[var(--radius-md)]',
        'lg' => 'rounded-[var(--radius-lg)]',
        'xl' => 'rounded-[var(--radius-xl)]',
        '2xl' => 'rounded-[var(--radius-2xl)]',
        'full' => 'rounded-full',
    ];

    // Resolve classes
    $intentStyle = $intentClasses[$intent][$appearance] ?? $intentClasses['neutral']['light'];
    $sizeClass = $sizes[$size] ?? $sizes['md'];
    $roundedClass = isset($radii[$rounded]) ? $radii[$rounded] : 'rounded-full'; // default is full
@endphp

<span 
    data-badge
    data-intent="{{ $intent }}"
    data-appearance="{{ $appearance }}"
    {{ $attributes->except(['intent', 'appearance', 'size', 'dot', 'rounded'])->class([
        'inline-flex items-center justify-center font-bold tracking-tight uppercase border select-none',
        $intentStyle,
        $sizeClass,
        $roundedClass
    ]) }}
>
    @if ($dot)
        <span class="w-1.5 h-1.5 rounded-full bg-current shrink-0" aria-hidden="true"></span>
    @elseif (isset($icon))
        <span class="inline-flex shrink-0 items-center justify-center pointer-events-none">
            {{ $icon }}
        </span>
    @endif

    {{ $slot }}
</span>
