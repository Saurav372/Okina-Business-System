@props([
    'intent' => 'neutral', // neutral, primary, blue, indigo, purple, info, success, danger, warning
    'appearance' => 'light', // solid, light, outline
    'size' => 'sm', // sm, md
    'dot' => false,
    'rounded' => null,
])

@php
    $intentClasses = [
        'neutral' => [
            'solid'   => 'bg-neutral-700 text-white border-transparent',
            'light'   => 'bg-neutral-100 text-neutral-700 border-neutral-200',
            'outline' => 'bg-transparent border border-neutral-300 text-neutral-600',
        ],
        'primary' => [
            'solid'   => 'bg-[color:var(--color-brand-600)] text-white border-transparent',
            'light'   => 'bg-[color:var(--color-brand-50)] text-[color:var(--color-brand-700)] border-[color:var(--color-brand-200)]',
            'outline' => 'bg-transparent border border-[color:var(--color-brand-300)] text-[color:var(--color-brand-700)]',
        ],
        'blue' => [
            'solid'   => 'bg-blue-600 text-white border-transparent',
            'light'   => 'bg-blue-50 text-blue-700 border-blue-200',
            'outline' => 'bg-transparent border border-blue-300 text-blue-700',
        ],
        'indigo' => [
            'solid'   => 'bg-indigo-600 text-white border-transparent',
            'light'   => 'bg-indigo-50 text-indigo-700 border-indigo-200',
            'outline' => 'bg-transparent border border-indigo-300 text-indigo-700',
        ],
        'purple' => [
            'solid'   => 'bg-purple-600 text-white border-transparent',
            'light'   => 'bg-purple-50 text-purple-700 border-purple-200',
            'outline' => 'bg-transparent border border-purple-200 text-purple-700',
        ],
        'info' => [
            'solid'   => 'bg-sky-600 text-white border-transparent',
            'light'   => 'bg-sky-50 text-sky-700 border-sky-200',
            'outline' => 'bg-transparent border border-sky-300 text-sky-700',
        ],
        'success' => [
            'solid'   => 'bg-emerald-600 text-white border-transparent',
            'light'   => 'bg-emerald-50 text-emerald-700 border-emerald-200',
            'outline' => 'bg-transparent border border-emerald-300 text-emerald-700',
        ],
        'danger' => [
            'solid'   => 'bg-red-600 text-white border-transparent',
            'light'   => 'bg-red-50 text-red-700 border-red-200',
            'outline' => 'bg-transparent border border-red-300 text-red-700',
        ],
        'warning' => [
            'solid'   => 'bg-amber-600 text-white border-transparent',
            'light'   => 'bg-amber-50 text-amber-700 border-amber-200',
            'outline' => 'bg-transparent border border-amber-300 text-amber-700',
        ],
    ];

    $sizes = [
        'xs' => 'px-2 py-0.5 text-[10px] gap-1',
        'sm' => 'px-2.5 py-0.5 text-[11px] gap-1.5',
        'md' => 'px-3 py-1 text-xs gap-1.5',
    ];

    $radii = [
        'none' => 'rounded-none',
        'sm'   => 'rounded-[var(--radius-sm)]',
        'md'   => 'rounded-[var(--radius-md)]',
        'lg'   => 'rounded-[var(--radius-lg)]',
        'xl'   => 'rounded-[var(--radius-xl)]',
        '2xl'  => 'rounded-[var(--radius-2xl)]',
        'full' => 'rounded-full',
    ];

    // Resolve classes
    $intentStyle = $intentClasses[$intent][$appearance] ?? ($intentClasses['neutral']['light']);
    $sizeClass = $sizes[$size] ?? $sizes['sm'];
    $roundedClass = isset($radii[$rounded]) ? $radii[$rounded] : 'rounded-full';
@endphp

<span 
    data-badge
    data-intent="{{ $intent }}"
    data-appearance="{{ $appearance }}"
    {{ $attributes->except(['intent', 'appearance', 'size', 'dot', 'rounded'])->class([
        'inline-flex items-center justify-center font-semibold tracking-normal border select-none whitespace-nowrap',
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
