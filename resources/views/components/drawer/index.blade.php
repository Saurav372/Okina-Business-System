@props([
    'id',
    'title' => null,
    'size' => 'md',
    'placement' => 'right',
    'persistent' => false,
    'busy' => false,
    'initialFocus' => null,
    'showHeader' => true,
    'description' => null,
])

@php
    // Independent size presets mapping
    $sizeMap = [
        'sm'   => 'max-w-sm w-full',
        'md'   => 'max-w-md w-full',
        'lg'   => 'max-w-lg w-full',
        'xl'   => 'max-w-xl w-full',
        '2xl'  => 'max-w-2xl w-full',
        'full' => 'max-w-full w-full',
    ];
    $sizeClass = $sizeMap[$size] ?? $sizeMap['md'];

    // Dynamic placement mapping controlling positioning and sliding motion transitions
    $placementMap = [
        'right' => [
            'wrapper' => 'justify-end',
            'panel' => 'right-0 top-0 bottom-0 h-full border-l',
            'enter-start' => 'translate-x-full',
            'enter-end' => 'translate-x-0',
            'leave-start' => 'translate-x-0',
            'leave-end' => 'translate-x-full',
        ],
        'left' => [
            'wrapper' => 'justify-start',
            'panel' => 'left-0 top-0 bottom-0 h-full border-r',
            'enter-start' => '-translate-x-full',
            'enter-end' => 'translate-x-0',
            'leave-start' => 'translate-x-0',
            'leave-end' => '-translate-x-full',
        ],
        'top' => [
            'wrapper' => 'items-start',
            'panel' => 'top-0 left-0 right-0 w-full border-b',
            'enter-start' => '-translate-y-full',
            'enter-end' => 'translate-y-0',
            'leave-start' => 'translate-y-0',
            'leave-end' => '-translate-y-full',
        ],
        'bottom' => [
            'wrapper' => 'items-end',
            'panel' => 'bottom-0 left-0 right-0 w-full border-t',
            'enter-start' => 'translate-y-full',
            'enter-end' => 'translate-y-0',
            'leave-start' => 'translate-y-0',
            'leave-end' => 'translate-y-full',
        ],
    ];

    $cfg = $placementMap[$placement] ?? $placementMap['right'];
    $titleId = $id . '-title';
    $descId = $id . '-description';
@endphp

{{-- Named Alpine component registered in app.js --}}
<div
    x-data="drawer({
        id: '{{ $id }}',
        persistent: {{ $persistent ? 'true' : 'false' }},
        busy: {{ $busy ? 'true' : 'false' }},
        initialFocus: '{{ $initialFocus ?? '' }}'
    })"
    @open-overlay.window="if (getOverlayId($event.detail) === id) openDrawer()"
    @close-overlay.window="if (getOverlayId($event.detail) === id) closeDrawer()"
    @keydown.escape.window="if (!persistent && !busy && isTopmost()) closeDrawer()"
>
    <template x-teleport="body">
        {{-- Full viewport wrapper --}}
        <div
            x-show="open"
            x-cloak
            class="fixed inset-0 z-modal flex {{ $cfg['wrapper'] }}"
            @keydown.tab.prevent="focusTrap($event)"
            aria-live="assertive"
        >
            {{-- Backdrop --}}
            <div
                class="fixed inset-0 bg-neutral-900/50 backdrop-blur-sm"
                x-show="open"
                x-transition:enter="ease-[var(--motion-ease)] duration-[var(--motion-normal)]"
                x-transition:enter-start="opacity-0"
                x-transition:enter-end="opacity-100"
                x-transition:leave="ease-[var(--ease-in)] duration-[var(--duration-200)]"
                x-transition:leave-start="opacity-100"
                x-transition:leave-end="opacity-0"
                @click="if (!persistent && !busy) closeDrawer()"
                aria-hidden="true"
            ></div>

            {{-- Drawer Slide Panel --}}
            <div
                data-overlay-id="{{ $id }}"
                role="dialog"
                aria-modal="true"
                aria-labelledby="{{ $titleId }}"
                @if(filled($description)) aria-describedby="{{ $descId }}" @endif
                class="relative {{ $sizeClass }} {{ $cfg['panel'] }} flex flex-col h-full bg-white shadow-2xl border-[color:var(--color-border,#e5e7eb)] overflow-hidden"
                x-show="open"
                x-transition:enter="transition-transform ease-[var(--motion-ease)] duration-[var(--motion-normal)]"
                x-transition:enter-start="{{ $cfg['enter-start'] }}"
                x-transition:enter-end="{{ $cfg['enter-end'] }}"
                x-transition:leave="transition-transform ease-[var(--ease-in)] duration-[var(--duration-200)]"
                x-transition:leave-start="{{ $cfg['leave-start'] }}"
                x-transition:leave-end="{{ $cfg['leave-end'] }}"
            >
                {{-- Header --}}
                @if($showHeader)
                    @if(isset($header))
                        {{ $header }}
                    @else
                        <div class="flex-shrink-0 flex items-start justify-between gap-4 px-6 py-5 border-b border-[color:var(--color-border,#e5e7eb)] bg-white">
                            <h2
                                id="{{ $titleId }}"
                                class="text-base font-bold leading-snug text-[color:var(--color-text-primary)]"
                            >
                                {{ $title ?? '' }}
                            </h2>
                            @if(!$persistent && !$busy)
                                @if(isset($close))
                                    {{ $close }}
                                @else
                                    <button
                                        type="button"
                                        @click="closeDrawer()"
                                        class="flex-shrink-0 -m-1.5 p-1.5 rounded-lg text-[color:var(--color-text-muted)] hover:text-[color:var(--color-text-primary)] hover:bg-[color:var(--color-surface-secondary)] transition-colors outline-none focus-visible:ring-2 focus-visible:ring-[color:var(--color-primary-500)]"
                                        aria-label="Close panel"
                                    >
                                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                        </svg>
                                    </button>
                                @endif
                            @endif
                        </div>
                    @endif
                @endif

                {{-- Scrollable Body --}}
                <div
                    @if(filled($description)) id="{{ $descId }}" @endif
                    class="flex-1 overflow-y-auto px-6 py-6 text-sm text-[color:var(--color-text-secondary,#4b5563)] bg-white"
                >
                    {{ $slot }}
                </div>

                {{-- Footer --}}
                @if(isset($footer))
                    <div class="flex-shrink-0 flex items-center justify-end gap-3 px-6 py-5 bg-[color:var(--color-surface-secondary,#f9fafb)] border-t border-[color:var(--color-border,#e5e7eb)]">
                        {{ $footer }}
                    </div>
                @endif
            </div>
        </div>
    </template>
</div>
