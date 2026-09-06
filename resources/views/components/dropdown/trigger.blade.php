<span
    data-dropdown-trigger
    style="display: contents"
    @click="toggle()"
    @keydown.down.prevent="open = true"
    @keydown.enter.prevent="toggle()"
    @keydown.space.prevent="toggle()"
    :aria-expanded="open.toString()"
    aria-haspopup="menu"
    {{ $attributes }}
>
    {{ $slot }}
</span>
