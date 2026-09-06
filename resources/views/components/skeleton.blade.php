@aware(['animate' => 'shimmer'])

@props([
    'loading' => true,
    'variant' => 'line', // line, block, circle
    'rounded' => null,   // none, sm, md, lg, xl, 2xl, full
    'width' => null,
    'height' => null,
])

@php
    // If the explicit attribute was not passed, it uses the @aware value ($animate)
    $resolvedAnimate = $attributes->get('animate') ?? $animate;
    
    // Resolve border radius based on rounded prop and variant default
    $defaultRadii = [
        'line' => 'rounded-[var(--radius-sm)]',
        'block' => 'rounded-[var(--skeleton-radius)]',
        'circle' => 'rounded-full',
    ];
    
    $radiiClasses = [
        'none' => 'rounded-none',
        'sm' => 'rounded-[var(--radius-sm)]',
        'md' => 'rounded-[var(--radius-md)]',
        'lg' => 'rounded-[var(--radius-lg)]',
        'xl' => 'rounded-[var(--radius-xl)]',
        '2xl' => 'rounded-[var(--radius-2xl)]',
        'full' => 'rounded-full',
    ];
    
    $roundedClass = isset($radiiClasses[$rounded]) ? $radiiClasses[$rounded] : ($defaultRadii[$variant] ?? $defaultRadii['line']);
    
    // Resolve animation class
    $animationClasses = [
        'shimmer' => 'skeleton-shimmer',
        'pulse' => 'animate-pulse',
        'static' => '',
    ];
    $animationClass = $animationClasses[$resolvedAnimate] ?? $animationClasses['shimmer'];
    
    // Resolve sizes
    $defaultSizes = [
        'line' => 'w-full h-4',
        'block' => 'w-full h-32',
        'circle' => 'w-10 h-10 shrink-0 aspect-square',
    ];
    $sizeClass = $defaultSizes[$variant] ?? $defaultSizes['line'];
    
    // Resolve custom width & height
    $resolvedWidth = is_numeric($width) ? $width . 'px' : $width;
    $resolvedHeight = is_numeric($height) ? $height . 'px' : $height;
    
    $styleString = '';
    if ($resolvedWidth) {
        $styleString .= "width: {$resolvedWidth};";
    }
    if ($resolvedHeight) {
        $styleString .= "height: {$resolvedHeight};";
    }
@endphp

@if (isset($slot) && $slot->isNotEmpty())
    <div aria-busy="{{ $loading ? 'true' : 'false' }}" aria-live="polite" class="w-full">
        @if ($loading)
            <div 
                aria-hidden="true" 
                role="presentation" 
                data-skeleton
                data-variant="{{ $variant }}"
                data-animation="{{ $resolvedAnimate }}"
                {{ $attributes->except(['animate', 'loading', 'variant', 'rounded', 'width', 'height'])->class([
                    'skeleton-base select-none pointer-events-none overflow-hidden block',
                    $sizeClass,
                    $roundedClass,
                    $animationClass
                ]) }}
                @if ($styleString)
                    style="{{ $styleString }}"
                @endif
            ></div>
        @else
            {{ $slot }}
        @endif
    </div>
@else
    <div 
        aria-hidden="true" 
        role="presentation" 
        data-skeleton
        data-variant="{{ $variant }}"
        data-animation="{{ $resolvedAnimate }}"
        {{ $attributes->except(['animate', 'loading', 'variant', 'rounded', 'width', 'height'])->class([
            'skeleton-base select-none pointer-events-none overflow-hidden block',
            $sizeClass,
            $roundedClass,
            $animationClass
        ]) }}
        @if ($styleString)
            style="{{ $styleString }}"
        @endif
    ></div>
@endif
