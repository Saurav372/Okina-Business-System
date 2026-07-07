@props([
    'rows' => 3,
])

@php
    $widths = ['w-full', 'w-[94%]', 'w-[88%]', 'w-[96%]', 'w-[82%]', 'w-[74%]', 'w-[68%]'];
@endphp

<div {{ $attributes->except('rows')->class(['space-y-2.5 w-full']) }} aria-hidden="true" role="presentation">
    @for ($i = 0; $i < $rows; $i++)
        @php
            $widthClass = ($i === $rows - 1 && $rows > 1) ? 'w-[62%]' : $widths[$i % count($widths)];
        @endphp
        <x-skeleton variant="line" class="{{ $widthClass }}" />
    @endfor
</div>
