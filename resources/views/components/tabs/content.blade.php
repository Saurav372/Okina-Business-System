@props(['value'])

<div 
    x-show="activeTab === '{{ $value }}'"
    role="tabpanel"
    tabindex="0"
    style="display: none;"
    {{ $attributes->merge(['class' => 'py-4 focus:outline-none focus-visible:ring-2 focus-visible:ring-[color:var(--color-primary-500)] focus-visible:ring-offset-2 rounded-md']) }}
>
    {{ $slot }}
</div>
