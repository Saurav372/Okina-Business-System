@props([
    'src' => null,
    'name' => null,
    'size' => 'md', // sm, md, lg, xl
    'width' => null,
    'height' => null,
    'rounded' => 'full', // none, sm, md, lg, xl, 2xl, full
    'status' => null, // online, busy, away, offline
    'statusPosition' => 'bottom-right', // top-right, bottom-right, top-left, bottom-left
    'ring' => 'none', // none, sm, md, lg
])

@php
    // Normalize whitespace & treat empty string as null
    $nameClean = null;
    if ($name !== null) {
        $trimmed = trim($name);
        if ($trimmed !== '') {
            $nameClean = preg_replace('/\s+/', ' ', $trimmed);
        }
    }

    // Unicode Normalization NFC Form C (defensive fallback)
    $normalizedName = $nameClean;
    if ($nameClean !== null) {
        if (class_exists(\Normalizer::class)) {
            $normalizedName = \Normalizer::normalize($nameClean, \Normalizer::FORM_C);
        }
    }

    // Initials extraction closure
    $getInitials = function (?string $n) {
        if ($n === null) return '';
        $words = preg_split("/\s+/", $n);
        if (count($words) >= 2) {
            $firstChar = mb_strtoupper(mb_substr($words[0], 0, 1, 'UTF-8'), 'UTF-8');
            $lastChar = mb_strtoupper(mb_substr($words[count($words) - 1], 0, 1, 'UTF-8'), 'UTF-8');
            return $firstChar . $lastChar;
        }
        $word = $words[0];
        if (mb_strlen($word, 'UTF-8') >= 2) {
            return mb_strtoupper(mb_substr($word, 0, 2, 'UTF-8'), 'UTF-8');
        }
        return mb_strtoupper(mb_substr($word, 0, 1, 'UTF-8'), 'UTF-8');
    };

    $initials = $getInitials($normalizedName);

    // Hash normalized name deterministically
    $bgColors = [
        'bg-blue-100 text-blue-800 dark:bg-blue-950/30 dark:text-blue-400',
        'bg-indigo-100 text-indigo-800 dark:bg-indigo-950/30 dark:text-indigo-400',
        'bg-purple-100 text-purple-800 dark:bg-purple-950/30 dark:text-purple-400',
        'bg-teal-100 text-teal-800 dark:bg-teal-950/30 dark:text-teal-400',
        'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/30 dark:text-emerald-400',
        'bg-amber-100 text-amber-800 dark:bg-amber-950/30 dark:text-amber-400',
        'bg-orange-100 text-orange-800 dark:bg-orange-950/30 dark:text-orange-400',
        'bg-pink-100 text-pink-800 dark:bg-pink-950/30 dark:text-pink-400',
    ];

    $colorClass = 'bg-[color:var(--color-neutral-100)] text-[color:var(--color-text-muted)] dark:bg-[color:var(--color-neutral-800)]';
    if ($normalizedName !== null) {
        $hash = crc32(mb_strtolower($normalizedName, 'UTF-8'));
        $colorIndex = abs($hash) % count($bgColors);
        $colorClass = $bgColors[$colorIndex];
    }

    // Size presets
    $sizes = [
        'sm' => 'w-8 h-8 text-[11px]',
        'md' => 'w-10 h-10 text-xs',
        'lg' => 'w-12 h-12 text-sm',
        'xl' => 'w-16 h-16 text-base',
    ];
    $sizeClass = $sizes[$size] ?? $sizes['md'];

    // Radii mapping
    $radii = [
        'none' => 'rounded-none',
        'sm' => 'rounded-[var(--radius-sm)]',
        'md' => 'rounded-[var(--radius-md)]',
        'lg' => 'rounded-[var(--radius-lg)]',
        'xl' => 'rounded-[var(--radius-xl)]',
        '2xl' => 'rounded-[var(--radius-2xl)]',
        'full' => 'rounded-full',
    ];
    $radiusClass = $radii[$rounded] ?? $radii['full'];

    // Ring Token Resolving
    $rings = [
        'none' => 'ring-0',
        'sm' => 'ring-2 ring-[color:var(--color-surface-primary)]',
        'md' => 'ring-2 ring-offset-2 ring-[color:var(--color-primary)] ring-offset-[color:var(--color-surface-primary)]',
        'lg' => 'ring-4 ring-offset-4 ring-[color:var(--color-primary)] ring-offset-[color:var(--color-surface-primary)]',
    ];
    $ringClass = $rings[$ring] ?? $rings['none'];

    // Status Position mapping
    $statusPositions = [
        'top-right' => 'top-0 right-0 translate-x-1/4 -translate-y-1/4',
        'bottom-right' => 'bottom-0 right-0 translate-x-1/4 translate-y-1/4',
        'top-left' => 'top-0 left-0 -translate-x-1/4 -translate-y-1/4',
        'bottom-left' => 'bottom-0 left-0 -translate-x-1/4 translate-y-1/4',
    ];
    $statusPosClass = $statusPositions[$statusPosition] ?? $statusPositions['bottom-right'];

    // Status Sizing mapping
    $statusSizes = [
        'sm' => 'w-2 h-2',
        'md' => 'w-2.5 h-2.5',
        'lg' => 'w-3 h-3',
        'xl' => 'w-3.5 h-3.5',
    ];
    $statusSizeClass = $statusSizes[$size] ?? $statusSizes['md'];

    // Status Colors mapping (existing semantic design tokens)
    $statusColors = [
        'online' => 'bg-[color:var(--color-success)]',
        'busy' => 'bg-[color:var(--color-danger)]',
        'away' => 'bg-[color:var(--color-warning)]',
        'offline' => 'bg-[color:var(--color-neutral-400)]',
    ];
    $statusColorClass = $statusColors[$status] ?? $statusColors['offline'];

    // Width/Height Priority
    $styleRules = [];
    if ($width !== null) {
        $styleRules[] = 'width: ' . (is_numeric($width) ? $width . 'px' : $width);
    }
    if ($height !== null) {
        $styleRules[] = 'height: ' . (is_numeric($height) ? $height . 'px' : $height);
    }
    $inlineStyles = count($styleRules) > 0 ? implode('; ', $styleRules) . ';' : null;

    // Merge conditional attributes cleanly
    if ($status) {
        $attributes = $attributes->merge(['data-status' => $status]);
    }
@endphp

<div 
    x-data="{ imageError: false, hasImage: {{ $src ? 'true' : 'false' }} }" 
    class="relative inline-block shrink-0 select-none"
    {!! $inlineStyles ? 'style="' . e($inlineStyles) . '"' : '' !!}
>
    <!-- Avatar Base Element (z-0) -->
    <div 
        data-avatar
        data-size="{{ $size }}"
        data-rounded="{{ $rounded }}"
        {{ $attributes->except(['src', 'name', 'size', 'width', 'height', 'rounded', 'status', 'statusPosition', 'ring'])->class([
            'relative overflow-hidden w-full h-full flex items-center justify-center font-bold tracking-tight z-0 border border-transparent shadow-sm',
            $sizeClass => !$inlineStyles,
            $radiusClass,
            $ringClass,
        ]) }}
    >
        @if ($src)
            <!-- Unmount on failure using template to prevent broken retry loops -->
            <template x-if="!imageError">
                <img 
                    src="{{ $src }}" 
                    @error="imageError = true" 
                    x-show="!imageError"
                    class="w-full h-full object-cover pointer-events-none"
                    alt="{{ $normalizedName !== null ? $normalizedName : '' }}"
                />
            </template>
        @endif

        <!-- Fallback (Initials / Icon) -->
        <div 
            x-show="imageError || !hasImage"
            class="w-full h-full flex items-center justify-center {{ $colorClass }}"
        >
            @if ($initials !== '')
                <span>{{ $initials }}</span>
            @else
                <svg class="w-2/3 h-2/3 text-current" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                    <path d="M24 20.993V24H0v-2.996A14.977 14.977 0 0112.004 15c4.904 0 9.26 2.354 11.996 5.993zM16.002 8.999a4 4 0 11-8 0 4 4 0 018 0z" />
                </svg>
            @endif
        </div>
    </div>

    <!-- Status Indicator Overlay (z-20) -->
    @if ($status)
        <span 
            class="absolute block rounded-full ring-2 ring-[color:var(--color-surface-primary)] z-20 pointer-events-none {{ $statusPosClass }} {{ $statusSizeClass }} {{ $statusColorClass }}"
            aria-hidden="true"
        ></span>
    @endif
</div>
