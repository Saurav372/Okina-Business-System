@props([
    'status' => 'default',
    'title' => null,
    'timestamp' => null,
    'datetime' => null,
    'lineStyle' => 'solid',
])

@php
    $statusClasses = [
        'default' => 'text-[color:var(--color-text-muted)] border-[color:var(--color-border)] bg-[color:var(--color-surface)]',
        'success' => 'text-[color:var(--color-success)] border-[color:var(--color-success)] bg-[color:var(--color-surface)]',
        'warning' => 'text-[color:var(--color-warning)] border-[color:var(--color-warning)] bg-[color:var(--color-surface)]',
        'danger'  => 'text-[color:var(--color-danger)] border-[color:var(--color-danger)] bg-[color:var(--color-surface)]',
        'info'    => 'text-[color:var(--color-info)] border-[color:var(--color-info)] bg-[color:var(--color-surface)]',
    ];

    $lineStyleClasses = [
        'solid'  => 'border-solid',
        'dashed' => 'border-dashed',
        'hidden' => 'border-transparent',
    ];

    $currentStatusClass = $statusClasses[$status] ?? $statusClasses['default'];
    $currentLineClass = $lineStyleClasses[$lineStyle] ?? $lineStyleClasses['solid'];
@endphp

<li {{ $attributes->class(['relative ps-[var(--spacing-10)] group block']) }}>
    <!-- Connector Line -->
    <span class="absolute top-[var(--spacing-6)] bottom-[-var(--spacing-6)] left-[calc(var(--spacing-5)-1px)] w-[2px] border-l-[2px] {{ $currentLineClass }} border-[color:var(--color-border)] group-last:hidden" aria-hidden="true"></span>
    
    <!-- Icon or Dot -->
    <span class="absolute left-0 top-[var(--spacing-1)] flex items-center justify-center w-[var(--spacing-10)] h-[var(--spacing-6)]" aria-hidden="true">
        @if(isset($icon))
            <span class="w-[var(--spacing-5)] h-[var(--spacing-5)] flex items-center justify-center {{ $currentStatusClass }} bg-transparent border-none">
                {{ $icon }}
            </span>
        @else
            <!-- Default decorative dot -->
            <span class="w-[var(--spacing-3)] h-[var(--spacing-3)] rounded-full border-[2px] {{ $currentStatusClass }} z-10"></span>
        @endif
    </span>

    <div class="flex flex-col gap-[var(--spacing-1)]">
        @if($title || isset($badge))
            <div class="flex items-center gap-[var(--spacing-3)] flex-wrap">
                @if($title)
                    <span class="text-[length:var(--text-body)] font-[number:var(--font-weight-medium)] text-[color:var(--color-text-primary)] leading-tight">
                        {{ $title }}
                    </span>
                @endif
                @if(isset($badge))
                    {{ $badge }}
                @endif
            </div>
        @endif
        
        @if($timestamp)
            <time @if($datetime) datetime="{{ $datetime }}" @endif class="text-[length:var(--text-caption)] text-[color:var(--color-text-muted)] block">
                {{ $timestamp }}
            </time>
        @endif

        @if($slot->isNotEmpty())
            <div class="text-[length:var(--text-body)] text-[color:var(--color-text-secondary)] mt-[var(--spacing-2)]">
                {{ $slot }}
            </div>
        @endif
    </div>
</li>
