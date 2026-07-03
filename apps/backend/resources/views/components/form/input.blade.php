@props([
    'id',
    'name' => null,
    'label' => null,
    'hint' => null,
    'error' => null,
    'required' => false,
    'disabled' => false,
    'readonly' => false,
    'type' => 'text',
    'value' => null,
    'autocomplete' => null,
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
    <div class="relative flex w-full items-center">
        @if(isset($prefix))
            <div class="absolute left-0 flex items-center pl-[var(--spacing-4)] pointer-events-none text-[color:var(--color-text-muted)]">
                {{ $prefix }}
            </div>
        @endif

        <input
            id="{{ $id }}"
            @if($name) name="{{ $name }}" @endif
            type="{{ $type }}"
            @if(!is_null($value)) value="{{ $value }}" @endif
            @if($required) required @endif
            @if($disabled) disabled @endif
            @if($readonly) readonly @endif
            @if($autocomplete) autocomplete="{{ $autocomplete }}" @endif
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
                isset($prefix) ? 'pl-[var(--spacing-12)]' : '',
                isset($suffix) ? 'pr-[var(--spacing-12)]' : '',
            ]) }}
        />

        @if(isset($suffix))
            <div class="absolute right-0 flex items-center pr-[var(--spacing-4)] pointer-events-none text-[color:var(--color-text-muted)]">
                {{ $suffix }}
            </div>
        @endif
    </div>
</x-form.wrapper>
