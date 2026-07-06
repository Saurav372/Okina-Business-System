{{-- Global Toast Container Element --}}
<div
    x-data="toastContainer()"
    @add-toast.window="add($event.detail)"
    @dismiss-toast.window="dismiss($event.detail)"
    @clear-toasts.window="clear()"
    class="fixed top-6 right-6 z-toast flex flex-col gap-3 w-full max-w-sm pointer-events-none p-4 max-sm:bottom-6 max-sm:top-auto max-sm:right-4 max-sm:left-4 max-sm:max-w-none"
    aria-live="polite"
>
    <template x-for="toast in toasts" :key="toast.id">
        <div
            {{-- Class-based exit transition to bypass DOM lingering constraints --}}
            class="pointer-events-auto shadow-xl border rounded-xl flex items-start gap-3 p-4 bg-white relative overflow-hidden w-full transition duration-[var(--motion-normal)] ease-[var(--motion-ease)] transform"
            :class="{
                'opacity-0 scale-95 -translate-y-2 pointer-events-none': toast.dismissing,
                'opacity-100 scale-100 translate-y-0': !toast.dismissing,
                'border-emerald-100 bg-emerald-50/5 text-emerald-950': toast.type === 'success',
                'border-rose-100 bg-rose-50/5 text-rose-950': toast.type === 'danger' || toast.type === 'error',
                'border-amber-100 bg-amber-50/5 text-amber-950': toast.type === 'warning',
                'border-sky-100 bg-sky-50/5 text-sky-950': toast.type === 'info',
            }"
            @mouseenter="pause(toast.id)"
            @mouseleave="resume(toast.id)"
            :role="toast.type === 'danger' || toast.type === 'error' ? 'alert' : 'status'"
            :aria-live="toast.type === 'danger' || toast.type === 'error' ? 'assertive' : 'polite'"
            aria-atomic="true"
        >
            {{-- Status Icons --}}
            <div class="flex-shrink-0 mt-0.5" :class="{
                'text-emerald-500': toast.type === 'success',
                'text-rose-500': toast.type === 'danger' || toast.type === 'error',
                'text-amber-500': toast.type === 'warning',
                'text-sky-500': toast.type === 'info',
            }">
                {{-- Success Icon --}}
                <template x-if="toast.type === 'success'">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </template>
                {{-- Danger Icon --}}
                <template x-if="toast.type === 'danger' || toast.type === 'error'">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </template>
                {{-- Warning Icon --}}
                <template x-if="toast.type === 'warning'">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                </template>
                {{-- Info Icon --}}
                <template x-if="toast.type === 'info'">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </template>
            </div>

            {{-- Message Body Content --}}
            <div class="flex-1 text-sm font-medium pr-4 leading-normal text-neutral-800" x-text="toast.message"></div>

            {{-- Dismiss Button --}}
            <button
                type="button"
                @click="dismiss(toast.id)"
                class="flex-shrink-0 -m-1.5 p-1.5 rounded-lg text-neutral-400 hover:text-neutral-900 transition-colors outline-none focus-visible:ring-2 focus-visible:ring-neutral-400"
                aria-label="Close notification"
            >
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>

            {{-- Linear Countdown Progress Bar --}}
            <div
                x-show="toast.duration > 0"
                class="absolute bottom-0 left-0 h-[3px] transition-[width] duration-75 ease-linear opacity-25"
                :class="{
                    'bg-emerald-500': toast.type === 'success',
                    'bg-rose-500': toast.type === 'danger' || toast.type === 'error',
                    'bg-amber-500': toast.type === 'warning',
                    'bg-sky-500': toast.type === 'info',
                }"
                :style="{ width: toast.progress + '%' }"
            ></div>
        </div>
    </template>
</div>
