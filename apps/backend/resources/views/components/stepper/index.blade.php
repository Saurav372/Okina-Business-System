@props([
    'active' => 1,
    'total' => null,
    'orientation' => 'horizontal',
    'size' => 'md',
    'showNumbers' => true
])

@php
    $sizeMap = [
        'sm' => [
            'circle' => '1.5rem',
            'font' => '0.75rem',
            'line' => '2px',
        ],
        'md' => [
            'circle' => '2.25rem',
            'font' => '0.875rem',
            'line' => '2px',
        ],
        'lg' => [
            'circle' => '3rem',
            'font' => '1rem',
            'line' => '3px',
        ],
    ];

    $sizeValues = $sizeMap[$size] ?? $sizeMap['md'];
    $isVertical = $orientation === 'vertical';
    $listClasses = $isVertical 
        ? 'flex flex-col w-full gap-6' 
        : 'flex flex-row overflow-x-auto scrollbar-none w-full gap-6 items-start py-2';
@endphp

<style>
    .ui-stepper-list::-webkit-scrollbar { display: none; }
    .ui-stepper-list > li:last-child .ui-step-connector { display: none !important; }
</style>

<nav 
    aria-label="Progress" 
    {{ $attributes->merge(['class' => 'w-full']) }}
    style="
        --step-circle-size: {{ $sizeValues['circle'] }};
        --step-font-size: {{ $sizeValues['font'] }};
        --step-line-size: {{ $sizeValues['line'] }};
        --step-color-active: var(--color-primary-600, #219ae8);
        --step-color-error: var(--color-danger-600, #dc2626);
        --step-color-muted: var(--color-neutral-300, #d1d5db);
    "
>
    <ol class="ui-stepper-list {{ $listClasses }}">
        {{ $slot }}
    </ol>
</nav>
