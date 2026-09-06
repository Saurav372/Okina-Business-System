@props([
    'intent' => 'primary', // primary, secondary, success, danger, warning, info
    'appearance' => 'solid', // solid, outline, ghost
    'size' => 'md', // sm, md, lg
    'shape' => 'default', // default, square, circle
    'type' => 'button', // button, submit, reset
    'loading' => false,
    'fullWidth' => false,
    'rounded' => null,
    'href' => null,
])

@php
    $intentClasses = [
        'primary' => [
            'solid' => 'bg-[color:var(--color-primary)] text-white hover:bg-[color:var(--color-primary-hover)] active:bg-[color:var(--color-primary-active)] border border-transparent',
            'outline' => 'bg-transparent border border-[color:var(--color-primary)] text-[color:var(--color-primary)] hover:bg-[color:var(--color-primary-50)] dark:hover:bg-[color:var(--color-primary-950)]/30',
            'ghost' => 'bg-transparent text-[color:var(--color-primary)] hover:bg-[color:var(--color-primary-50)] border border-transparent dark:hover:bg-[color:var(--color-primary-950)]/30',
        ],
        'secondary' => [
            'solid' => 'bg-[color:var(--color-neutral-100)] text-[color:var(--color-text-primary)] hover:bg-[color:var(--color-neutral-200)] border border-[color:var(--color-border)] dark:bg-[color:var(--color-neutral-800)] dark:hover:bg-[color:var(--color-neutral-700)] dark:text-white dark:border-[color:var(--color-neutral-700)]',
            'outline' => 'bg-transparent border border-[color:var(--color-border)] text-[color:var(--color-text-primary)] hover:bg-[color:var(--color-neutral-100)] dark:text-white dark:border-[color:var(--color-neutral-700)] dark:hover:bg-[color:var(--color-neutral-800)]',
            'ghost' => 'bg-transparent text-[color:var(--color-text-secondary)] hover:bg-[color:var(--color-neutral-100)] hover:text-[color:var(--color-text-primary)] border border-transparent dark:text-[color:var(--color-neutral-300)] dark:hover:bg-[color:var(--color-neutral-800)] dark:hover:text-white',
        ],
        'success' => [
            'solid' => 'bg-[color:var(--color-success)] text-white hover:bg-[color:var(--color-success-600)] active:bg-[color:var(--color-success-700)] border border-transparent',
            'outline' => 'bg-transparent border border-[color:var(--color-success)] text-[color:var(--color-success)] hover:bg-[color:var(--color-success-50)] dark:hover:bg-[color:var(--color-success-950)]/30',
            'ghost' => 'bg-transparent text-[color:var(--color-success)] hover:bg-[color:var(--color-success-50)] border border-transparent dark:hover:bg-[color:var(--color-success-950)]/30',
        ],
        'danger' => [
            'solid' => 'bg-[color:var(--color-danger)] text-white hover:bg-[color:var(--color-danger-600)] active:bg-[color:var(--color-danger-700)] border border-transparent',
            'outline' => 'bg-transparent border border-[color:var(--color-danger)] text-[color:var(--color-danger)] hover:bg-[color:var(--color-danger-50)] dark:hover:bg-[color:var(--color-danger-950)]/30',
            'ghost' => 'bg-transparent text-[color:var(--color-danger)] hover:bg-[color:var(--color-danger-50)] border border-transparent dark:hover:bg-[color:var(--color-danger-950)]/30',
        ],
        'warning' => [
            'solid' => 'bg-[color:var(--color-warning)] text-white hover:bg-[color:var(--color-warning-600)] active:bg-[color:var(--color-warning-700)] border border-transparent',
            'outline' => 'bg-transparent border border-[color:var(--color-warning)] text-[color:var(--color-warning)] hover:bg-[color:var(--color-warning-50)] dark:hover:bg-[color:var(--color-warning-950)]/30',
            'ghost' => 'bg-transparent text-[color:var(--color-warning)] hover:bg-[color:var(--color-warning-50)] border border-transparent dark:hover:bg-[color:var(--color-warning-950)]/30',
        ],
        'info' => [
            'solid' => 'bg-[color:var(--color-info)] text-white hover:bg-[color:var(--color-info-600)] active:bg-[color:var(--color-info-700)] border border-transparent',
            'outline' => 'bg-transparent border border-[color:var(--color-info)] text-[color:var(--color-info)] hover:bg-[color:var(--color-primary-50)] dark:hover:bg-[color:var(--color-primary-950)]/30',
            'ghost' => 'bg-transparent text-[color:var(--color-info)] hover:bg-[color:var(--color-primary-50)] border border-transparent dark:hover:bg-[color:var(--color-primary-950)]/30',
        ],
    ];

    $sizes = [
        'default' => [
            'sm' => 'h-8 px-3 text-xs gap-1.5',
            'md' => 'h-10 px-4 text-sm gap-2',
            'lg' => 'h-12 px-5 text-base gap-2.5',
        ],
        'square' => [
            'sm' => 'w-8 h-8 p-0 justify-center items-center shrink-0',
            'md' => 'w-10 h-10 p-0 justify-center items-center shrink-0',
            'lg' => 'w-12 h-12 p-0 justify-center items-center shrink-0',
        ],
        'circle' => [
            'sm' => 'w-8 h-8 p-0 justify-center items-center rounded-full shrink-0',
            'md' => 'w-10 h-10 p-0 justify-center items-center rounded-full shrink-0',
            'lg' => 'w-12 h-12 p-0 justify-center items-center rounded-full shrink-0',
        ],
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

    // Disabled flag evaluates boolean check
    $isDisabled = ($attributes->has('disabled') && $attributes->get('disabled') !== 'false') || $loading;

    // Resolve styling class combinations
    $intentStyle = $intentClasses[$intent][$appearance] ?? $intentClasses['primary']['solid'];
    
    // Size class selection
    $shapeStyles = $sizes[$shape] ?? $sizes['default'];
    $sizeClass = $shapeStyles[$size] ?? $shapeStyles['md'];
    
    // Circle shape enforces rounded-full, otherwise resolve rounded prop
    $roundedClass = '';
    if ($shape !== 'circle') {
        $roundedClass = isset($radii[$rounded]) ? $radii[$rounded] : 'rounded-[var(--radius-md)]';
    }

    $baseClasses = 'inline-flex items-center justify-center font-semibold transition-all duration-[var(--motion-fast)] ease-[var(--motion-ease)] focus:outline-none focus-visible:ring-2 focus-visible:ring-[color:var(--focus-ring-color)] focus-visible:ring-offset-2 active:scale-[var(--motion-scale-active)] disabled:pointer-events-none disabled:opacity-50 select-none cursor-pointer';

    // Swapping tags
    $tag = ($href && !$isDisabled) ? 'a' : 'button';
    
    // Setup dynamic link options
    $anchorProps = [];
    if ($href) {
        if ($isDisabled) {
            $anchorProps = [
                'role' => 'link',
                'aria-disabled' => 'true',
                'tabindex' => '-1',
            ];
        } else {
            $anchorProps = [
                'href' => $href,
            ];
        }
    }
@endphp

<{{ $tag }} 
    @if($tag === 'button')
        type="{{ $type }}"
        @if($isDisabled) disabled @endif
    @endif
    @foreach($anchorProps as $k => $v)
        {{ $k }}="{{ $v }}"
    @endforeach
    data-button
    data-intent="{{ $intent }}"
    data-appearance="{{ $appearance }}"
    data-loading="{{ $loading ? 'true' : 'false' }}"
    data-shape="{{ $shape }}"
    @if($loading)
        aria-busy="true"
        aria-disabled="true"
    @endif
    {{ $attributes->except(['intent', 'appearance', 'size', 'shape', 'type', 'loading', 'fullWidth', 'rounded', 'href', 'disabled'])->class([
        $baseClasses,
        $intentStyle,
        $sizeClass,
        $roundedClass,
        'w-full' => $fullWidth,
    ]) }}
>
    @if ($loading)
        <x-spinner size="sm" class="shrink-0" />
        <span class="sr-only">Loading...</span>
    @elseif (isset($prefix))
        <span class="inline-flex shrink-0 items-center justify-center pointer-events-none">
            {{ $prefix }}
        </span>
    @endif

    {{ $slot }}

    @if (!$loading && isset($suffix))
        <span class="inline-flex shrink-0 items-center justify-center pointer-events-none">
            {{ $suffix }}
        </span>
    @endif
</{{ $tag }}>
