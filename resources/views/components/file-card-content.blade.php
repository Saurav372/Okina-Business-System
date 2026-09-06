@php
    // Sizing adjustments for list vs tile
    $mediaSize = $variant === 'list' ? 'w-8 h-8' : 'w-14 h-14';
@endphp

@if ($variant === 'tile')
    {{-- Media block (Thumbnail / Icon) --}}
    <div x-data="{ thumbLoaded: false, thumbError: false }" class="relative {{ $mediaSize }} shrink-0">
        @if ($showThumbnail)
            {{-- Skeleton placeholder while loading (motion-safe) --}}
            <div x-show="!thumbLoaded && !thumbError"
                 class="absolute inset-0 motion-safe:animate-pulse bg-[color:var(--color-neutral-200)] dark:bg-[color:var(--color-neutral-800)] rounded-[var(--radius-md)]"
                 aria-hidden="true">
            </div>

            {{-- Image fades in on load --}}
            <img src="{{ $thumbnail }}"
                 x-ref="img"
                 x-init="if ($refs.img.complete) thumbLoaded = true"
                 x-show="!thumbError"
                 x-on:load="thumbLoaded = true"
                 x-on:error="thumbError = true"
                 x-transition:enter="transition-opacity duration-300"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 class="absolute inset-0 w-full h-full object-cover rounded-[var(--radius-md)]"
                 alt=""
            />
        @endif

        {{-- Icon Fallback --}}
        <div x-show="{{ $showThumbnail ? '!thumbLoaded || thumbError' : 'true' }}"
             class="absolute inset-0 flex items-center justify-center rounded-[var(--radius-md)] {{ $fileType['bgClass'] }} {{ $fileType['iconClass'] }}"
             aria-hidden="true">
            @include('components.file-card-icons', ['icon' => $fileType['icon'], 'class' => 'w-6 h-6'])
        </div>
    </div>

    {{-- File info --}}
    <div class="flex flex-col items-center w-full min-w-0">
        <span class="flex items-center justify-center w-full text-sm font-semibold text-[color:var(--color-text-primary)] min-w-0">
            <span class="truncate block max-w-full">{{ $stem }}</span>
            @if($ext)
                <span class="shrink-0">.{{ $ext }}</span>
            @endif
        </span>
        
        <div class="flex items-center justify-center gap-1.5 mt-1 text-xs text-[color:var(--color-text-muted)] w-full min-w-0">
            @if ($size)
                <span class="shrink-0">{{ $size }}</span>
            @endif
            @if ($size && $showBadge)
                <span class="shrink-0 text-[color:var(--color-neutral-300)]" aria-hidden="true">•</span>
            @endif
            @if ($showBadge)
                <x-badge size="sm" intent="{{ $fileType['accent'] }}" appearance="light" class="shrink-0 font-semibold">
                    {{ $fileType['label'] }}
                </x-badge>
            @endif
        </div>
    </div>
@else
    {{-- list variant layout (row) --}}
    <div class="flex items-center gap-3 w-full min-w-0 pr-8">
        {{-- Media block --}}
        <div x-data="{ thumbLoaded: false, thumbError: false }" class="relative {{ $mediaSize }} shrink-0">
            @if ($showThumbnail)
                <div x-show="!thumbLoaded && !thumbError"
                     class="absolute inset-0 motion-safe:animate-pulse bg-[color:var(--color-neutral-200)] dark:bg-[color:var(--color-neutral-800)] rounded-[var(--radius-sm)]"
                     aria-hidden="true">
                </div>

                <img src="{{ $thumbnail }}"
                     x-ref="img"
                     x-init="if ($refs.img.complete) thumbLoaded = true"
                     x-show="!thumbError"
                     x-on:load="thumbLoaded = true"
                     x-on:error="thumbError = true"
                     x-transition:enter="transition-opacity duration-300"
                     x-transition:enter-start="opacity-0"
                     x-transition:enter-end="opacity-100"
                     class="absolute inset-0 w-full h-full object-cover rounded-[var(--radius-sm)]"
                     alt=""
                />
            @endif

            <div x-show="{{ $showThumbnail ? '!thumbLoaded || thumbError' : 'true' }}"
                 class="absolute inset-0 flex items-center justify-center rounded-[var(--radius-sm)] {{ $fileType['bgClass'] }} {{ $fileType['iconClass'] }}"
                 aria-hidden="true">
                @include('components.file-card-icons', ['icon' => $fileType['icon'], 'class' => 'w-4 h-4'])
            </div>
        </div>

        {{-- Filename (truncate stem, keep extension) --}}
        <div class="flex-1 min-w-0">
            <span class="flex items-center text-sm font-semibold text-[color:var(--color-text-primary)] min-w-0">
                <span class="truncate block">{{ $stem }}</span>
                @if($ext)
                    <span class="shrink-0">.{{ $ext }}</span>
                @endif
            </span>
        </div>

        {{-- Meta (Badge & size on the right side) --}}
        <div class="flex items-center gap-3 shrink-0 text-xs text-[color:var(--color-text-muted)]">
            @if ($showBadge)
                <x-badge size="sm" intent="{{ $fileType['accent'] }}" appearance="light" class="font-semibold">
                    {{ $fileType['label'] }}
                </x-badge>
            @endif
            @if ($size)
                <span class="font-medium shrink-0">{{ $size }}</span>
            @endif
        </div>
    </div>
@endif
