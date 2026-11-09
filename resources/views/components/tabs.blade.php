@props([
    'tabs' => [],
    'default' => null,
    'variant' => 'underline',
    'size' => 'md',
    'vertical' => false,
    'full' => false,
    'label' => null,
])

@php
    $id = $attributes->get('id') ?? 'nui-tabs-'.Str::random(8);

    // Accepts ['perfil' => 'Perfil'], ['Perfil', 'Senha'] or [['name' => 'perfil', 'label' => 'Perfil', 'icon' => '<svg…>', 'badge' => 3, 'disabled' => true]].
    $items = collect($tabs)->map(fn ($tab, $key) => is_array($tab)
        ? ['name' => (string) ($tab['name'] ?? $key), 'label' => (string) $tab['label'], 'icon' => $tab['icon'] ?? null, 'badge' => $tab['badge'] ?? null, 'disabled' => (bool) ($tab['disabled'] ?? false)]
        : ['name' => (string) (array_is_list($tabs) ? $tab : $key), 'label' => (string) $tab, 'icon' => null, 'badge' => null, 'disabled' => false]
    )->values();

    $selected = (string) ($default ?? $items->firstWhere('disabled', false)['name'] ?? '');
    $segmented = $variant === 'segmented';
    $key = fn (string $name): string => Str::slug($name) ?: md5($name);

    $listClasses = [
        'nui-tabs-list relative flex shrink-0 [scrollbar-width:none] [&::-webkit-scrollbar]:hidden',
        $vertical ? 'flex-col' : 'overflow-x-auto',
        $segmented
            ? 'gap-1 p-1 bg-base-200 rounded-nui border-[length:var(--nui-border-width)] border-base-300 shadow-nui-inset'.($full || $vertical ? '' : ' w-fit max-w-full')
            : ($vertical ? 'gap-1 pl-2.5 shadow-[inset_1px_0_0_var(--color-base-300)] min-w-40' : 'gap-1 pb-px shadow-[inset_0_-1px_0_var(--color-base-300)]'),
    ];

    $tabClasses = [
        'nui-tab relative z-10 inline-flex items-center gap-2 whitespace-nowrap [font-weight:var(--nui-control-weight)] select-none outline-none transition-colors duration-200',
        'text-base-content/60 hover:text-base-content aria-selected:text-base-content',
        'disabled:opacity-40 disabled:pointer-events-none',
        'focus-visible:outline-2 focus-visible:outline-primary [&_svg]:shrink-0',
        $segmented ? 'justify-center rounded-[calc(var(--nui-radius)-0.25rem)] focus-visible:-outline-offset-2' : 'rounded-[min(var(--nui-radius-field),0.375rem)] focus-visible:-outline-offset-4',
        $full && ! $vertical ? 'flex-1 justify-center' : '',
        $vertical ? 'w-full justify-start text-left'.($segmented ? '' : ' hover:bg-base-content/[0.04] aria-selected:bg-base-content/[0.06]') : '',
        match ($size) {
            'sm' => ($segmented ? 'h-7 px-2.5' : 'h-9 px-2.5').' text-xs [&_svg]:size-3.5',
            'lg' => ($segmented ? 'h-11 px-5' : 'h-12 px-4').' text-base [&_svg]:size-5',
            default => ($segmented ? 'h-9 px-3.5' : 'h-10 px-3').' text-sm [&_svg]:size-4',
        },
    ];

    $indicatorClasses = $segmented
        ? 'rounded-[calc(var(--nui-radius)-0.25rem)] bg-base-100 border border-base-content/10 border-b-[3px] border-b-base-content/20 shadow-nui'
        : 'rounded-full bg-primary '.($vertical ? 'w-0.5 left-0' : 'h-[3px] !top-auto bottom-0');
@endphp

{{--
    Tab list (WAI-ARIA tabs pattern) plus its <x-nui::tab-panel> children. Arrow keys move and select,
    Home/End jump, disabled tabs are skipped. The selected name works with x-model and wire:model (x-modelable).
    Icons are trusted HTML from your code.
--}}
<div
    x-data="{
        selected: @js($selected),
        direction: 1,
        ready: false,
        switched: false,
        init() {
            if (! this.enabled().some((tab) => tab.dataset.name === this.selected)) this.selected = this.enabled()[0]?.dataset.name ?? '';
            this.$nextTick(() => { this.place(); this.$el.offsetWidth; this.ready = true });
            this.$watch('selected', () => this.$nextTick(() => this.place()));
            const observer = new ResizeObserver(() => this.place());
            [this.$refs.list, ...this.tabs()].forEach((element) => observer.observe(element));
            document.fonts?.ready.then(() => this.place());
        },
        tabs() { return [...this.$refs.list.querySelectorAll('[role=tab]')] },
        tabOf(name) { return this.tabs().find((tab) => tab.dataset.name === name) },
        enabled() { return this.tabs().filter((tab) => ! tab.disabled) },
        select(name, focus = false) {
            const tabs = this.tabs(), from = tabs.findIndex((tab) => tab.dataset.name === this.selected), to = tabs.findIndex((tab) => tab.dataset.name === name);
            if (to === -1 || tabs[to].disabled) return;
            if (name === this.selected) return;
            this.direction = to < from ? -1 : 1;
            this.switched = true;
            this.selected = name;
            if (focus) tabs[to].focus();
            tabs[to].scrollIntoView({ block: 'nearest', inline: 'nearest', behavior: matchMedia('(prefers-reduced-motion: reduce)').matches ? 'auto' : 'smooth' });
        },
        move(step) {
            const tabs = this.enabled(), index = tabs.findIndex((tab) => tab.dataset.name === this.selected);
            if (tabs.length) this.select(tabs[(index + step + tabs.length) % tabs.length].dataset.name, true);
        },
        edge(last) { const tabs = this.enabled(); if (tabs.length) this.select(tabs[last ? tabs.length - 1 : 0].dataset.name, true) },
        onKey(event) {
            const vertical = @js((bool) $vertical);
            const keys = { [vertical ? 'ArrowDown' : 'ArrowRight']: () => this.move(1), [vertical ? 'ArrowUp' : 'ArrowLeft']: () => this.move(-1), Home: () => this.edge(false), End: () => this.edge(true) };
            if (keys[event.key]) { event.preventDefault(); keys[event.key](); }
        },
        place() {
            const tab = this.tabs().find((tab) => tab.dataset.name === this.selected), mark = this.$refs.indicator;
            if (! tab) { mark.style.opacity = 0; return; }
            const segmented = @js($segmented), vertical = @js((bool) $vertical);
            mark.style.opacity = 1;
            const inset = vertical && ! segmented ? 8 : 0;
            Object.assign(mark.style, {
                left: segmented || ! vertical ? tab.offsetLeft + 'px' : '',
                width: segmented || ! vertical ? tab.offsetWidth + 'px' : '',
                top: segmented || vertical ? tab.offsetTop + inset + 'px' : '',
                height: segmented || vertical ? tab.offsetHeight - inset * 2 + 'px' : '',
            });
        },
    }"
    x-modelable="selected"
    {{ $attributes->whereStartsWith(['wire:model', 'x-model']) }}
    x-bind:data-ready="ready" x-bind:data-switched="switched" x-bind:data-direction="direction < 0 ? 'back' : 'forward'"
    data-variant="{{ $variant }}" @if ($default === null) data-auto @endif
    {{ $attributes->except(['id', 'class'])->whereDoesntStartWith(['wire:model', 'x-model']) }}
    {{ $attributes->only('class')->class(['nui-tabs min-w-0', $vertical ? 'grid gap-6 sm:grid-cols-[auto_minmax(0,1fr)] items-start' : 'flex flex-col gap-5']) }}
    id="{{ $id }}">

    <div x-ref="list" role="tablist" @if ($label) aria-label="{{ $label }}" @endif @if ($vertical) aria-orientation="vertical" @endif
        x-on:keydown="onKey($event)" @class($listClasses)>
        <span x-ref="indicator" aria-hidden="true" class="nui-tabs-indicator absolute pointer-events-none opacity-0 {{ $indicatorClasses }}"></span>
        @foreach ($items as $item)
            @php
                $isSelected = $item['name'] === $selected;
            @endphp
            <button type="button" role="tab" data-name="{{ $item['name'] }}"
                id="{{ $id }}-tab-{{ $key($item['name']) }}" aria-controls="{{ $id }}-panel-{{ $key($item['name']) }}"
                aria-selected="{{ $isSelected ? 'true' : 'false' }}" tabindex="{{ $isSelected ? '0' : '-1' }}"
                x-bind:aria-selected="selected === @js($item['name'])" x-bind:tabindex="selected === @js($item['name']) ? 0 : -1"
                x-on:click="select(@js($item['name']))"
                @disabled($item['disabled']) @class($tabClasses)>
                @if ($item['icon'])
                    <span aria-hidden="true" class="inline-flex">{!! $item['icon'] !!}</span>
                @endif
                <span>{{ $item['label'] }}</span>
                @if (filled($item['badge']))
                    <span class="inline-grid place-items-center min-w-5 h-5 px-1.5 rounded-full bg-base-content/[0.08] text-[0.6875rem] font-semibold tabular-nums text-base-content/70">{{ $item['badge'] }}</span>
                @endif
            </button>
        @endforeach
    </div>

    <div class="nui-tabs-panels min-w-0">
        {{ $slot }}
    </div>
</div>
