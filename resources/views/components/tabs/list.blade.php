<div 
    x-data="{
        focusTab(direction) {
            let tabs = Array.from($el.querySelectorAll('[role=\'tab\']:not([disabled])'));
            let index = tabs.indexOf(document.activeElement);
            if (index !== -1) {
                let nextIndex = (index + direction + tabs.length) % tabs.length;
                tabs[nextIndex].focus();
            }
        }
    }"
    @keydown.right.prevent="focusTab(1)"
    @keydown.left.prevent="focusTab(-1)"
    @keydown.home.prevent="let tabs = Array.from($el.querySelectorAll('[role=\'tab\']:not([disabled])')); if(tabs.length) tabs[0].focus();"
    @keydown.end.prevent="let tabs = Array.from($el.querySelectorAll('[role=\'tab\']:not([disabled])')); if(tabs.length) tabs[tabs.length - 1].focus();"
    {{ $attributes->merge(['class' => 'flex border-b border-[color:var(--color-border)] overflow-x-auto hide-scrollbar', 'role' => 'tablist', 'aria-orientation' => 'horizontal']) }}
>
    {{ $slot }}
</div>
