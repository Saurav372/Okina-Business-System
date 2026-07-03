@props([
    'placeholder' => 'Search',
])

<x-form.input type="search" :placeholder="$placeholder" {{ $attributes }}>
    <x-slot:prefix>
        <x-icons.search />
    </x-slot:prefix>
</x-form.input>
