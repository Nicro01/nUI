@props([
    'label' => null,
    'labelStyle' => 'top',
    'hint' => null,
    'error' => null,
    'placeholder' => 'Escolha uma data',
    'min' => null,
    'max' => null,
    'locale' => 'pt-BR',
])

@php
    $name = $attributes->get('name');
    $id = $attributes->get('id') ?? $name ?? $attributes->whereStartsWith('wire:model')->first() ?? 'nui-'.Str::random(8);
    $error ??= $name ? ($errors ?? null)?->first($name) : null;
    $labelStyle = $labelStyle === 'floating' ? 'legend' : $labelStyle; // floating needs :placeholder-shown, which buttons lack
    $initial = ($name ? old($name, $attributes->get('value')) : $attributes->get('value')) ?: '';
    $navButton = 'grid place-items-center size-8 rounded-[calc(var(--nui-radius)-6px)] text-base-content/70 hover:text-base-content hover:bg-base-content/[0.06] focus-visible:outline-2 focus-visible:outline-primary';
@endphp

{{-- Calendar popover. Value is always Y-m-d (what Laravel and Livewire expect); the label follows the locale. --}}
<x-nui::field :label="$labelStyle === 'top' ? $label : null" :$hint :$error :for="$id" :class="$attributes->get('class')">
    <div class="relative"
        x-data="{
            open: false,
            value: @js($initial),
            min: @js($min),
            max: @js($max),
            locale: @js($locale),
            view: null,
            focused: null,
            init() { this.view = this.monthOf(this.value || this.format(new Date())) },
            parse(text) { const [year, month, day] = text.split('-').map(Number); return new Date(year, month - 1, day) },
            format(date) { return [date.getFullYear(), String(date.getMonth() + 1).padStart(2, '0'), String(date.getDate()).padStart(2, '0')].join('-') },
            monthOf(text) { const date = this.parse(text); return new Date(date.getFullYear(), date.getMonth(), 1) },
            disabled(text) { return (this.min && text < this.min) || (this.max && text > this.max) },
            get display() { return this.value ? this.parse(this.value).toLocaleDateString(this.locale, { day: '2-digit', month: 'long', year: 'numeric' }) : '' },
            get title() { const month = this.view.toLocaleDateString(this.locale, { month: 'long' }); return month + ' ' + this.view.getFullYear() },
            get weekdays() { return [...Array(7)].map((_, day) => new Date(2024, 0, 7 + day).toLocaleDateString(this.locale, { weekday: 'narrow' })) },
            get days() {
                const start = new Date(this.view); start.setDate(1 - this.view.getDay());
                const today = this.format(new Date());
                return [...Array(42)].map((_, offset) => {
                    const date = new Date(start); date.setDate(start.getDate() + offset);
                    const text = this.format(date);
                    return { text, day: date.getDate(), outside: date.getMonth() !== this.view.getMonth(), today: text === today, label: date.toLocaleDateString(this.locale, { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' }) };
                });
            },
            shift(months) { this.view = new Date(this.view.getFullYear(), this.view.getMonth() + months, 1) },
            show() {
                this.open = true;
                this.openPanel();
                this.focused = this.value || this.format(new Date());
                this.view = this.monthOf(this.focused);
                this.focusDay();
            },
            hide(refocus = true) { if (! this.open) return; this.open = false; this.closePanel(); if (refocus) this.$refs.trigger.focus() },
            place() {
                const anchor = this.$refs.anchor.getBoundingClientRect(), panel = this.$refs.panel;
                
                const height = panel.offsetHeight, below = innerHeight - anchor.bottom;
                const up = below < height + 16 && anchor.top > below;
                panel.dataset.side = up ? 'top' : 'bottom';
                panel.style.left = Math.max(8, Math.min(anchor.left, innerWidth - panel.offsetWidth - 8)) + 'px';
                panel.style.top = up ? 'auto' : anchor.bottom + 8 + 'px';
                panel.style.bottom = up ? innerHeight - anchor.top + 8 + 'px' : 'auto';
            },
            openPanel() { const panel = this.$refs.panel; panel.classList.remove('nui-pop-out'); panel.showPopover(); this.place(); panel.classList.add('nui-pop-in'); },
            closePanel() { const panel = this.$refs.panel; panel.classList.replace('nui-pop-in', 'nui-pop-out'); setTimeout(() => { if (!this.open && panel.isConnected && panel.matches(':popover-open')) panel.hidePopover(); }, 130); },
            pick(text) { if (this.disabled(text)) return; this.value = text; this.hide() },
            focusDay() { this.$nextTick(() => this.$refs.grid?.querySelector(`[data-date='${this.focused}']`)?.focus()) },
            onKey(event) {
                const date = this.parse(this.focused);
                const moves = { ArrowLeft: -1, ArrowRight: 1, ArrowUp: -7, ArrowDown: 7 };
                if (moves[event.key]) date.setDate(date.getDate() + moves[event.key]);
                else if (event.key === 'PageUp') date.setMonth(date.getMonth() - 1);
                else if (event.key === 'PageDown') date.setMonth(date.getMonth() + 1);
                else if (event.key === 'Home') date.setDate(date.getDate() - date.getDay());
                else if (event.key === 'End') date.setDate(date.getDate() + 6 - date.getDay());
                else return;
                event.preventDefault();
                this.focused = this.format(date);
                this.view = this.monthOf(this.focused);
                this.focusDay();
            },
        }"
        x-modelable="value"
        {{ $attributes->whereStartsWith(['wire:model', 'x-model']) }}
        x-on:click.outside="open && hide(false)"
        x-on:resize.window="open && place()" x-on:scroll.window.capture="open && place()"
        x-on:keydown.escape="if (open) { $event.preventDefault(); $event.stopPropagation(); hide() }">

        @if ($name)
            <input type="hidden" name="{{ $name }}" x-bind:value="value">
        @endif

        <x-nui::box :invalid="(bool) $error" :label="$labelStyle === 'top' ? null : $label" :for="$id" :label-style="$labelStyle" x-ref="anchor">
            <svg aria-hidden="true" class="size-4 shrink-0 text-base-content/55" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="3" width="12" height="11" rx="2" /><path d="M2 6.5h12M5.5 1.5v3M10.5 1.5v3" /></svg>
            <x-nui::button variant="ghost" size="sm" type="button" id="{{ $id }}" x-ref="trigger" aria-haspopup="dialog" aria-expanded="false" x-bind:aria-expanded="open.toString()"
                :aria-invalid="$error ? 'true' : null" :aria-describedby="$error ? $id.'-error' : ($hint ? $id.'-hint' : null)"
                x-on:click="open ? hide() : show()" x-on:keydown.arrow-down.prevent="show()"
                :disabled="$attributes->get('disabled')"
                class="!justify-start !h-auto !p-0 !border-0 !bg-transparent !shadow-none flex-1 min-w-0 py-2 text-left truncate outline-none disabled:cursor-not-allowed after:absolute after:inset-0 after:content-['']"
                :data-placeholder="$placeholder" x-text="display || $el.dataset.placeholder" x-bind:class="! value && 'text-base-content/45'">{{ $placeholder }}</x-nui::button>
        </x-nui::box>

        {{-- Top-layer popover: renders above dialogs and overflow containers. --}}
        <div popover="manual" x-ref="panel" role="dialog" aria-modal="false" aria-label="Escolher data"
            class="fixed m-0 inset-auto overflow-visible w-[19rem] max-w-[calc(100vw-2rem)] origin-top-left p-3 bg-base-100 text-base-content rounded-nui shadow-nui border-[length:var(--nui-border-width)] border-base-300">
            <div class="flex items-center justify-between mb-2">
                <x-nui::button variant="ghost" size="sm" type="button" class="!p-0 {{ $navButton }}" x-on:click="shift(-1)" aria-label="Mês anterior">
                    <svg aria-hidden="true" class="size-4" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="m10 4-4 4 4 4" /></svg>
                </x-nui::button>
                <p class="text-sm font-semibold first-letter:uppercase" x-text="title" aria-live="polite"></p>
                <x-nui::button variant="ghost" size="sm" type="button" class="!p-0 {{ $navButton }}" x-on:click="shift(1)" aria-label="Próximo mês">
                    <svg aria-hidden="true" class="size-4" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="m6 4 4 4-4 4" /></svg>
                </x-nui::button>
            </div>
            <div class="grid grid-cols-7 mb-1" aria-hidden="true">
                <template x-for="(weekday, index) in weekdays" x-bind:key="index">
                    <span class="grid place-items-center h-8 text-xs font-medium uppercase text-base-content/50" x-text="weekday"></span>
                </template>
            </div>
            <div class="grid grid-cols-7 gap-0.5" x-ref="grid" x-on:keydown="onKey($event)">
                <template x-for="day in days" x-bind:key="day.text">
                    <x-nui::button variant="ghost" size="sm" type="button" x-bind:data-date="day.text" x-text="day.day"
                        x-bind:tabindex="day.text === focused ? 0 : -1"
                        x-bind:aria-label="day.label"
                        x-bind:aria-pressed="(day.text === value).toString()"
                        x-bind:aria-current="day.today ? 'date' : null"
                        x-bind:disabled="disabled(day.text)"
                        x-on:click="pick(day.text)"
                        x-on:focus="focused = day.text"
                        x-bind:class="{
                            '!bg-primary !text-primary-content font-semibold shadow-nui-control': day.text === value,
                            'text-base-content/35': day.outside && day.text !== value,
                            'ring-1 ring-inset ring-primary/50': day.today && day.text !== value,
                            'hover:bg-base-content/[0.06]': day.text !== value,
                        }"
                        class="grid place-items-center h-9 rounded-[calc(var(--nui-radius)-6px)] text-sm tabular-nums transition outline-none focus:outline-2 focus:outline-offset-1 focus:outline-primary disabled:opacity-30 disabled:pointer-events-none"></x-nui::button>
                </template>
            </div>
            <div class="flex items-center justify-between mt-4 pt-2 text-sm">
                <x-nui::button variant="ghost" size="sm" type="button" class="px-2 py-1 rounded-md font-medium text-base-content/65 hover:text-base-content focus-visible:outline-2 focus-visible:outline-primary" x-on:click="value = ''; hide()">Limpar</x-nui::button>
                <x-nui::button variant="ghost" size="sm" type="button" class="px-2 py-1 rounded-md font-semibold text-primary hover:bg-primary/10 focus-visible:outline-2 focus-visible:outline-primary" x-on:click="pick(format(new Date()))">Hoje</x-nui::button>
            </div>
        </div>
    </div>
</x-nui::field>




