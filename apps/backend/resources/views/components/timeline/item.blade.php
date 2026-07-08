@props([
    'item' => null,
    'status' => 'default',
    'title' => null,
    'timestamp' => null,
    'datetime' => null,
    'lineStyle' => 'solid',
    'href' => null,
    'actorInitials' => null,
    'actorName' => null,
    'iconName' => null,
])

@php
    // If a DTO item is provided, extract properties
    if ($item) {
        $title = $item->title;
        $status = $item->variant;
        $timestamp = $item->formatTimeForDashboard();
        $href = $item->href;
        $actorInitials = $item->actorInitials;
        $actorName = $item->actorName;
        $iconName = $item->icon;
        $description = $item->description;
    } else {
        $description = null;
    }

    $isInteractive = filled($href);

    $statusClasses = [
        'default' => 'text-[color:var(--color-neutral-500)] border-[color:var(--color-border)] bg-neutral-100',
        'success' => 'text-emerald-700 border-emerald-200 bg-emerald-50 ring-2 ring-emerald-100/50',
        'warning' => 'text-amber-700 border-amber-200 bg-amber-50 ring-2 ring-amber-100/50',
        'danger'  => 'text-rose-700 border-rose-200 bg-rose-50 ring-2 ring-rose-100/50',
        'info'    => 'text-blue-700 border-blue-200 bg-blue-50 ring-2 ring-blue-100/50',
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
    'relative ps-14 block group/timeline-item',
    '[&:last-child>.connector-line]:hidden'
]) }}>
    <!-- Connector Line -->
    <span class="connector-line absolute top-1 -bottom-6 left-[15px] w-[2px] border-l-[2px] {{ $currentLineClass }} border-slate-200 z-0" aria-hidden="true"></span>
    
    <!-- Timeline Circle Icon -->
    <span class="absolute left-0 top-1 flex items-center justify-center w-8 h-8 rounded-full border-2 {{ $currentStatusClass }} z-10 text-center select-none" aria-hidden="true">
        @if(isset($icon))
            {{ $icon }}
        @elseif($iconName)
            <x-icons.lucide name="{{ $iconName }}" class="w-4 h-4 shrink-0" />
        @else
            @php
                $dotClass = str_replace(['text-[color:var(--', 'text-'], ['bg-[color:var(--', 'bg-'], $currentStatusClass);
            @endphp
            <span class="w-2.5 h-2.5 rounded-full {{ $dotClass }}"></span>
        @endif
    </span>

    <!-- Interactive click anchor wrapping the card -->
    @if($isInteractive)
        <a 
            href="{{ $href }}" 
            class="absolute inset-0 z-20 rounded-xl focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[color:var(--focus-ring-color)] focus-visible:ring-offset-2" 
            aria-label="Investigate event details: {{ $title }}. {{ $description ?? '' }}"
        ></a>
    @endif

    <div class="flex flex-col gap-1 pb-6 transition-all duration-[var(--motion-fast)] group-hover/timeline-item:translate-x-0.5">
        @if($timestamp)
            <time @if($datetime) datetime="{{ $datetime }}" @endif class="text-[11px] font-bold text-neutral-400 block tracking-wide uppercase">
                {{ $timestamp }}
            </time>
        @endif

        @if($title || isset($badge) || $actorName)
            <div class="flex items-center gap-2 flex-wrap mt-0.5">
                @if($title)
                    <span class="text-sm font-bold text-[color:var(--color-text-primary)] leading-none transition-colors group-hover/timeline-item:text-[color:var(--color-brand-600)]">
                        {{ $title }}
                    </span>
                @endif
                
                @if($actorName)
                    <div class="flex items-center gap-1.5 inline-flex">
                        @if($actorInitials)
                            <span class="flex items-center justify-center w-5 h-5 rounded-full bg-neutral-100 border border-neutral-200 text-[8px] font-bold text-neutral-500 uppercase shrink-0" title="Actor: {{ $actorName }}">
                                {{ $actorInitials }}
                            </span>
                        @endif
                        <span class="text-[11px] font-medium text-neutral-400">
                            by {{ $actorName }}
                        </span>
                    </div>
                @endif

                @if(isset($badge))
                    <div class="flex items-center">{{ $badge }}</div>
                @endif
            </div>
        @endif

        @if($slot->isNotEmpty())
            <div class="text-xs leading-relaxed text-neutral-600 mt-1 max-w-2xl">
                {{ $slot }}
            </div>
        @elseif(isset($description))
            <div class="text-xs leading-relaxed text-neutral-600 mt-1 max-w-2xl line-clamp-2" title="{{ $description }}">
                {{ $description }}
            </div>
        @endif
    </div>
</li>
