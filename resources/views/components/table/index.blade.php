<div class="w-full flex flex-col rounded-[var(--radius-xl)] border border-[color:var(--color-border)] shadow-sm bg-[color:var(--color-surface)]">
    @if (isset($toolbar))
        <div class="px-5 py-4 border-b border-[color:var(--color-border)] flex items-center justify-between gap-4 overflow-x-auto custom-scrollbar bg-white rounded-t-[var(--radius-xl)]">
            {{ $toolbar }}
        </div>
    @endif
    
    <div class="w-full overflow-x-auto custom-scrollbar">
        <table {{ $attributes->class(['w-full text-left text-[15px] leading-[24px] text-[color:var(--color-text-primary)] border-collapse']) }}>
            {{ $slot }}
        </table>
    </div>

    @if (isset($footer))
        <div class="border-t border-[color:var(--color-border)] bg-[color:var(--color-surface)] rounded-b-[var(--radius-xl)]">
            {{ $footer }}
        </div>
    @endif
</div>