@props([
    'id',
    'name' => null,
    'label' => null,
    'hint' => null,
    'error' => null,
    'required' => false,
    'disabled' => false,
    'value' => null,
    'options' => [],
    'placeholder' => null,
])

@php
    $ariaDescribedBy = [];
    if ($hint) {
        $ariaDescribedBy[] = "{$id}-hint";
    }
    if ($error) {
        $ariaDescribedBy[] = "{$id}-error";
    }
    $ariaDescribedByStr = !empty($ariaDescribedBy) ? implode(' ', $ariaDescribedBy) : null;
@endphp

<x-form.wrapper
    :id="$id"
    :label="$label"
    :hint="$hint"
    :error="$error"
    :required="$required"
>
    <select
        id="{{ $id }}"
        @if($name) name="{{ $name }}" @endif
        @if($required) required @endif
        @if($disabled) disabled @endif
        @if($ariaDescribedByStr) aria-describedby="{{ $ariaDescribedByStr }}" @endif
        @if($error) aria-invalid="true" @endif
        {{ $attributes->class([
            'block w-full',
            'rounded-[var(--radius-md)]',
            'border',
            $error ? 'border-[color:var(--color-danger)] focus:ring-[color:var(--color-danger)]' : 'border-[color:var(--color-border)] focus:ring-[color:var(--focus-ring-color)]',
            'px-[var(--spacing-4)] py-[var(--spacing-2)]',
            'text-[length:var(--text-body)] text-[color:var(--color-text-primary)] bg-[color:var(--color-surface)]',
            'shadow-[var(--shadow-sm)]',
            'transition-colors duration-[var(--motion-fast)] ease-[var(--motion-ease)]',
            'focus:outline-none focus:ring-[length:var(--focus-ring-width)] focus:ring-offset-[length:var(--focus-ring-offset)] focus:border-transparent',
            'disabled:opacity-50 disabled:bg-[color:var(--color-surface-secondary)] disabled:cursor-not-allowed',
        ]) }}
    >
        @if($placeholder && (!$required || is_null($value) || $value === ''))
            <option value="">{{ $placeholder }}</option>
        @endif

        @foreach($options as $option)
            <option 
                value="{{ data_get($option, 'value', '') }}" 
                @if((string) $value === (string) data_get($option, 'value', '')) selected @endif
            >
                {{ data_get($option, 'label', '') }}
            </option>
        @endforeach
    </select>
</x-form.wrapper>
