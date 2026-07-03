<tr {{ $attributes->class([
    'hover:bg-[color:var(--color-surface-hover)]',
    'transition-colors duration-[var(--motion-fast)] ease-[var(--motion-ease)]'
]) }}>
    {{ $slot }}
</tr>
