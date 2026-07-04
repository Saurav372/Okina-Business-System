@props([
    'status' => 'default',
    'title' => null,
    'timestamp' => null,
    'datetime' => null,
    'lineStyle' => 'solid',
])

@php
    $statusClasses = [
        'default' => 'text-[color:var(--color-text-muted)] border-[color:var(--color-border)]',
        'success' => 'text-[color:var(--color-success)] border-[color:var(--color-success)]',
        'warning' => 'text-[color:var(--color-warning)] border-[color:var(--color-warning)]',
        'danger'  => 'text-[color:var(--color-danger)] border-[color:var(--color-danger)]',
        'info'    => 'text-[color:var(--color-info)] border-[color:var(--color-info)]',
    ];

    $lineStyleClasses = [
        'solid'  => 'border-solid',
        'dashed' => 'border-dashed',
        'hidden' => 'border-transparent',
    ];

    $currentStatusClass = $statusClasses[$status] ?? $statusClasses['default'];
    $currentLineClass = $lineStyleClasses[$lineStyle] ?? $lineStyleClasses['solid'];
@endphp

<li {{ $attributes->class([
    'relative ps-10 block',
    '[&:last-child>.connector-line]:hidden'
]) }}>
    <!-- Connector Line -->
    <span class="connector-line absolute top-6 -bottom-6 left-[19px] w-[2px] border-l-[2px] {{ $currentLineClass }} border-[color:var(--color-border)]" aria-hidden="true"></span>
    
    <!-- Icon or Dot -->
    <span class="absolute left-0 top-1 flex items-center justify-center w-10 h-6" aria-hidden="true">
        @if(isset($icon))
            <span class="w-5 h-5 flex items-center justify-center {{ $currentStatusClass }} bg-[color:var(--color-surface)]">
                {{ $icon }}
            </span>
        @else
            <!-- Default decorative dot -->
            <span class="w-3 h-3 rounded-full border-[2px] {{ $currentStatusClass }} z-10 bg-[color:var(--color-surface)]"></span>
        @endif
    </span>

    <div class="flex flex-col gap-1">
        @if($title || isset($badge))
            <div class="flex items-center gap-3 flex-wrap">
                @if($title)
                    <span class="text-[15px] font-medium text-[color:var(--color-text-primary)] leading-tight">
                        {{ $title }}
                    </span>
                @endif
                @if(isset($badge))
                    {{ $badge }}
                @endif
            </div>
        @endif
        
        @if($timestamp)
            <time @if($datetime) datetime="{{ $datetime }}" @endif class="text-xs text-slate-500 block mt-0.5">
                {{ $timestamp }}
            </time>
        @endif

        @if($slot->isNotEmpty())
            <div class="text-[15px] leading-[1.6] text-slate-500 mt-2">
                {{ $slot }}
            </div>
        @endif
    </div>
</li>
