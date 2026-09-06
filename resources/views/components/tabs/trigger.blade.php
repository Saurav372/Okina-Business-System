@props(['value', 'disabled' => false])

<button 
    type="button" 
    role="tab" 
    :aria-selected="activeTab === '{{ $value }}'"
    @click="activeTab = '{{ $value }}'"
    :tabindex="activeTab === '{{ $value }}' ? '0' : '-1'"
    {{ $disabled ? 'disabled aria-disabled=true' : '' }}
    {{ $attributes->merge(['class' => 'relative px-4 py-3 text-sm font-medium transition-colors outline-none focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-[color:var(--color-primary-500)] whitespace-nowrap disabled:opacity-50 disabled:cursor-not-allowed']) }}
    :class="{
        'text-[color:var(--color-primary-600)]': activeTab === '{{ $value }}',
        'text-[color:var(--color-text-muted)] hover:text-[color:var(--color-text-primary)] hover:bg-[color:var(--color-surface-secondary)]': activeTab !== '{{ $value }}'
    }"
>
    {{ $slot }}
    
    <span 
        class="absolute bottom-0 inset-x-0 h-0.5 bg-[color:var(--color-primary-600)] transition-transform origin-center duration-200 ease-out"
        :class="activeTab === '{{ $value }}' ? 'scale-x-100 opacity-100' : 'scale-x-0 opacity-0'"
    ></span>
</button>
