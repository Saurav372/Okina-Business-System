@props([
    'id',
    'name' => null,
    'label' => null,
    'hint' => null,
    'error' => null,
    'required' => false,
    'disabled' => false,
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
    <input
        id="{{ $id }}"
        @if($name) name="{{ $name }}" @endif
        type="file"
        @if($required) required @endif
        @if($disabled) disabled @endif
        @if($ariaDescribedByStr) aria-describedby="{{ $ariaDescribedByStr }}" @endif
        @if($error) aria-invalid="true" @endif
        {{ $attributes->class([
            'block w-full overflow-hidden text-ellipsis',
            'rounded-[var(--radius-md)]',
            'border',
            $error ? 'border-[color:var(--color-danger)] focus:ring-[color:var(--color-danger)]' : 'border-[color:var(--color-border)] focus:ring-[color:var(--focus-ring-color)]',
            
            // Adjust input padding so the button sits nicely inside with breathing room
            'p-1.5',
            
            'text-[length:var(--text-body)] text-[color:var(--color-text-muted)] bg-[color:var(--color-surface)]',
            'shadow-[var(--shadow-sm)]',
            'transition-colors duration-[var(--motion-fast)] ease-[var(--motion-ease)]',
            'focus:outline-none focus:ring-[length:var(--focus-ring-width)] focus:ring-offset-[length:var(--focus-ring-offset)] focus:border-transparent',
            'disabled:opacity-50 disabled:bg-[color:var(--color-surface-secondary)] disabled:cursor-not-allowed',
            
            // File selector button specific styling
            'file:mr-[var(--spacing-3)]',
            'file:py-1.5 file:px-3',
            'file:rounded-[var(--radius-sm)] file:border-0',
            'file:text-[length:var(--text-body)] file:font-medium',
            'file:bg-[color:var(--color-surface-secondary)] file:text-[color:var(--color-text-primary)]',
            'hover:file:bg-[color:var(--color-surface-hover)] hover:file:cursor-pointer transition-colors',
        ]) }}
    />
</x-form.wrapper>
