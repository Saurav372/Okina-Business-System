@props([
    'type' => 'info', // info, success, warning, danger, error
    'title' => null,
    'dismissible' => false,
])

@php
    $typeClasses = match ($type) {
        'success' => [
            'bg' => 'bg-emerald-50/50 border-emerald-100 text-emerald-950',
            'icon' => 'text-emerald-500',
            'iconSvg' => '<svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>'
        ],
        'danger', 'error' => [
            'bg' => 'bg-rose-50/50 border-rose-100 text-rose-950',
            'icon' => 'text-rose-500',
            'iconSvg' => '<svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>'
        ],
        'warning' => [
            'bg' => 'bg-amber-50/50 border-amber-100 text-amber-950',
            'icon' => 'text-amber-500',
            'iconSvg' => '<svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>'
        ],
        default => [
            'bg' => 'bg-sky-50/50 border-sky-100 text-sky-950',
            'icon' => 'text-sky-500',
            'iconSvg' => '<svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>'
        ],
    };
@endphp

<div 
    x-data="{ show: true }"
    x-show="show"
    x-transition:leave="transition ease-in duration-100 transform"
    x-transition:leave-start="opacity-100 scale-100"
    x-transition:leave-end="opacity-0 scale-95"
    class="flex items-start gap-3 p-4 border rounded-xl shadow-xs transition duration-200 {{ $typeClasses['bg'] }} {{ $attributes->get('class') }}"
    role="alert"
    {{ $attributes->whereDoesntStartWith('class') }}
>
    <!-- Icon -->
    <div class="flex-shrink-0 mt-0.5 {{ $typeClasses['icon'] }}">
        {!! $typeClasses['iconSvg'] !!}
    </div>

    <!-- Content -->
    <div class="flex-1 text-sm leading-normal">
        @if($title)
            <h4 class="font-bold mb-1">{{ $title }}</h4>
        @endif
        <div class="font-medium text-neutral-800">{{ $slot }}</div>
    </div>

    <!-- Dismiss button -->
    @if($dismissible)
        <button 
            type="button" 
            @click="show = false"
            class="flex-shrink-0 -m-1.5 p-1.5 rounded-lg text-neutral-400 hover:text-neutral-900 transition-colors outline-none focus-visible:ring-2 focus-visible:ring-neutral-400"
            aria-label="Dismiss alert"
        >
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
            </svg>
        </button>
    @endif
</div>
