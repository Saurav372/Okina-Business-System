<thead {{ $attributes->class([
    'bg-[color:var(--color-surface-secondary)]/50',
    'border-b border-[color:var(--color-border)]',
    'text-xs uppercase tracking-wider text-[color:var(--color-text-muted)] font-semibold'
]) }}>
    {{ $slot }}
</thead>
