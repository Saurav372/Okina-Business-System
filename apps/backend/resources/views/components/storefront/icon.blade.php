@props(['name', 'label' => null])

<svg {{ $attributes->merge(['class' => 'sf-icon']) }} viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" @if($label) role="img" aria-label="{{ $label }}" @else aria-hidden="true" @endif>
    @switch($name)
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
