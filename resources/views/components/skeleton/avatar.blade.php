@props([
    'size' => null,
])

<x-skeleton 
    variant="circle" 
    rounded="full" 
    :width="$size" 
    :height="$size" 
    {{ $attributes }} 
/>
