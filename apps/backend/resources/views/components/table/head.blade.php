<thead {{ $attributes->class([
    'bg-[color:var(--color-primary-50)]',
    'border-b border-[color:var(--color-border)]',
    'text-xs uppercase tracking-wider text-[color:var(--color-primary-800)] font-semibold'
]) }}>
    {{ $slot }}
</thead>
