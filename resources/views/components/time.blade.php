@props([
    'label' => null,
    'labelStyle' => 'top',
    'hint' => null,
    'error' => null,
    'placeholder' => 'Escolha um horário',
    'min' => null,
    'max' => null,
    'step' => 5,
    'hour12' => false,
    'suggestions' => [],
    'locale' => 'pt-BR',
])

@php
    $name = $attributes->get('name');
    $id = $attributes->get('id') ?? $name ?? $attributes->whereStartsWith('wire:model')->first() ?? 'nui-'.Str::random(8);
    $error ??= $name ? ($errors ?? null)?->first($name) : null;
    $labelStyle = $labelStyle === 'floating' ? 'legend' : $labelStyle;
    $initial = ($name ? old($name, $attributes->get('value')) : $attributes->get('value')) ?: '';
    $initial = $initial ? substr((string) $initial, 0, 5) : '';
    $cell = 'grid place-items-center h-9 shrink-0 rounded-[calc(var(--nui-radius)-6px)] text-sm tabular-nums cursor-pointer select-none outline-none transition focus-visible:outline-2 focus-visible:outline-offset-1 focus-visible:outline-primary';
@endphp

{{--
    Time picker. Value is always H:i in 24h ("14:30"), what Laravel and Livewire expect; the label follows the locale.
    Panel: hour and minute columns (plus AM/PM with hour12), "Agora" and optional suggested times. min/max (H:i) disable
    what is out of range; step sets the minute interval. Works with x-model / wire:model (x-modelable) and plain forms.
--}}
<x-nui::field :label="$labelStyle === 'top' ? $label : null" :$hint :$error :for="$id" :class="$attributes->get('class')">
    <div class="relative"
        x-data="{
            open: false,
            value: @js($initial),
            min: @js($min),
            max: @js($max),
            step: @js(max(1, (int) $step)),
            hour12: @js((bool) $hour12),
            locale: @js($locale),
            pad(number) { return String(number).padStart(2, '0') },
            get hour() { return this.value ? Number(this.value.slice(0, 2)) : null },
            get minute() { return this.value ? Number(this.value.slice(3, 5)) : null },
            get hours() { return this.hour12 ? [12, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11] : [...Array(24).keys()] },
            get minutes() { return [...Array(Math.ceil(60 / this.step)).keys()].map((index) => index * this.step) },
            get period() { return this.hour === null ? null : (this.hour < 12 ? 'AM' : 'PM') },
            toHour(shown, period = this.period ?? 'AM') { return this.hour12 ? (shown % 12) + (period === 'PM' ? 12 : 0) : shown },
            shown(hour) { return this.hour12 ? (hour % 12 || 12) : hour },
            text(hour, minute) { return this.pad(hour) + ':' + this.pad(minute) },
            disabled(text) { return (this.min && text < this.min) || (this.max && text > this.max) },
            hourDisabled(hour) { return this.minutes.every((minute) => this.disabled(this.text(hour, minute))) },
            get display() {
                if (! this.value) return '';
                return new Date(2000, 0, 1, this.hour, this.minute).toLocaleTimeString(this.locale, { hour: '2-digit', minute: '2-digit', hour12: this.hour12 });
            },
            set(hour, minute) {
                if (minute === null) minute = this.minutes.find((option) => ! this.disabled(this.text(hour, option))) ?? 0;
                const text = this.text(hour, minute);
                if (! this.disabled(text)) this.value = text;
            },
            pickHour(shown) { this.set(this.toHour(shown), this.minute) },
            pickMinute(minute) { this.set(this.hour ?? (this.hours.map((shown) => this.toHour(shown)).find((hour) => ! this.hourDisabled(hour)) ?? 0), minute) },
            pickPeriod(period) { this.set(this.toHour(this.shown(this.hour ?? 0), period), this.minute) },
            now() {
                const date = new Date(), rounded = Math.round(date.getMinutes() / this.step) * this.step;
                date.setMinutes(rounded, 0, 0);
                this.set(date.getHours(), date.getMinutes());
            },
            show() {
                this.open = true;
                const panel = this.$refs.panel;
                panel.classList.remove('nui-pop-out'); panel.showPopover(); this.place(); panel.classList.add('nui-pop-in');
                this.$nextTick(() => { this.center(true); (this.$refs.panel.querySelector('[data-column=hour] [aria-selected=true]') ?? this.$refs.panel.querySelector('[data-column=hour] [role=option]:not([aria-disabled=true])'))?.focus({ preventScroll: true }) });
            },
            hide(refocus = true) {
                if (! this.open) return;
                this.open = false;
                const panel = this.$refs.panel;
                panel.classList.replace('nui-pop-in', 'nui-pop-out');
                setTimeout(() => { if (! this.open && panel.isConnected && panel.matches(':popover-open')) panel.hidePopover() }, 130);
                if (refocus) this.$refs.trigger.focus();
            },
            place() {
                const anchor = this.$refs.anchor.getBoundingClientRect(), panel = this.$refs.panel;
                const height = panel.offsetHeight, below = innerHeight - anchor.bottom;
                const up = below < height + 16 && anchor.top > below;
                panel.dataset.side = up ? 'top' : 'bottom';
                panel.style.left = Math.max(8, Math.min(anchor.left, innerWidth - panel.offsetWidth - 8)) + 'px';
                panel.style.top = up ? 'auto' : anchor.bottom + 8 + 'px';
                panel.style.bottom = up ? innerHeight - anchor.top + 8 + 'px' : 'auto';
            },
            center(instant = false) {
                this.$refs.panel.querySelectorAll('[data-column]').forEach((column) => {
                    const selected = column.querySelector('[aria-selected=true]');
                    if (selected) column.scrollTo({ top: selected.offsetTop - column.clientHeight / 2 + selected.offsetHeight / 2, behavior: instant || matchMedia('(prefers-reduced-motion: reduce)').matches ? 'instant' : 'smooth' });
                });
            },
            onKey(event) {
                const option = event.target.closest('[role=option]');
                if (! option) return;
                const column = option.closest('[data-column]'), options = [...column.querySelectorAll('[role=option]:not([aria-disabled=true])')];
                const index = options.indexOf(option), columns = [...this.$refs.panel.querySelectorAll('[data-column]')];
                const moves = { ArrowDown: index + 1, ArrowUp: index - 1, Home: 0, End: options.length - 1, PageDown: index + 5, PageUp: index - 5 };
                if (event.key in moves) {
                    event.preventDefault();
                    const next = options[Math.max(0, Math.min(moves[event.key], options.length - 1))];
                    next?.click(); next?.focus();
                    this.$nextTick(() => this.center());
                } else if (event.key === 'ArrowRight' || event.key === 'ArrowLeft') {
                    event.preventDefault();
                    const target = columns[columns.indexOf(column) + (event.key === 'ArrowRight' ? 1 : -1)];
                    (target?.querySelector('[aria-selected=true]') ?? target?.querySelector('[role=option]:not([aria-disabled=true])'))?.focus();
                } else if (event.key === 'Enter' || event.key === ' ') {
                    event.preventDefault();
                    option.click();
                    if (event.key === 'Enter') this.hide();
                }
            },
        }"
        x-modelable="value"
        {{ $attributes->whereStartsWith(['wire:model', 'x-model']) }}
        x-effect="open && value !== undefined && $nextTick(() => center())"
        x-on:click.outside="open && hide(false)"
        x-on:resize.window="open && place()" x-on:scroll.window.capture="open && ! $refs.panel.contains($event.target) && place()"
        x-on:keydown.escape="if (open) { $event.preventDefault(); $event.stopPropagation(); hide() }">

        @if ($name)
            <input type="hidden" name="{{ $name }}" x-bind:value="value">
        @endif

        <x-nui::box :invalid="(bool) $error" :label="$labelStyle === 'top' ? null : $label" :for="$id" :label-style="$labelStyle" x-ref="anchor">
            <svg aria-hidden="true" class="size-4 shrink-0 text-base-content/55" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><circle cx="8" cy="8" r="6" /><path d="M8 4.75V8l2.25 1.5" /></svg>
            <x-nui::button variant="ghost" size="sm" type="button" id="{{ $id }}" x-ref="trigger" aria-haspopup="dialog" aria-expanded="false" x-bind:aria-expanded="open.toString()"
                :aria-invalid="$error ? 'true' : null" :aria-describedby="$error ? $id.'-error' : ($hint ? $id.'-hint' : null)"
                x-on:click="open ? hide() : show()" x-on:keydown.arrow-down.prevent="show()"
                :disabled="$attributes->get('disabled')"
                class="!justify-start !h-auto !p-0 !border-0 !bg-transparent !shadow-none flex-1 min-w-0 py-2 text-left tabular-nums truncate outline-none disabled:cursor-not-allowed after:absolute after:inset-0 after:content-['']"
                :data-placeholder="$placeholder" x-text="display || $el.dataset.placeholder" x-bind:class="! value && 'text-base-content/45'">{{ $placeholder }}</x-nui::button>
        </x-nui::box>

        {{-- Top-layer popover: renders above dialogs and overflow containers. --}}
        <div popover="manual" x-ref="panel" role="dialog" aria-modal="false" aria-label="Escolher horário" x-on:keydown="onKey($event)"
            class="fixed m-0 inset-auto overflow-visible w-max max-w-[calc(100vw-2rem)] p-2 bg-base-100 text-base-content rounded-nui shadow-nui border-[length:var(--nui-border-width)] border-base-300">
            <div class="flex flex-col gap-3">
                <div class="relative flex gap-1 self-center">
                    {{-- Reading line: the selected row of every column sits on it. --}}
                    <span aria-hidden="true" class="pointer-events-none absolute inset-x-0 top-1/2 -translate-y-1/2 h-9 rounded-[calc(var(--nui-radius)-6px)] bg-base-content/[0.06]"></span>

                    <div data-lenis-prevent data-column="hour" role="listbox" aria-label="Horas" class="nui-time-column relative w-14 h-[9.25rem] [mask-image:linear-gradient(transparent,black_32%,black_68%,transparent)] overflow-y-auto overscroll-contain snap-y py-[3.5rem] flex flex-col gap-0.5 [scrollbar-width:none] [&::-webkit-scrollbar]:hidden">
                        <template x-for="hourOption in hours" x-bind:key="hourOption">
                            <div role="option" x-bind:tabindex="hourOption === shown(hour ?? -1) || (hour === null && hourOption === hours[0]) ? 0 : -1"
                                x-bind:aria-selected="(hour !== null && hourOption === shown(hour)).toString()"
                                x-bind:aria-disabled="hourDisabled(toHour(hourOption)).toString()"
                                x-on:click="hourDisabled(toHour(hourOption)) || pickHour(hourOption)"
                                x-text="pad(hourOption)"
                                x-bind:class="hour !== null && hourOption === shown(hour) ? 'bg-primary text-primary-content font-semibold shadow-nui-control' : (hourDisabled(toHour(hourOption)) ? 'text-base-content/25 cursor-not-allowed' : 'hover:bg-base-content/[0.06]')"
                                class="{{ $cell }} snap-center"></div>
                        </template>
                    </div>

                    <span aria-hidden="true" class="relative self-center text-base font-semibold text-base-content/40">:</span>

                    <div data-lenis-prevent data-column="minute" role="listbox" aria-label="Minutos" class="nui-time-column relative w-14 h-[9.25rem] [mask-image:linear-gradient(transparent,black_32%,black_68%,transparent)] overflow-y-auto overscroll-contain snap-y py-[3.5rem] flex flex-col gap-0.5 [scrollbar-width:none] [&::-webkit-scrollbar]:hidden">
                        <template x-for="option in minutes" x-bind:key="option">
                            <div role="option" x-bind:tabindex="option === (minute ?? minutes[0]) ? 0 : -1"
                                x-bind:aria-selected="(option === minute).toString()"
                                x-bind:aria-disabled="(hour !== null && disabled(text(hour, option))).toString()"
                                x-on:click="(hour !== null && disabled(text(hour, option))) || pickMinute(option)"
                                x-text="pad(option)"
                                x-bind:class="option === minute ? 'bg-primary text-primary-content font-semibold shadow-nui-control' : (hour !== null && disabled(text(hour, option)) ? 'text-base-content/25 cursor-not-allowed' : 'hover:bg-base-content/[0.06]')"
                                class="{{ $cell }} snap-center"></div>
                        </template>
                    </div>

                    @if ($hour12)
                        <div data-lenis-prevent data-column="period" role="listbox" aria-label="Período" class="nui-time-column relative w-14 h-[9.25rem] [mask-image:linear-gradient(transparent,black_32%,black_68%,transparent)] overflow-y-auto overscroll-contain snap-y py-[3.5rem] flex flex-col gap-0.5 [scrollbar-width:none] [&::-webkit-scrollbar]:hidden">
                            @foreach (['AM', 'PM'] as $periodOption)
                                <div role="option" tabindex="-1" x-bind:aria-selected="(period === @js($periodOption)).toString()" x-on:click="pickPeriod(@js($periodOption))"
                                    x-bind:class="period === @js($periodOption) ? 'bg-primary text-primary-content font-semibold shadow-nui-control' : 'hover:bg-base-content/[0.06]'"
                                    class="{{ $cell }} snap-center">{{ $periodOption }}</div>
                            @endforeach
                        </div>
                    @endif
                </div>

                @if (count($suggestions))
                    <div class="flex flex-wrap gap-1 justify-center max-w-[15rem] self-center" role="group" aria-label="Horários sugeridos">
                        @foreach ($suggestions as $suggestion)
                            <button type="button" x-on:click="value = @js($suggestion); hide()" x-bind:disabled="disabled(@js($suggestion))"
                                x-bind:class="value === @js($suggestion) ? 'bg-primary/12 text-primary border-primary/40' : 'border-base-content/15 hover:bg-base-content/[0.05]'"
                                class="h-7 px-2 rounded-full border border-b-2 text-xs font-medium tabular-nums cursor-pointer disabled:opacity-35 disabled:pointer-events-none focus-visible:outline-2 focus-visible:outline-primary">{{ $suggestion }}</button>
                        @endforeach
                    </div>
                @endif

                <div class="flex items-center justify-between gap-2">
                    <x-nui::button variant="ghost" size="sm" x-on:click="now()">Agora</x-nui::button>
                    <x-nui::button size="sm" x-on:click="hide()">Pronto</x-nui::button>
                </div>
            </div>
        </div>
    </div>
</x-nui::field>
