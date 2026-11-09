@props([
    'items' => [],
    'label' => 'Navegação principal',
    'navigate' => false,
    'hover' => false,
    'width' => 896,
])

@php
    $id = $attributes->get('id') ?? 'nui-mega-'.Str::random(6);
    $width = min(1280, max(280, (int) $width));
    $items = array_values(array_filter($items, fn (array $item) => filled($item['label'] ?? null) && (filled($item['href'] ?? null) || count($item['groups'] ?? []))));
@endphp

@if (count($items))
<nav {{ $attributes->merge(['id' => $id, 'aria-label' => $label])->class('relative min-w-0 w-full') }}
    x-data="{
        active: null,
        timer: null,
        hover: @js((bool) $hover),
        width: @js($width),
        labels: @js(array_column($items, 'label')),
        init() {
            this.onNavigate = () => this.hide(false);
            document.addEventListener('livewire:navigating', this.onNavigate);
        },
        destroy() { clearTimeout(this.timer); document.removeEventListener('livewire:navigating', this.onNavigate); },
        panel(index) { return this.$refs['panel' + index] },
        trigger(index) { return this.$el.querySelector('[data-menu-trigger=' + JSON.stringify(String(index)) + ']') },
        cancelClose() { clearTimeout(this.timer); this.timer = null },
        scheduleClose() { if (this.hover) { this.cancelClose(); this.timer = setTimeout(() => this.hide(false), 180) } },
        show(index, focus = false) {
            index = Number(index);
            this.cancelClose();
            const panel = this.panel(index);
            if (!panel) return;
            if (this.active === index) { if (focus) this.focusFirst(); return; }
            const previous = this.active === null ? null : this.panel(this.active);
            const from = previous?.matches(':popover-open') ? previous.getBoundingClientRect() : null;
            const direction = from ? Math.sign(index - this.active) : 0;
            if (from) {
                [previous, ...previous.children].forEach((element) => element.getAnimations().forEach((animation) => animation.cancel()));
                previous.hidePopover();
                delete previous.dataset.morphing;
                this.$dispatch('megamenu-closed', { index: this.active });
                panel.dataset.morphing = '';
            } else {
                this.hide(false);
            }
            this.active = index;
            panel.showPopover();
            this.place();
            this.morph(panel, from, direction);
            this.mark(index);
            if (focus) this.$nextTick(() => this.focusFirst());
            this.$dispatch('megamenu-opened', { index, label: this.labels[index] });
        },
        calm() { return matchMedia('(prefers-reduced-motion: reduce)').matches },
        morph(panel, from, direction) {
            [panel, ...panel.children].forEach((element) => element.getAnimations().forEach((animation) => animation.cancel()));
            if (!from || this.calm()) { delete panel.dataset.morphing; return; }
            const to = panel.getBoundingClientRect();
            const ease = 'cubic-bezier(0.16, 1, 0.3, 1)';
            panel.style.overflow = 'hidden';
            panel.animate([
                { transform: `translate(${from.left - to.left}px, ${from.top - to.top}px)`, height: from.height + 'px' },
                { transform: 'none', height: to.height + 'px' },
            ], { duration: 340, easing: ease }).finished.catch(() => {}).finally(() => {
                panel.style.overflow = '';
            });
            [...panel.children].forEach((child, order) => child.animate([
                { opacity: 0, transform: `translateX(${direction * 32}px)` },
                { opacity: 1, transform: 'none' },
            ], { duration: 300, delay: 60 + order * 30, easing: ease, fill: 'backwards' }));
        },
        mark(index) {
            const bar = this.$refs.indicator, trigger = this.trigger(index);
            if (!bar) return;
            if (index === null || !trigger) { bar.style.opacity = '0'; return; }
            const nav = this.$el.getBoundingClientRect(), box = trigger.getBoundingClientRect();
            const wasHidden = bar.style.opacity !== '1';
            if (wasHidden || this.calm()) bar.style.transition = 'none';
            bar.style.transform = `translate(${box.left - nav.left + 10}px, ${box.bottom - nav.top - 2}px)`;
            bar.style.width = Math.max(0, box.width - 20) + 'px';
            bar.offsetWidth;
            bar.style.transition = '';
            bar.style.opacity = '1';
        },
        toggle(index) { index = Number(index); this.active === index ? this.hide() : this.show(index) },
        hide(focus = true) {
            this.cancelClose();
            if (this.active === null) return;
            const index = this.active, panel = this.panel(index);
            if (panel?.matches(':popover-open')) panel.hidePopover();
            if (panel) delete panel.dataset.morphing;
            this.active = null;
            this.mark(null);
            if (focus) this.trigger(index)?.focus({ preventScroll: true });
            this.$dispatch('megamenu-closed', { index });
        },
        place() {
            if (this.active === null) return;
            const rect = this.$el.getBoundingClientRect(), panel = this.panel(this.active);
            if (!panel) return;
            if (rect.bottom < 0 || rect.top > innerHeight) return this.hide(false);
            const width = Math.min(this.width, innerWidth - 24);
            panel.style.width = width + 'px';
            panel.style.maxHeight = (innerHeight - 24) + 'px';
            panel.style.left = Math.max(12, Math.min(rect.left, innerWidth - width - 12)) + 'px';
            const above = rect.top, below = innerHeight - rect.bottom - 8;
            panel.style.top = (panel.offsetHeight > below && above > below
                ? Math.max(12, rect.top - panel.offsetHeight - 8)
                : Math.max(12, Math.min(rect.bottom + 8, innerHeight - panel.offsetHeight - 12))) + 'px';
        },
        focusFirst() { this.panel(this.active)?.querySelector('a[href], button:not(:disabled)')?.focus() },
        step(index, direction) {
            const triggers = [...this.$el.querySelectorAll('[data-menu-trigger]')];
            const position = triggers.findIndex(trigger => Number(trigger.dataset.menuTrigger) === Number(index));
            const target = triggers[(position + direction + triggers.length) % triggers.length];
            const wasOpen = this.active !== null;
            if (wasOpen && target) this.show(Number(target.dataset.menuTrigger));
            target?.focus();
        },
        edge(last) {
            const triggers = [...this.$el.querySelectorAll('[data-menu-trigger]')];
            triggers[last ? triggers.length - 1 : 0]?.focus();
        },
        hoverOpen(index) { if (this.hover && matchMedia('(hover: hover) and (pointer: fine)').matches) this.show(index) }
    }"
    x-on:click.outside="hide(false)" x-on:mouseleave="scheduleClose()"
    x-on:keydown.escape="if (active !== null) { $event.stopPropagation(); $event.preventDefault(); hide(); }"
    x-on:focusout="$nextTick(() => { if (!$el.contains(document.activeElement)) hide(false); })"
    x-on:resize.window="place(); active !== null && mark(active)" x-on:scroll.window.capture="place()">
    {{-- Brass indicator that travels under the open item; the panels morph into each other (see morph()). --}}
    <span x-ref="indicator" aria-hidden="true" class="nui-mega-indicator"></span>
    <ul class="flex min-w-0 flex-wrap items-center gap-1">
        @foreach ($items as $index => $item)
            <li>
                @if (count($item['groups'] ?? []))
                    <x-nui::button variant="ghost" :id="$id.'-trigger-'.$index" :data-menu-trigger="$index"
                        :aria-controls="$id.'-panel-'.$index" aria-expanded="false"
                        x-bind:aria-expanded="(active === Number($el.dataset.menuTrigger)).toString()"
                        x-on:click="toggle($el.dataset.menuTrigger)" x-on:mouseenter="hoverOpen($el.dataset.menuTrigger)"
                        x-on:focus="if (active !== null && active !== Number($el.dataset.menuTrigger) && $el.matches(':focus-visible')) hide(false)"
                        x-on:keydown.arrow-down.prevent="show($el.dataset.menuTrigger, true)"
                        x-on:keydown.arrow-right.prevent="step($el.dataset.menuTrigger, 1)"
                        x-on:keydown.arrow-left.prevent="step($el.dataset.menuTrigger, -1)"
                        x-on:keydown.home.prevent="edge(false)" x-on:keydown.end.prevent="edge(true)"
                        x-bind:class="active === Number($el.dataset.menuTrigger) && '!bg-primary/10 !text-primary'">
                        {{ $item['label'] }}
                        <svg aria-hidden="true" viewBox="0 0 20 20" class="size-3.5 transition-transform" fill="none" stroke="currentColor" stroke-width="1.6"
                            x-bind:class="active === Number($el.parentElement.dataset.menuTrigger) && 'rotate-180'"><path d="m5 8 5 5 5-5" /></svg>
                    </x-nui::button>

                    <x-nui::card :id="$id.'-panel-'.$index" :aria-labelledby="$id.'-trigger-'.$index"
                        padding="none" popover="manual" x-ref="panel{{ $index }}" class="nui-mega-panel"
                        x-on:mouseenter="cancelClose()" x-on:mouseleave="scheduleClose()"
                        x-on:click="const link = $event.target.closest('a[href]'); if (link) $dispatch('megamenu-navigate', { href: link.href })">
                        <div class="flex items-center justify-between gap-4 border-b border-base-content/10 px-5 py-3">
                            <span class="text-sm font-semibold">{{ $item['label'] }}</span>
                            <x-nui::button variant="ghost" class="!size-7 !p-0" x-on:click="hide()" aria-label="Fechar navegação">
                                <svg aria-hidden="true" class="size-4" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.7"><path d="m5 5 10 10M5 15 15 5" /></svg>
                            </x-nui::button>
                        </div>
                        <div @class(['grid gap-6 p-5', 'md:grid-cols-[minmax(0,1fr)_14rem]' => isset($item['featured'])])>
                            <div @class([
                                'grid min-w-0 gap-x-5 gap-y-6',
                                'sm:grid-cols-2' => count($item['groups']) > 1,
                                'lg:grid-cols-3' => count($item['groups']) > 2 && ! isset($item['featured']),
                            ])>
                                @foreach ($item['groups'] as $group)
                                    <div class="min-w-0">
                                        @if (filled($group['label'] ?? null))
                                            <h3 class="mb-2 px-3 text-xs font-semibold text-base-content/60">{{ $group['label'] }}</h3>
                                        @endif
                                        <ul class="flex flex-col gap-1">
                                            @foreach ($group['links'] ?? [] as $link)
                                                @continue(empty($link['href']))
                                                <li>
                                                    <x-nui::button variant="ghost" :href="($link['disabled'] ?? false) ? null : $link['href']"
                                                        :disabled="($link['disabled'] ?? false)" :wire:navigate="$navigate && ! ($link['disabled'] ?? false) ? true : null"
                                                        :aria-current="($link['current'] ?? false) ? 'page' : null"
                                                        class="!h-auto w-full !justify-start !whitespace-normal !px-3 !py-2.5 text-left">
                                                        <span class="flex min-w-0 flex-1 flex-col gap-1">
                                                            <span class="flex flex-wrap items-center gap-2 text-sm font-medium">
                                                                {{ $link['label'] }}
                                                                @if (filled($link['badge'] ?? null))
                                                                    <x-nui::tag size="sm" variant="primary">{{ $link['badge'] }}</x-nui::tag>
                                                                @endif
                                                            </span>
                                                            @if (filled($link['description'] ?? null))
                                                                <span class="text-xs font-normal leading-relaxed text-base-content/60">{{ $link['description'] }}</span>
                                                            @endif
                                                        </span>
                                                    </x-nui::button>
                                                </li>
                                            @endforeach
                                        </ul>
                                    </div>
                                @endforeach
                            </div>
                            @if (isset($item['featured']))
                                <aside class="flex flex-col items-start rounded-nui bg-base-200 p-4">
                                    @if (filled($item['featured']['badge'] ?? null))
                                        <x-nui::tag size="sm" variant="primary" class="mb-3">{{ $item['featured']['badge'] }}</x-nui::tag>
                                    @endif
                                    <p class="text-base font-semibold leading-snug">{{ $item['featured']['title'] ?? '' }}</p>
                                    <p class="mt-2 text-xs leading-relaxed text-base-content/65">{{ $item['featured']['description'] ?? '' }}</p>
                                    @if (filled($item['featured']['href'] ?? null))
                                        <x-nui::button :href="$item['featured']['href']" :wire:navigate="$navigate ? true : null" size="sm" class="mt-5 !h-auto !whitespace-normal !py-2">
                                            {{ $item['featured']['action'] ?? 'Saiba mais' }}
                                        </x-nui::button>
                                    @endif
                                </aside>
                            @endif
                        </div>
                    </x-nui::card>
                @else
                    <x-nui::button variant="ghost" :href="$item['href']" :wire:navigate="$navigate ? true : null"
                        :data-menu-trigger="$index" :aria-current="($item['current'] ?? false) ? 'page' : null"
                        x-on:focus="hide(false)"
                        x-on:keydown.arrow-right.prevent="step($el.dataset.menuTrigger, 1)"
                        x-on:keydown.arrow-left.prevent="step($el.dataset.menuTrigger, -1)"
                        x-on:keydown.home.prevent="edge(false)" x-on:keydown.end.prevent="edge(true)">
                        {{ $item['label'] }}
                    </x-nui::button>
                @endif
            </li>
        @endforeach
    </ul>
</nav>
@endif
