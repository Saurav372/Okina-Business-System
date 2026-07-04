<div class="w-full overflow-x-auto custom-scrollbar rounded-[var(--radius-xl)] border border-[color:var(--color-border)] shadow-[var(--shadow-lg)] bg-white/70 backdrop-blur-md transition-shadow duration-[var(--motion-normal)] hover:shadow-[var(--shadow-xl)]">
    <table {{ $attributes->class(['w-full text-left text-[length:var(--text-sm)] text-[color:var(--color-text-primary)] border-collapse']) }}>
        {{ $slot }}
    </table>
</div>
