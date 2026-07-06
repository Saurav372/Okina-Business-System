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
        'sm' => 'max-w-sm w-full',
        'md' => 'max-w-md w-full',
        'lg' => 'max-w-lg w-full',
        'xl' => 'max-w-xl w-full',
        '2xl' => 'max-w-2xl w-full',
        'full' => 'max-w-full h-full rounded-none m-0',
    ];
    $sizeClass = $sizeMap[$size] ?? $sizeMap['md'];
@endphp

<div
    x-data="{
        open: false,
        lastActiveElement: null,
        id: '{{ $id }}',
        persistent: {{ $persistent ? 'true' : 'false' }},
        busy: {{ $busy ? 'true' : 'false' }},
        initialFocus: '{{ $initialFocus }}',

        openModal() {
            this.open = true;
            this.lastActiveElement = document.activeElement;
            
            // Increment active modals counter
            window.activeModals = (window.activeModals || 0) + 1;
            document.documentElement.style.overflow = 'hidden';

            // Push to stack
            window.modalStack = window.modalStack || [];
            window.modalStack.push(this.id);

            // Focus target element
            this.$nextTick(() => {
                let target = null;
                if (this.initialFocus) {
                    target = document.getElementById(this.initialFocus);
                }
                if (!target) {
                    const focusables = [...this.$el.querySelectorAll('button, [href], input, select, textarea, [tabindex=\'0\']')].filter(el => !el.hasAttribute('disabled') && el.getAttribute('tabindex') !== '-1');
                    if (focusables.length > 0) {
                        target = focusables[0];
                    }
                }
                if (target) {
                    target.focus();
                }
            });
        },

        closeModal() {
            if (this.busy) return;
            this.open = false;

            // Decrement active modals counter
            window.activeModals = Math.max(0, (window.activeModals || 1) - 1);
            if (window.activeModals === 0) {
                document.documentElement.style.overflow = '';
            }

            // Remove from stack
            window.modalStack = (window.modalStack || []).filter(x => x !== this.id);

            // Focus return
            if (this.lastActiveElement) {
                this.lastActiveElement.focus();
            }
        },

        focusTrap(e) {
            const focusables = [...this.$el.querySelectorAll('button, [href], input, select, textarea, [tabindex=\'0\']')].filter(el => !el.hasAttribute('disabled') && el.getAttribute('tabindex') !== '-1');
            if (focusables.length === 0) return;
            const first = focusables[0];
            const last = focusables[focusables.length - 1];

            if (e.shiftKey && document.activeElement === first) {
                last.focus();
                e.preventDefault();
            } else if (!e.shiftKey && document.activeElement === last) {
                first.focus();
                e.preventDefault();
            }
        },

        isTopmost() {
            const stack = window.modalStack || [];
            return stack[stack.length - 1] === this.id;
        }
    }"
    @open-modal.window="if ($event.detail === id || $event.detail?.id === id) openModal()"
    @close-modal.window="if ($event.detail === id || $event.detail?.id === id) closeModal()"
    @keydown.escape.window="if (!persistent && !busy && isTopmost()) closeModal()"
    class="ui-modal-wrapper"
>
    <template x-teleport="body">
        <div 
            x-show="open"
            class="fixed inset-0 z-50 flex items-center justify-center p-4 overflow-y-auto"
            style="display: none;"
            @keydown.tab="focusTrap($event)"
        >
            <!-- Backdrop Overlay -->
            <div 
                class="fixed inset-0 transition-opacity duration-300 backdrop-blur-sm bg-neutral-900/40"
                x-show="open"
                x-transition:enter="ease-out duration-300"
                x-transition:enter-start="opacity-0"
                x-transition:enter-end="opacity-100"
                x-transition:leave="ease-in duration-200"
                x-transition:leave-start="opacity-100"
                x-transition:leave-end="opacity-0"
                @click="if (!persistent && !busy) closeModal()"
                aria-hidden="true"
            ></div>

            <!-- Modal Content Panel -->
            <div 
                class="relative bg-white rounded-2xl shadow-2xl overflow-hidden transform transition-all duration-300 {{ $sizeClass }}"
                x-show="open"
                x-transition:enter="ease-out duration-300"
                x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                x-transition:leave="ease-in duration-200"
                x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                role="dialog"
                aria-modal="true"
                aria-labelledby="{{ $id }}-title"
                @if(filled($description))
                    aria-describedby="{{ $id }}-description"
                @endif
            >
                <!-- Header Slot / Default Title Header -->
                @if(isset($header))
                    {{ $header }}
                @else
                    <div class="flex items-center justify-between px-6 py-4 border-b border-[color:var(--color-border,#e5e7eb)]">
                        <h3 id="{{ $id }}-title" class="text-lg font-bold text-[color:var(--color-text-primary)]">
                            {{ $title ?? 'Notification' }}
                        </h3>
                        @if(!$persistent && !$busy)
                            <button 
                                type="button" 
                                @click="closeModal()" 
                                class="text-[color:var(--color-text-muted,#9ca3af)] hover:text-[color:var(--color-text-primary)] transition-colors rounded-lg p-1 outline-none focus-visible:ring-2 focus-visible:ring-[color:var(--color-primary-500)]"
                                aria-label="Close modal"
                            >
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        @endif
                    </div>
                @endif

                <!-- Body (Default Slot) -->
                <div 
                    @if(filled($description)) id="{{ $id }}-description" @endif
                    class="px-6 py-4 text-sm text-[color:var(--color-text-secondary,#4b5563)] whitespace-normal break-words"
                >
                    {{ $slot }}
                </div>

                <!-- Footer Slot -->
                @if(isset($footer))
                    <div class="flex items-center justify-end gap-3 px-6 py-4 bg-[color:var(--color-surface-secondary,#f9fafb)] border-t border-[color:var(--color-border,#e5e7eb)]">
                        {{ $footer }}
                    </div>
                @endif
            </div>
        </div>
    </template>
</div>
