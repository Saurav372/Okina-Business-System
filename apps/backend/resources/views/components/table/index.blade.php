<div class="w-full overflow-x-auto custom-scrollbar rounded-[var(--radius-lg)] border border-[color:var(--color-border)] shadow-[var(--shadow-sm)] bg-[color:var(--color-surface)]">
    <table {{ $attributes->class(['w-full text-left text-[length:var(--text-sm)] text-[color:var(--color-text-primary)]']) }}>
        {{ $slot }}
    </table>
</div>
