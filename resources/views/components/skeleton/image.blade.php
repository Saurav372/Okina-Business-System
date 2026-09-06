@props([
    'aspect' => 'video',
])

@php
    $aspects = [
        'video' => 'aspect-video',
        'square' => 'aspect-square',
        'portrait' => 'aspect-[3/4]',
        'auto' => 'aspect-auto',
    ];
    $aspectClass = $aspects[$aspect] ?? 'aspect-video';
@endphp

<x-skeleton 
    variant="block" 
    {{ $attributes->class([$aspectClass, 'w-full']) }} 
/>
