<tr {{ $attributes->class([
    'hover:bg-[color:var(--color-primary-50)]',
    'transition-colors duration-[var(--motion-fast)] ease-[var(--motion-ease)]'
]) }}>
    {{ $slot }}
</tr>
