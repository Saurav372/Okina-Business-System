@props(['name', 'label' => null])

<svg {{ $attributes->merge(['class' => 'sf-icon']) }} viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" @if($label) role="img" aria-label="{{ $label }}" @else aria-hidden="true" @endif>
    @switch($name)
        @case('shirt')
            <path d="m8 3-5 3-2 5 4 2 2-3v11h10V10l2 3 4-2-2-5-5-3a4 4 0 0 1-8 0Z"></path>
            @break
        @case('team')
            <circle cx="12" cy="7" r="3"></circle><path d="M6 21v-3a6 6 0 0 1 12 0v3M5 5a3 3 0 0 0 0 6M19 5a3 3 0 0 1 0 6M2 19v-2a4 4 0 0 1 3-4M22 19v-2a4 4 0 0 0-3-4"></path>
            @break
        @case('truck')
            <path d="M2 4h12v13H2zM14 9h4l4 4v4h-8"></path><circle cx="6" cy="18" r="2"></circle><circle cx="18" cy="18" r="2"></circle>
            @break
        @case('diamond')
            <path d="m3 4-2 5 11 13L23 9l-3-5H3ZM1 9h22M8 4 6 9l6 13 6-13-2-5"></path>
            @break
        @case('artwork')
            <rect x="3" y="3" width="18" height="18" rx="2"></rect><circle cx="8" cy="8" r="2"></circle><path d="m3 17 6-5 4 3 4-5 4 5"></path>
            @break
        @case('settings')
            <path d="m10 2-.7 3-2 .9-2.8-.8-2 3.5 2.1 2.1v2.6l-2.1 2.1 2 3.5 2.8-.8 2 .9.7 3h4l.7-3 2-.9 2.8.8 2-3.5-2.1-2.1v-2.6l2.1-2.1-2-3.5-2.8.8-2-.9-.7-3z"></path><circle cx="12" cy="12" r="3"></circle>
            @break
        @case('printer')
            <path d="M6 8V2h12v6M6 17H3V8h18v9h-3M6 14h12v8H6zM6 11h1M9 17h6M9 19h4"></path>
            @break
        @case('needle')
            <path d="M4 20 16 3a3 3 0 0 1 4 4L4 20ZM16 7l2-2M6 21c9 3 17-3 10-7"></path>
            @break
        @case('layers')
            <path d="m12 2 10 5-10 5L2 7l10-5ZM2 12l10 5 10-5M2 17l10 5 10-5"></path>
            @break
        @case('drop')
            <path d="M12 2C10 6 4 11 4 15a8 8 0 0 0 16 0c0-4-6-9-8-13Z"></path><path d="M8 15a4 4 0 0 0 4 4"></path>
            @break
        @case('search')
            <circle cx="11" cy="11" r="7"></circle><path d="m20 20-4-4"></path>
            @break
        @case('user')
            <circle cx="12" cy="8" r="4"></circle><path d="M4.5 21a7.5 7.5 0 0 1 15 0"></path>
            @break
        @case('bag')
            <path d="M5 8h14l-1 13H6L5 8Z"></path><path d="M9 9V6a3 3 0 0 1 6 0v3"></path>
            @break
        @case('menu')
            <path d="M4 7h16M4 12h16M4 17h16"></path>
            @break
        @case('close')
            <path d="m6 6 12 12M18 6 6 18"></path>
            @break
        @case('home')
            <path d="m3 10 9-7 9 7"></path><path d="M5 9v12h14V9M9 21v-7h6v7"></path>
            @break
        @case('orders')
            <circle cx="12" cy="12" r="9"></circle><path d="M12 7v5l3 2"></path>
            @break
        @case('arrow')
            <path d="M5 12h14M14 7l5 5-5 5"></path>
            @break
        @case('check')
            <path d="m5 12 4 4L19 6"></path>
            @break
        @default
            <circle cx="12" cy="12" r="9"></circle>
    @endswitch
</svg>
