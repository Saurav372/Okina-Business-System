@props([
    'id',
    'title' => null,
    'size' => 'md',
    'persistent' => false,
    'busy' => false,
    'initialFocus' => null,
    'description' => null,
])

@php
    $sizeMap = [
        'sm'   => 'max-w-sm w-full',
        'md'   => 'max-w-md w-full',
        'lg'   => 'max-w-lg w-full',
        'xl'   => 'max-w-xl w-full',
        '2xl'  => 'max-w-2xl w-full',
        'full' => 'max-w-full h-full rounded-none m-0',
    ];
    $sizeClass  = $sizeMap[$size] ?? $sizeMap['md'];
    $titleId    = $id . '-title';
    $descId     = $id . '-description';
    $isFull     = $size === 'full';
@endphp

{{-- Named Alpine component registered in app.js --}}
<div
    x-data="modal({
        id: '{{ $id }}',
        persistent: {{ $persistent ? 'true' : 'false' }},
        busy: {{ $busy ? 'true' : 'false' }},
        initialFocus: '{{ $initialFocus ?? '' }}'
    })"
    @open-modal.window="if ($event.detail === id) openModal()"
    @close-modal.window="if ($event.detail === id) closeModal()"
    @keydown.escape.window="if (!persistent && !busy && isTopmost()) closeModal()"
>
    <template x-teleport="body">
        {{-- Full viewport wrapper (handles scroll when content is tall) --}}
        <div
            x-show="open"
            x-cloak
            class="fixed inset-0 z-[200] flex {{ $isFull ? '' : 'items-center justify-center p-4 sm:p-6' }} overflow-y-auto"
            @keydown.tab.prevent="focusTrap($event)"
            aria-live="assertive"
        >
            {{-- Backdrop --}}
            <div
                class="fixed inset-0 bg-neutral-900/50 backdrop-blur-sm"
                x-show="open"
                x-transition:enter="ease-out duration-200"
                x-transition:enter-start="opacity-0"
                x-transition:enter-end="opacity-100"
                x-transition:leave="ease-in duration-150"
                x-transition:leave-start="opacity-100"
                x-transition:leave-end="opacity-0"
                @click="if (!persistent && !busy) closeModal()"
                aria-hidden="true"
            ></div>

            {{-- Dialog Panel --}}
            <div
                data-modal-id="{{ $id }}"
                role="dialog"
                aria-modal="true"
                aria-labelledby="{{ $titleId }}"
                @if(filled($description)) aria-describedby="{{ $descId }}" @endif
                class="relative {{ $sizeClass }} {{ $isFull ? 'min-h-full flex flex-col' : 'rounded-2xl' }} bg-white shadow-2xl ring-1 ring-black/5 overflow-hidden"
                x-show="open"
                x-transition:enter="ease-out duration-200"
                x-transition:enter-start="opacity-0 scale-95 translate-y-2"
                x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                x-transition:leave="ease-in duration-150"
                x-transition:leave-start="opacity-100 scale-100 translate-y-0"
                x-transition:leave-end="opacity-0 scale-95 translate-y-2"
            >
                {{-- Header --}}
                @if(isset($header))
                    {{ $header }}
                @else
                    <div class="flex items-start justify-between gap-4 px-6 py-4 border-b border-[color:var(--color-border,#e5e7eb)]">
                        <h2
                            id="{{ $titleId }}"
                            class="text-base font-bold leading-snug text-[color:var(--color-text-primary)]"
                        >
                            {{ $title ?? '' }}
                        </h2>
                        @if(!$persistent && !$busy)
                            <button
                                type="button"
                                @click="closeModal()"
                                class="flex-shrink-0 -m-1 p-1 rounded-lg text-[color:var(--color-text-muted)] hover:text-[color:var(--color-text-primary)] hover:bg-[color:var(--color-surface-secondary)] transition-colors outline-none focus-visible:ring-2 focus-visible:ring-[color:var(--color-primary-500)]"
                                aria-label="Close dialog"
                            >
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        @endif
                    </div>
                @endif

                {{-- Body --}}
                <div
                    @if(filled($description)) id="{{ $descId }}" @endif
                    class="px-6 py-5 text-sm text-[color:var(--color-text-secondary,#4b5563)] {{ $isFull ? 'flex-1 overflow-y-auto' : '' }}"
                >
                    {{ $slot }}
                </div>

                {{-- Footer --}}
                @if(isset($footer))
                    <div class="flex items-center justify-end gap-3 px-6 py-4 bg-[color:var(--color-surface-secondary,#f9fafb)] border-t border-[color:var(--color-border,#e5e7eb)]">
                        {{ $footer }}
                    </div>
                @endif
            </div>
        </div>
    </template>
</div>
