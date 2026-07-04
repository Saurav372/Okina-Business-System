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
    'relative ps-14 block',
    '[&:last-child>.connector-line]:hidden'
]) }}>
    <!-- Connector Line -->
    <span class="connector-line absolute top-1 -bottom-6 left-[15px] w-[2px] border-l-[2px] {{ $currentLineClass }} border-slate-300 z-0" aria-hidden="true"></span>
    
    <!-- Icon or Dot -->
    <span class="absolute left-0 top-1 flex items-center justify-center w-8 h-8 rounded-full border-[2px] {{ $currentStatusClass }} z-10 bg-[color:var(--color-surface)]" aria-hidden="true">
        @if(isset($icon))
            <span class="w-4 h-4 flex items-center justify-center">
                {{ $icon }}
            </span>
        @else
            <!-- Default decorative dot -->
            @php
                $dotClass = str_replace(['text-[color:var(--', 'text-'], ['bg-[color:var(--', 'bg-'], $currentStatusClass);
            @endphp
            <span class="w-2.5 h-2.5 rounded-full {{ $dotClass }}"></span>
        @endif
    </span>

    <div class="flex flex-col gap-1 pb-4">
        @if($title || isset($badge))
            <div class="flex items-center gap-3 flex-wrap pt-1.5">
                @if($title)
                    <span class="text-[15px] font-semibold text-[color:var(--color-text-primary)] leading-none">
                        {{ $title }}
                    </span>
                @endif
                @if(isset($badge))
                    <div class="flex items-center">{{ $badge }}</div>
                @endif
            </div>
        @endif
        
        @if($timestamp)
            <time @if($datetime) datetime="{{ $datetime }}" @endif class="text-xs text-[color:var(--color-text-muted)] block mt-1">
                {{ $timestamp }}
            </time>
        @endif

        @if($slot->isNotEmpty())
            <div class="text-[15px] leading-[1.6] text-slate-500 mt-2 max-w-2xl">
                {{ $slot }}
            </div>
        @endif
    </div>
</li>
