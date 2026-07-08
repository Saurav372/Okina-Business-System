@props([
    'type' => 'fade',
    'effect' => null,
    'duration' => 'normal',
    'easing' => 'ease',
    'delay' => null,
    'appear' => false,
    'transform' => true,
    'show' => null,
    'tag' => 'div',
    'xCloak' => true,
    'origin' => 'center',
])

@php
    // Whitelists
    $validDurations = ['fast', 'normal', 'slow', '75', '100', '150', '200', '300', '500', '700', '1000'];
    $validDelays = ['75', '100', '150', '200', '300', '500', '700', '1000'];
    $validOrigins = ['center', 'top', 'bottom', 'left', 'right', 'top-left', 'top-right', 'bottom-left', 'bottom-right'];
    $validEasings = ['ease', 'linear', 'in', 'out', 'in-out', 'bounce'];

    // Registry of basic transition parts
    $registry = [
        'fade' => [
            'enter-start' => 'opacity-0',
            'enter-end' => 'opacity-100',
            'leave-start' => 'opacity-100',
            'leave-end' => 'opacity-0',
            'transform' => false
        ],
        'slide-up' => [
            'enter-start' => 'translate-y-4',
            'enter-end' => 'translate-y-0',
            'leave-start' => 'translate-y-0',
            'leave-end' => 'translate-y-4',
            'transform' => true
        ],
        'slide-down' => [
            'enter-start' => '-translate-y-4',
            'enter-end' => 'translate-y-0',
            'leave-start' => 'translate-y-0',
            'leave-end' => '-translate-y-4',
            'transform' => true
        ],
        'slide-left' => [
            'enter-start' => 'translate-x-4',
            'enter-end' => 'translate-x-0',
            'leave-start' => 'translate-x-0',
            'leave-end' => 'translate-x-4',
            'transform' => true
        ],
        'slide-right' => [
            'enter-start' => '-translate-x-4',
            'enter-end' => 'translate-x-0',
            'leave-start' => 'translate-x-0',
            'leave-end' => '-translate-x-4',
            'transform' => true
        ],
        'scale' => [
            'enter-start' => 'scale-95',
            'enter-end' => 'scale-100',
            'leave-start' => 'scale-100',
            'leave-end' => 'scale-95',
            'transform' => true
        ]
    ];

    // Check if type is collapse
    $isCollapse = $type === 'collapse';

    // Normalize duration
    $normDuration = in_array((string)$duration, $validDurations) ? (string)$duration : 'normal';
    $durationVal = match($normDuration) {
        'fast' => 'var(--motion-fast)',
        'normal' => 'var(--motion-normal)',
        'slow' => 'var(--motion-slow)',
        '75', '100', '150', '200', '300', '500', '700', '1000' => "var(--duration-{$normDuration})",
        default => 'var(--motion-normal)'
    };
    $durationClass = "duration-[{$durationVal}]";
    if (in_array($normDuration, ['75', '100', '150', '200', '300', '500', '700', '1000'])) {
        $durationClass = "duration-{$normDuration}";
    }

    // Normalize easing
    $normEasing = in_array((string)$easing, $validEasings) ? (string)$easing : 'ease';
    $easingVal = match($normEasing) {
        'ease' => 'var(--motion-ease)',
        'linear' => 'var(--ease-linear)',
        'in' => 'var(--ease-in)',
        'out' => 'var(--ease-out)',
        'in-out' => 'var(--ease-in-out)',
        'bounce' => 'var(--ease-bounce)',
        default => 'var(--motion-ease)'
    };
    $easingClass = "ease-[{$easingVal}]";

    // Normalize delay
    $delayClass = '';
    if (filled($delay) && in_array((string)$delay, $validDelays)) {
        $delayClass = "delay-{$delay}";
    }

    // Normalize origin
    $normOrigin = in_array((string)$origin, $validOrigins) ? (string)$origin : 'center';
    $originClass = "origin-{$normOrigin}";

    // Handle warning for collapse + transform conflicts, and auto-ignore transform effects
    $hasIncompatibleTransformEffects = false;
    if ($isCollapse) {
        $transform = false;
        if (filled($effect)) {
            $effectParts = preg_split('/[\s,]+/', strtolower((string)$effect), -1, PREG_SPLIT_NO_EMPTY);
            foreach ($effectParts as $part) {
                if (isset($registry[$part]) && $registry[$part]['transform']) {
                    $hasIncompatibleTransformEffects = true;
                }
            }
        }
        if ($hasIncompatibleTransformEffects && app()->isLocal() && config('app.debug')) {
            logger()->warning("Collapse motion type does not support scaling or translation transforms. Incompatible transform effects were automatically stripped.");
        }
    }

    // Accumulate transition classes
    $enterStarts = [];
    $enterEnds = [];
    $leaveStarts = [];
    $leaveEnds = [];
    $requiresTransformEnter = false;
    $requiresTransformLeave = false;

    // Parse base type (except collapse)
    if (!$isCollapse) {
        $baseType = strtolower((string)$type);
        if (isset($registry[$baseType])) {
            $config = $registry[$baseType];
            $isTransformAllowed = !$config['transform'] || $transform;
            
            if ($isTransformAllowed) {
                $enterStarts[] = $config['enter-start'];
                $enterEnds[] = $config['enter-end'];
                $leaveStarts[] = $config['leave-start'];
                $leaveEnds[] = $config['leave-end'];
                if ($config['transform']) {
                    $requiresTransformEnter = true;
                    $requiresTransformLeave = true;
                }
            } else {
                $fadeConfig = $registry['fade'];
                $enterStarts[] = $fadeConfig['enter-start'];
                $enterEnds[] = $fadeConfig['enter-end'];
                $leaveStarts[] = $fadeConfig['leave-start'];
                $leaveEnds[] = $fadeConfig['leave-end'];
            }
        } else {
            if (app()->isLocal() && config('app.debug')) {
                logger()->warning("Unknown motion type: {$type}");
            }
        }
    }

    // Parse additional effects
    if (!$isCollapse && filled($effect)) {
        $effectParts = preg_split('/[\s,]+/', strtolower((string)$effect), -1, PREG_SPLIT_NO_EMPTY);
        
        foreach ($effectParts as $eff) {
            if (isset($registry[$eff])) {
                $config = $registry[$eff];
                $isTransformAllowed = !$config['transform'] || $transform;
                
                if ($isTransformAllowed) {
                    $enterStarts[] = $config['enter-start'];
                    $enterEnds[] = $config['enter-end'];
                    $leaveStarts[] = $config['leave-start'];
                    $leaveEnds[] = $config['leave-end'];
                    if ($config['transform']) {
                        $requiresTransformEnter = true;
                        $requiresTransformLeave = true;
                    }
                } else {
                    $fadeConfig = $registry['fade'];
                    $enterStarts[] = $fadeConfig['enter-start'];
                    $enterEnds[] = $fadeConfig['enter-end'];
                    $leaveStarts[] = $fadeConfig['leave-start'];
                    $leaveEnds[] = $fadeConfig['leave-end'];
                }
            } else {
                if (app()->isLocal() && config('app.debug')) {
                    logger()->warning("Unknown motion effect: {$eff}");
                }
            }
        }
    }

    // Deduplicate and filter classes
    $enterStartClass = implode(' ', array_unique(array_filter($enterStarts)));
    $enterEndClass = implode(' ', array_unique(array_filter($enterEnds)));
    $leaveStartClass = implode(' ', array_unique(array_filter($leaveStarts)));
    $leaveEndClass = implode(' ', array_unique(array_filter($leaveEnds)));

    // Set transition durations and easings
    $transformEnterAttr = $requiresTransformEnter ? ' transform' : '';
    $transformLeaveAttr = $requiresTransformLeave ? ' transform' : '';
    
    $originClassString = ($requiresTransformEnter || $requiresTransformLeave) ? " {$originClass}" : '';

    $enterClass = trim("transition {$easingClass} {$durationClass} {$delayClass}{$transformEnterAttr}{$originClassString}");
    $leaveClass = trim("transition ease-[var(--ease-in)] duration-[var(--duration-200)] {$delayClass}{$transformLeaveAttr}{$originClassString}");

    $enterAttr = $appear ? 'x-transition:enter.appear' : 'x-transition:enter';
    $leaveAttr = $appear ? 'x-transition:leave.appear' : 'x-transition:leave';
@endphp

@if($isCollapse)
    <{{ $tag }}
        @if(filled($show)) x-show="{{ $show }}" @endif
        @if($xCloak) x-cloak @endif
        {{ $attributes->merge([
            'class' => implode(' ', array_filter(['ui-motion', 'grid', 'transition-all', $durationClass, $easingClass, $delayClass, 'overflow-hidden']))
        ]) }}
        @if(filled($show)) :class="{{ $show }} ? 'grid-rows-[1fr] opacity-100' : 'grid-rows-[0fr] opacity-0'" @endif
    >
        <div class="min-h-0 overflow-hidden">
            {{ $slot }}
        </div>
    </{{ $tag }}>
@else
    <{{ $tag }}
        @if(filled($show)) x-show="{{ $show }}" @endif
        @if($xCloak) x-cloak @endif
        {{ $attributes->merge([
            'class' => 'ui-motion',
            $enterAttr => $enterClass,
            'x-transition:enter-start' => $enterStartClass,
            'x-transition:enter-end' => $enterEndClass,
            $leaveAttr => $leaveClass,
            'x-transition:leave-start' => $leaveStartClass,
            'x-transition:leave-end' => $leaveEndClass
        ]) }}
    >
        {{ $slot }}
    </{{ $tag }}>
@endif
