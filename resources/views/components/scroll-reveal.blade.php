@props([
    'as' => 'div',
    'type' => 'fade',
    'speed' => 'normal',
    'delay' => 'none',
    'once' => true,
    'threshold' => 0.1,
    'rootMargin' => '0px 0px 0px 0px',
])

@php
    // Validate element tag wrapper
    $allowedTags = ['div', 'section', 'article', 'aside', 'main', 'header', 'footer', 'nav', 'ul', 'li', 'figure'];
    $resolvedTag = in_array(strtolower($as), $allowedTags) ? strtolower($as) : 'div';

    // Validate and map animation type
    $allowedTypes = ['fade', 'slide-up', 'slide-down', 'slide-left', 'slide-right', 'scale-up', 'scale-down'];
    $resolvedType = in_array(strtolower($type), $allowedTypes) ? strtolower($type) : 'fade';

    // Clamp threshold between 0 and 1 (single numeric threshold only)
    $clampedThreshold = max(0.0, min(1.0, (float)$threshold));

    // Token duration mappings
    $speeds = [
        'fast' => '150ms',
        'normal' => '300ms',
        'slow' => '500ms',
    ];
    if (array_key_exists($speed, $speeds)) {
        $resolvedSpeed = $speeds[$speed];
    } elseif (preg_match('/^\d+(\.\d+)?(ms|s)$/i', trim($speed))) {
        $resolvedSpeed = trim($speed);
    } else {
        $resolvedSpeed = $speeds['normal'];
    }

    // Token delay mappings
    $delays = [
        'none' => '0ms',
        'xs' => '75ms',
        'sm' => '150ms',
        'md' => '300ms',
        'lg' => '500ms',
        'xl' => '1000ms',
    ];
    if (array_key_exists($delay, $delays)) {
        $resolvedDelay = $delays[$delay];
    } elseif (preg_match('/^\d+(\.\d+)?(ms|s)$/i', trim($delay))) {
        $resolvedDelay = trim($delay);
    } else {
        $resolvedDelay = $delays['none'];
    }

    // Build style properties using CSS custom properties for duration/delay
    $styleParts = [];
    if (!empty($resolvedSpeed)) {
        $styleParts[] = "--reveal-duration: {$resolvedSpeed}";
    }
    if (!empty($resolvedDelay) && $resolvedDelay !== '0ms') {
        $styleParts[] = "--reveal-delay: {$resolvedDelay}";
    }
    $styleString = implode('; ', $styleParts);

    // Convert variables for client-side JS config
    $onceJs = $once ? 'true' : 'false';
@endphp

<{{ $resolvedTag }}
    x-data="{ revealed: false }"
    x-init="
        if (!window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        revealed = true;
                        if ({{ $onceJs }}) {
                            observer.unobserve(entry.target);
                        }
                    } else if (!{{ $onceJs }}) {
                        revealed = false;
                    }
                });
            }, { 
                threshold: {{ $clampedThreshold }}, 
                rootMargin: '{{ $rootMargin }}' 
            });
            observer.observe($el);
            return () => observer.disconnect();
        } else {
            revealed = true;
        }
    "
    :class="revealed ? 'is-revealed' : 'is-hidden'"
    {{ $attributes->class([
        'ui-reveal',
        'ui-reveal-' . $resolvedType,
    ]) }}
    @if(!empty($styleString))
        style="{{ $styleString }}"
    @endif
>
    {{ $slot }}
</{{ $resolvedTag }}>
