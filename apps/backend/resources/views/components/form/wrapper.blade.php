@props([
    'id',
    'label' => null,
    'hint' => null,
    'error' => null,
    'required' => false,
])

<div {{ $attributes->merge(['class' => 'flex flex-col gap-1.5']) }}>
    @if($label)
        <label for="{{ $id }}" class="block text-sm font-medium text-[color:var(--color-text-primary)]">
            {{ $label }}
            @if($required)
                <span class="text-[color:var(--color-danger)]" aria-hidden="true">*</span>
            @endif
        </label>
    @endif

    {{ $slot }}

    @if($hint)
        <p id="{{ $id }}-hint" class="text-sm text-[color:var(--color-text-muted)]">
            {{ $hint }}
        </p>
    @endif

    @if($error)
        <p id="{{ $id }}-error" class="text-sm text-[color:var(--color-danger)] font-medium">
            {{ $error }}
        </p>
    @endif
</div>
