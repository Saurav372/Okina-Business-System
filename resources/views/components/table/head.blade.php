<thead {{ $attributes->class([
    'bg-[color:var(--color-surface-secondary)]/50',
    'border-b border-[color:var(--color-border)]',
    'text-[12px] uppercase tracking-[0.03em] text-slate-500 font-semibold'
]) }}>
    {{ $slot }}
</thead>
