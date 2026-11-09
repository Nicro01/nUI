@props(['variant' => 'dropdown', 'label' => 'Menu', 'align' => 'start', 'size' => 'md', 'navigate' => false, 'disabled' => false])

@php
    $inline = $variant === 'inline';
    $id = $attributes->get('id') ?? 'nui-menu-'.Str::random(8);
@endphp

<nav {{ $attributes->merge(['id' => $id, 'aria-label' => $label])->class($inline ? 'nui-menu w-full min-w-0' : 'nui-menu inline-flex min-w-0') }}
    x-data="{
        open: false, search: '', searchedAt: 0,
        inline: @js($inline), align: @js($align),
        init() {
            this.onNavigate = () => this.hide(false);
            document.addEventListener('livewire:navigating', this.onNavigate);
        },
        destroy() { document.removeEventListener('livewire:navigating', this.onNavigate); },
        entries(level) {
            return [...level.querySelectorAll('[data-nui-menu-item]')].filter(el =>
                el.closest('[data-nui-menu-level]') === level && !el.disabled &&
                el.getAttribute('aria-disabled') !== 'true' && el.getClientRects().length);
        },
        focusEdge(level, last = false) {
            const items = this.entries(level);
            items[last ? items.length - 1 : 0]?.focus();
        },
        show(last = false) {
            if (this.inline || this.$refs.trigger.disabled) return;
            const panel = this.$refs.panel;
            panel.showPopover(); this.open = true;
            this.place(); {{-- position before the first paint, or it flashes at its static spot beside the trigger --}}
            panel.classList.remove('nui-pop-in'); void panel.offsetWidth; panel.classList.add('nui-pop-in');
            this.$nextTick(() => this.focusEdge(panel, last));
        },
        hide(restore = true) {
            if (this.inline || !this.open) return;
            this.$refs.panel.hidePopover(); this.open = false;
            if (restore) this.$refs.trigger.focus({ preventScroll: true });
        },
        place() {
            if (this.inline || !this.open) return;
            const panel = this.$refs.panel, rect = this.$refs.trigger.getBoundingClientRect();
            if (rect.bottom < 0 || rect.top > innerHeight) return this.hide(false);
            const below = innerHeight - rect.bottom - 8, above = rect.top - 8;
            const up = panel.scrollHeight > below && above > below;
            panel.dataset.side = up ? 'top' : 'bottom';
            panel.style.maxHeight = Math.max(48, Math.min(innerHeight - 24, up ? above : below)) + 'px';
            const right = getComputedStyle(this.$el).direction === 'rtl';
            const end = (this.align === 'end') !== right;
            const left = end ? rect.right - panel.offsetWidth : rect.left;
            panel.style.left = Math.max(12, Math.min(left, innerWidth - panel.offsetWidth - 12)) + 'px';
            panel.style.top = Math.max(12, up ? rect.top - panel.offsetHeight - 8 : rect.bottom + 8) + 'px';
        },
        key(event) {
            const item = event.target.closest('[data-nui-menu-item]');
            if (!item) return;
            const level = item.closest('[data-nui-menu-level]'), items = this.entries(level);
            const index = items.indexOf(item), key = event.key;
            if (['ArrowDown', 'ArrowUp', 'Home', 'End'].includes(key)) {
                event.preventDefault(); event.stopPropagation();
                const next = key === 'Home' ? 0 : key === 'End' ? items.length - 1 : (index + (key === 'ArrowDown' ? 1 : -1) + items.length) % items.length;
                items[next]?.focus();
            } else if (key === 'Escape' && !this.inline) {
                event.preventDefault(); event.stopPropagation(); this.hide();
            } else if (key === 'Tab' && !this.inline) {
                setTimeout(() => this.hide(false), 0);
            } else if (key === ' ' && item.tagName === 'A') {
                event.preventDefault(); item.click();
            } else if (key.length === 1 && !event.ctrlKey && !event.metaKey && !event.altKey && key !== ' ') {
                this.search = Date.now() - this.searchedAt > 600 ? key : this.search + key;
                this.searchedAt = Date.now();
                const text = this.search.toLocaleLowerCase();
                const query = [...text].every(char => char === text[0]) ? text[0] : text;
                const ordered = [...items.slice(index + 1), ...items.slice(0, index + 1)];
                ordered.find(el => (el.dataset.label || el.textContent).trim().toLocaleLowerCase().startsWith(query))?.focus();
                event.preventDefault();
            }
        }
    }"
    x-on:keydown="key($event)"
    x-on:focusout="$nextTick(() => { if (!$el.contains(document.activeElement)) hide(false); })"
    x-on:resize.window="place()" x-on:scroll.window.capture="place()">
    @unless ($inline)
        <x-nui::button :$disabled :$size variant="secondary" x-ref="trigger" :id="$id.'-trigger'"
            aria-haspopup="menu" :aria-controls="$id.'-panel'" x-bind:aria-expanded="open.toString()"
            x-on:click="open ? hide() : show()"
            x-on:keydown.arrow-down.prevent="show()" x-on:keydown.arrow-up.prevent="show(true)">
            {{ $trigger ?? $label }}
            <svg aria-hidden="true" class="size-4" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.7"><path d="m6 8 4 4 4-4" /></svg>
        </x-nui::button>
    @endunless
    <x-nui::card padding="none" :id="$id.'-panel'" x-ref="panel" data-nui-menu-level
        :role="$inline ? null : 'menu'" :aria-label="$inline ? null : $label"
        :popover="$inline ? null : 'auto'"
        :class="$inline ? 'nui-menu-panel nui-menu-inline' : 'nui-menu-panel nui-menu-popup'"
        x-on:toggle="if (!inline) { open = $event.newState === 'open'; if (open) $nextTick(() => place()); }"
        x-on:click="if (!inline && $event.target.closest('[data-nui-menu-action]')) hide()"
        x-on:nui-menu-resize="$nextTick(() => place())">
        <div role="none" class="p-1.5">{{ $slot }}</div>
    </x-nui::card>
</nav>
