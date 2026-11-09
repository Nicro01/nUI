@props(['label' => 'Dock', 'orientation' => 'horizontal', 'size' => 'md', 'magnify' => 1.3, 'range' => 110, 'navigate' => false, 'as' => 'toolbar'])

@php
    $vertical = $orientation === 'vertical';
    $scale = max(1, (float) $magnify);
    $nav = $as === 'nav';
@endphp

{{--
    Icon sorts that swell toward the pointer, like type pulled from the case.
    as="toolbar" (default): actions, one Tab stop; arrows, Home and End move between items (roving tabindex).
    as="nav": a navigation landmark with a list of links; every link keeps its own Tab stop.
    Labels unroll on hover (mouse) and keyboard focus, stay inside the viewport, and Esc hides them (WCAG 1.4.13).
    magnify: largest scale under the pointer (1 or false turns it off). range: reach of the swell in px.
    Pointer magnification only runs for a mouse and never with prefers-reduced-motion.
    The Tab stop is rendered by the server (active item) and restored after Livewire morphs.
--}}
@if ($nav)
<nav aria-label="{{ $label }}" class="w-fit {{ $attributes->get('class') }}">
@endif
<{{ $nav ? 'ul' : 'div' }} @if ($nav) role="list" @else role="toolbar" aria-label="{{ $label }}" aria-orientation="{{ $vertical ? 'vertical' : 'horizontal' }}" @endif
    data-size="{{ $size }}"
    {{ ($nav ? $attributes->except('class') : $attributes)->class(['nui-dock', 'nui-dock-vertical flex-col' => $vertical, 'flex m-0 list-none']) }}
    x-data="{
        max: {{ $scale }},
        range: {{ (int) $range }},
        vertical: @js($vertical),
        roving: @js(! $nav),
        rest: null,
        point: null,
        frame: 0,
        stop: null,
        all() { return [...$el.querySelectorAll('[data-nui-dock-item]')] },
        items() { return this.all().filter((item) => !item.hasAttribute('aria-disabled')) },
        calm() { return this.max <= 1 || matchMedia('(prefers-reduced-motion: reduce)').matches },
        edges(box) { return this.vertical ? [box.top, box.height] : [box.left, box.width] },
        keepStop() {
            if (!this.roving) return;
            const items = this.items();
            const stops = items.filter((item) => item.tabIndex === 0);
            if (stops.length === 1 && (!this.stop || stops[0] === this.stop)) return;
            const keep = (this.stop && items.includes(this.stop) && this.stop)
                || items.find((item) => item.hasAttribute('aria-current')) || items[0];
            this.all().forEach((item) => { const value = item === keep ? '0' : '-1'; if (item.getAttribute('tabindex') !== value) item.setAttribute('tabindex', value) });
        },
        measure() {
            if (this.rest) return;
            const [start, length] = this.edges($el.getBoundingClientRect());
            this.rest = { length, centers: this.all().map((item) => { const [itemStart, size] = this.edges(item.getBoundingClientRect()); return itemStart - start + size / 2 }) };
        },
        swell(point) {
            if (this.calm()) return;
            this.measure();
            const [start, length] = this.edges($el.getBoundingClientRect());
            const at = (point - start) * this.rest.length / length;
            $el.setAttribute('data-swelling', '');
            this.all().forEach((item, index) => {
                const reach = Math.max(0, 1 - Math.abs(at - this.rest.centers[index]) / this.range);
                item.style.setProperty('--nui-dock-scale', (1 + (this.max - 1) * (1 - Math.cos(reach * Math.PI)) / 2).toFixed(3));
            });
        },
        settle() {
            cancelAnimationFrame(this.frame);
            this.frame = 0;
            this.point = null;
            $el.removeAttribute('data-swelling');
            this.all().forEach((item) => item.style.removeProperty('--nui-dock-scale'));
        },
        pointer(event) {
            if (event.pointerType !== 'mouse') return;
            this.point = this.vertical ? event.clientY : event.clientX;
            if (!this.frame) this.frame = requestAnimationFrame(() => { this.frame = 0; if (this.point !== null) this.swell(this.point) });
        },
        place(item) {
            const tip = item?.querySelector('.nui-dock-tip');
            if (!tip) return;
            tip.style.removeProperty('--nui-dock-tip-shift');
            tip.removeAttribute('data-side');
            requestAnimationFrame(() => {
                const box = tip.getBoundingClientRect(), gap = 8, width = document.documentElement.clientWidth;
                if (this.vertical) {
                    if (box.right > width - gap) tip.setAttribute('data-side', 'start');
                    return;
                }
                const shift = box.left < gap ? gap - box.left : box.right > width - gap ? width - gap - box.right : 0;
                if (shift) tip.style.setProperty('--nui-dock-tip-shift', Math.round(shift) + 'px');
            });
        },
        move(event) {
            if (!this.roving) return;
            const items = this.items();
            const index = items.indexOf(event.target.closest('[data-nui-dock-item]'));
            if (index < 0) return;
            const next = { Home: 0, End: items.length - 1 }[event.key]
                ?? (event.key === (this.vertical ? 'ArrowDown' : 'ArrowRight') ? (index + 1) % items.length
                : event.key === (this.vertical ? 'ArrowUp' : 'ArrowLeft') ? (index - 1 + items.length) % items.length : null);
            if (next === null) return;
            event.preventDefault();
            items[next].focus();
        },
        focused(event) {
            const item = event.target.closest('[data-nui-dock-item]');
            if (!item) return;
            if (this.roving && !item.hasAttribute('aria-disabled')) { this.stop = item; this.keepStop() }
            if (!item.matches(':focus-visible')) return;
            this.place(item);
            this.measure();
            const [start] = this.edges($el.getBoundingClientRect());
            this.swell(start + this.rest.centers[this.all().indexOf(item)]);
        },
    }"
    x-init="keepStop(); $nextTick(() => measure()); new MutationObserver((changes) => { if (changes.some((change) => change.type === 'childList')) rest = null; keepStop() }).observe($el, { subtree: true, childList: true, attributes: true, attributeFilter: ['tabindex', 'aria-current', 'aria-disabled'] })"
    x-on:pointerenter="$el.removeAttribute('data-quiet')" x-on:pointermove="pointer($event)" x-on:pointerleave="settle()"
    x-on:pointerover="$event.pointerType === 'mouse' && place($event.target.closest('[data-nui-dock-item]'))"
    x-on:keydown="move($event)" x-on:keydown.escape="$el.setAttribute('data-quiet', '')" x-on:focusin="focused($event)"
    x-on:focusout="$el.contains($event.relatedTarget) || (settle(), $el.removeAttribute('data-quiet'))"
    x-on:resize.window="settle(); rest = null">
    {{ $slot }}
</{{ $nav ? 'ul' : 'div' }}>
@if ($nav)
</nav>
@endif
