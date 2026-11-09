@props([
    'label' => 'Carrossel',
    'perView' => 1,
    'gap' => 16,
    'peek' => false,
    'loop' => false,
    'autoplay' => false,
    'controls' => 'bottom',
    'indicator' => 'dots',
])

@php
    $id = $attributes->get('id') ?? 'nui-carousel-'.Str::random(8);
    $interval = $autoplay === true ? 5000 : (int) $autoplay;
    $extra = $peek ? 0.2 : 0;
    $overlay = $controls === 'overlay';
    $arrowClasses = [
        'nui-carousel-arrow grid place-items-center size-9 shrink-0 rounded-nui cursor-pointer select-none transition',
        'bg-base-100 text-base-content border border-base-content/15 border-b-[3px] border-b-base-content/25 shadow-nui',
        'hover:bg-base-200 active:translate-y-px active:border-b active:mb-0.5',
        'disabled:opacity-35 disabled:pointer-events-none',
        'focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary [&_svg]:size-4',
    ];
@endphp

{{--
    Native scroll-snap track: swipe, trackpad and touch work without JavaScript. Alpine adds arrows, the page
    indicator, arrow-key paging and optional autoplay (pause button; paused while the pointer or focus is on the slides;
    instant instead of sliding for reduced motion).
    The current page works with x-model and wire:model (x-modelable). Slides are <x-nui::carousel-slide>.
--}}
<section role="region" aria-roledescription="carrossel" aria-label="{{ $label }}"
    x-data="{
        page: 0,
        target: 0,
        pages: [0],
        loop: @js((bool) $loop),
        interval: @js($interval),
        playing: false,
        held: false,
        init() {
            this.playing = this.interval > 0;
            this.measure();
            const observer = new ResizeObserver(() => this.measure());
            [this.$refs.track, ...this.slides()].forEach((element) => observer.observe(element));
            let shownAt = Date.now();
            const restart = () => shownAt = Date.now();
            setInterval(() => {
                if (! this.playing || this.held || document.hidden) return restart();
                if (Date.now() - shownAt >= Math.max(this.interval, 1500)) { this.next(true); restart(); }
            }, 200);
            this.$watch('page', (value) => { restart(); if (value !== this.target) this.go(value) });
        },
        slides() { return [...this.$refs.track.children] },
        measure(snap = true) {
            const track = this.$refs.track, max = track.scrollWidth - track.clientWidth;
            const stops = this.slides().map((slide) => Math.round(Math.max(0, Math.min(slide.offsetLeft - track.offsetLeft, max))));
            this.pages = stops.length ? [...new Set(stops)] : [0];
            if (this.pages.length && this.pages[this.pages.length - 1] < max - 2) this.pages.push(Math.round(max));
            if (snap) this.go(this.page, true);
            this.label();
        },
        sync() {
            const left = this.$refs.track.scrollLeft;
            let nearest = 0;
            this.pages.forEach((stop, index) => { if (Math.abs(stop - left) < Math.abs(this.pages[nearest] - left)) nearest = index });
            this.target = nearest;
            this.page = nearest;
        },
        label() {
            const total = this.slides().length;
            this.slides().forEach((slide, index) => slide.setAttribute('aria-label', (index + 1) + ' de ' + total));
        },
        go(index, instant = false) {
            this.measure(false);
            index = Math.max(0, Math.min(Number(index) || 0, this.pages.length - 1));
            this.target = index;
            this.page = index;
            this.$refs.track.scrollTo({ left: this.pages[index], behavior: instant || matchMedia('(prefers-reduced-motion: reduce)').matches ? 'instant' : 'smooth' });
        },
        next(auto = false) {
            this.measure(false);
            this.go(this.page >= this.pages.length - 1 ? (this.loop || auto ? 0 : this.page) : this.page + 1);
        },
        previous() {
            this.measure(false);
            this.go(this.page <= 0 ? (this.loop ? this.pages.length - 1 : 0) : this.page - 1);
        },
        onKey(event) {
            const keys = { ArrowRight: () => this.next(), ArrowLeft: () => this.previous(), Home: () => this.go(0), End: () => this.go(this.pages.length - 1) };
            if (keys[event.key]) { event.preventDefault(); keys[event.key](); }
        },
    }"
    x-modelable="page"
    {{ $attributes->whereStartsWith(['wire:model', 'x-model']) }}
    style="--nui-carousel-gap: {{ (int) $gap }}px; --nui-carousel-base: {{ 1 + $extra }}; --nui-carousel-view: {{ max(1, (float) $perView) + $extra }};"
    {{ $attributes->except(['id', 'class', 'style'])->whereDoesntStartWith(['wire:model', 'x-model']) }}
    {{ $attributes->only('class')->class(['nui-carousel relative flex flex-col gap-4 min-w-0']) }}
    id="{{ $id }}">

    <div class="relative"
        x-on:mouseenter="held = true" x-on:mouseleave="held = $el.contains(document.activeElement)"
        x-on:focusin="held = true" x-on:focusout="held = $el.contains($event.relatedTarget)">
        <p class="sr-only" x-bind:aria-live="playing ? 'off' : 'polite'" x-text="'Slide ' + (page + 1) + ' de ' + pages.length"></p>
        <div x-ref="track" tabindex="0" role="group" aria-label="Slides; use as setas para navegar"
            data-lenis-prevent
            x-on:scroll.passive="clearTimeout($el._settle); $el._settle = setTimeout(() => sync(), 60)"
            x-on:keydown="onKey($event)"
            class="nui-carousel-track flex gap-[var(--nui-carousel-gap)] overflow-x-auto overscroll-x-contain snap-x snap-mandatory [scrollbar-width:none] [&::-webkit-scrollbar]:hidden rounded-nui outline-none focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-primary">
            {{ $slot }}
        </div>

        @if ($overlay)
            <button type="button" aria-label="Slide anterior" aria-controls="{{ $id }}" x-on:click="previous()" x-bind:disabled="! loop && page === 0"
                @class([...$arrowClasses, 'absolute left-3 top-1/2 -translate-y-1/2 z-10'])>
                <svg aria-hidden="true" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="m10 4-4 4 4 4" /></svg>
            </button>
            <button type="button" aria-label="Próximo slide" aria-controls="{{ $id }}" x-on:click="next()" x-bind:disabled="! loop && page === pages.length - 1"
                @class([...$arrowClasses, 'absolute right-3 top-1/2 -translate-y-1/2 z-10'])>
                <svg aria-hidden="true" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="m6 4 4 4-4 4" /></svg>
            </button>
        @endif
    </div>

    <div class="flex items-center gap-3 min-h-9" x-show="pages.length > 1 || {{ $interval > 0 ? 'true' : 'false' }}">
        @if ($interval > 0)
            <button type="button" x-on:click="playing = ! playing" x-bind:aria-label="playing ? 'Pausar rotação automática' : 'Retomar rotação automática'"
                @class($arrowClasses)>
                <svg x-show="playing" aria-hidden="true" viewBox="0 0 16 16" fill="currentColor"><rect x="4" y="3.5" width="2.75" height="9" rx="0.75" /><rect x="9.25" y="3.5" width="2.75" height="9" rx="0.75" /></svg>
                <svg x-show="! playing" x-cloak aria-hidden="true" viewBox="0 0 16 16" fill="currentColor"><path d="M5 3.8v8.4a.7.7 0 0 0 1.06.6l6.8-4.2a.7.7 0 0 0 0-1.2l-6.8-4.2A.7.7 0 0 0 5 3.8Z" /></svg>
            </button>
        @endif

        @if ($indicator === 'counter')
            <p class="text-sm tabular-nums text-base-content/70" aria-hidden="true">
                <span class="font-semibold text-base-content" x-text="page + 1">1</span> / <span x-text="pages.length">1</span>
            </p>
        @elseif ($indicator !== 'none')
            <div class="flex items-center gap-1.5" role="group" aria-label="Escolher slide">
                <template x-for="(stop, index) in pages" x-bind:key="index">
                    <button type="button" x-on:click="go(index)" x-bind:aria-label="'Ir para o slide ' + (index + 1)" x-bind:aria-current="page === index ? 'true' : 'false'"
                        class="nui-carousel-dot group/dot grid place-items-center h-6 cursor-pointer outline-none focus-visible:outline-2 focus-visible:outline-primary rounded-full">
                        <span class="block h-1.5 rounded-full transition-[width,background-color] duration-300 ease-[cubic-bezier(0.16,1,0.3,1)]"
                            x-bind:class="page === index ? 'w-6 bg-primary' : 'w-1.5 bg-base-content/25 group-hover/dot:bg-base-content/45'"></span>
                    </button>
                </template>
            </div>
        @endif

        @unless ($overlay)
            <div class="ml-auto flex items-center gap-2">
                <button type="button" aria-label="Slide anterior" aria-controls="{{ $id }}" x-on:click="previous()" x-bind:disabled="! loop && page === 0" @class($arrowClasses)>
                    <svg aria-hidden="true" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="m10 4-4 4 4 4" /></svg>
                </button>
                <button type="button" aria-label="Próximo slide" aria-controls="{{ $id }}" x-on:click="next()" x-bind:disabled="! loop && page === pages.length - 1" @class($arrowClasses)>
                    <svg aria-hidden="true" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="m6 4 4 4-4 4" /></svg>
                </button>
            </div>
        @endunless
    </div>
</section>
