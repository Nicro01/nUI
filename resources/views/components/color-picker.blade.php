@props([
    'label' => 'Cor',
    'hint' => null,
    'error' => null,
    'value' => '#000000',
    'mode' => 'input',
    'readonly' => false,
    'presets' => ['#1f2937', '#a85535', '#d4ad54', '#3d8060', '#397a9e', '#7c5ca5', '#ffffff', '#000000'],
])

@php
    $name = $attributes->get('name');
    $id = $attributes->get('id') ?? $name ?? 'nui-color-'.Str::random(6);
    $error ??= $name ? ($errors ?? null)?->first($name) : null;
    $initial = strtolower((string) ($name ? old($name, $value) : $value));
    if (preg_match('/^#?([a-f0-9]{3,4})$/', $initial, $match)) {
        $initial = '#'.implode('', array_map(fn (string $digit) => $digit.$digit, str_split($match[1])));
    }
    if (! preg_match('/^#(?:[a-f0-9]{6}|[a-f0-9]{8})$/', $initial)) {
        $initial = '#000000';
    }
    $disabled = (bool) $attributes->get('disabled');
    $described = $error ? $id.'-error' : ($hint ? $id.'-hint' : null);
    $swatches = array_values(array_filter($presets, fn ($color) => is_string($color) && preg_match('/^#[a-fA-F0-9]{6}$/', $color)));
@endphp

<x-nui::field :$label :$hint :$error :for="$id" :class="$attributes->get('class')">
    <div @class(['relative min-w-0', 'inline-flex self-start' => $mode === 'well'])
        x-data="{
            value: @js($initial),
            draft: @js($initial),
            open: false,
            invalid: false,
            h: 0, s: 0, v: 0, a: 100,
            init() {
                this.read(this.value);
                this.$watch('[h, s, v, a]', () => this.write());
                this.$watch('value', color => {
                    const normalized = this.normalize(color);
                    if (normalized && normalized !== this.hex()) this.read(normalized);
                });
            },
            normalize(color) {
                const text = String(color ?? '').trim().toLowerCase();
                if (/^#?[0-9a-f]{3,4}$/.test(text)) return '#' + text.replace('#', '').split('').map(c => c + c).join('');
                if (/^#?(?:[0-9a-f]{6}|[0-9a-f]{8})$/.test(text)) return '#' + text.replace('#', '');
                return null;
            },
            read(color) {
                color = this.normalize(color);
                if (!color) return;
                const channels = [1, 3, 5].map(i => parseInt(color.slice(i, i + 2), 16) / 255);
                const [r, g, b] = channels, max = Math.max(...channels), min = Math.min(...channels), delta = max - min;
                let hue = this.h;
                if (delta) {
                    hue = max === r ? ((g - b) / delta) % 6 : max === g ? (b - r) / delta + 2 : (r - g) / delta + 4;
                    hue = (hue * 60 + 360) % 360;
                }
                this.h = hue;
                this.s = max ? delta / max * 100 : 0;
                this.v = max * 100;
                this.a = color.length === 9 ? parseInt(color.slice(7, 9), 16) / 255 * 100 : 100;
                this.value = color;
                this.draft = color;
                this.invalid = false;
            },
            hex() {
                const s = this.s / 100, v = this.v / 100;
                const channel = n => {
                    const k = (n + this.h / 60) % 6;
                    return Math.round((v - v * s * Math.max(0, Math.min(k, 4 - k, 1))) * 255).toString(16).padStart(2, '0');
                };
                return '#' + channel(5) + channel(3) + channel(1) + (this.a < 100 ? Math.round(this.a / 100 * 255).toString(16).padStart(2, '0') : '');
            },
            write() { this.value = this.hex(); this.draft = this.value; this.invalid = false; },
            change(channel, amount) {
                this[channel] = Math.max(0, Math.min(channel === 'h' ? 360 : 100, Number(amount) || 0));
                this.value = this.hex();
                this.draft = this.value;
                this.invalid = false;
            },
            point(event) {
                const rect = this.$refs.surface.getBoundingClientRect();
                this.s = Math.max(0, Math.min(100, (event.clientX - rect.left) / rect.width * 100));
                this.v = Math.max(0, Math.min(100, 100 - (event.clientY - rect.top) / rect.height * 100));
                this.value = this.hex();
                this.draft = this.value;
                this.invalid = false;
            },
            apply() {
                const normalized = this.normalize(this.draft);
                this.invalid = !normalized;
                if (normalized) this.read(normalized);
            },
            show() {
                this.open = true;
                this.$refs.panel.showPopover();
                this.$nextTick(() => { this.place(); this.$refs.hex.focus({ preventScroll: true }); });
            },
            hide(focus = true) {
                this.open = false;
                this.$refs.panel.hidePopover();
                if (focus) this.$refs.trigger.focus({ preventScroll: true });
            },
            place() {
                const anchor = this.$refs.anchor.getBoundingClientRect(), panel = this.$refs.panel;
                const width = Math.min(288, window.innerWidth - 24);
                panel.style.width = width + 'px';
                panel.style.left = Math.max(12, Math.min(anchor.left, window.innerWidth - width - 12)) + 'px';
                panel.style.top = Math.max(12, Math.min(anchor.bottom + 8, window.innerHeight - panel.offsetHeight - 12)) + 'px';
                if (anchor.bottom + 8 + panel.offsetHeight > window.innerHeight && anchor.top > panel.offsetHeight + 8) panel.style.top = (anchor.top - panel.offsetHeight - 8) + 'px';
            }
        }"
        x-modelable="value" {{ $attributes->whereStartsWith(['wire:model', 'x-model']) }}
        x-on:color-channel.stop="change($event.detail.channel, $event.detail.value)"
        x-on:click.outside="open && hide(false)"
        x-on:keydown.escape="if (open) { $event.stopPropagation(); $event.preventDefault(); hide(); }"
        x-on:resize.window="open && place()" x-on:scroll.window.capture="open && place()"
        x-on:reset.window="if ($event.target.contains($el)) $nextTick(() => read(@js($initial)))">

        @if ($name)
            <input type="hidden" name="{{ $name }}" value="{{ $initial }}" x-bind:value="value" @disabled($disabled)>
        @endif

        <div x-ref="anchor" @class(['flex min-w-0 items-center gap-2', 'opacity-50' => $disabled])>
            <x-nui::button variant="ghost" :id="$mode === 'well' ? $id : $id.'-trigger'" x-ref="trigger"
                aria-haspopup="dialog" :aria-controls="$id.'-panel'" aria-expanded="false" x-bind:aria-expanded="open.toString()"
                :aria-label="$label ?: 'Escolher cor'" :aria-describedby="$described" :aria-invalid="$error ? 'true' : null"
                :disabled="$disabled || $readonly" x-on:click="open ? hide() : show()"
                class="nui-color-trigger !size-10 !shrink-0 !p-1.5 border-field-border bg-field shadow-nui-control">
                <span aria-hidden="true" class="nui-color-swatch block size-full rounded-[calc(var(--nui-radius-field)-2px)] border border-black/15 shadow-nui-inset"
                    style="background-color: {{ $initial }}" x-bind:style="{ backgroundColor: normalize(value) || '#000000' }"></span>
            </x-nui::button>
            @if ($mode !== 'well')
                <x-nui::input :id="$id" type="text" :value="$initial" x-bind:value="draft" maxlength="9" spellcheck="false" autocomplete="off"
                    :aria-label="($label ?: 'Cor').' em hexadecimal'" :disabled="$disabled" :readonly="$readonly" :aria-describedby="$described"
                    x-on:input="draft = $event.target.value; invalid = false" x-on:change="apply()" x-on:keydown.enter.prevent="apply()" x-bind:aria-invalid="invalid.toString()"
                    class="min-w-0 flex-1 [&_input]:font-mono [&_input]:text-sm [&_input]:uppercase">
                    <x-slot:trailing><span aria-hidden="true" class="text-[0.625rem] text-base-content/45">HEX</span></x-slot:trailing>
                </x-nui::input>
            @endif
        </div>
        <p x-show="invalid && !open" x-cloak role="status" class="mt-1 text-xs text-danger">Use uma cor como #805735. A última cor válida foi mantida.</p>

        <div popover="manual" data-lenis-prevent id="{{ $id }}-panel" x-ref="panel" role="dialog" aria-label="{{ 'Seletor de '.($label ?: 'cor') }}"
            class="nui-color-panel fixed inset-auto m-0 max-h-[calc(100dvh-1.5rem)] overflow-y-auto rounded-nui border-[length:var(--nui-border-width)] border-base-300 bg-base-100 p-4 text-base-content shadow-nui"
            x-on:keydown.tab="const nodes = [...$el.querySelectorAll('button:not(:disabled), input:not(:disabled)')]; if ($event.shiftKey && document.activeElement === nodes[0]) { $event.preventDefault(); nodes.at(-1).focus(); } else if (!$event.shiftKey && document.activeElement === nodes.at(-1)) { $event.preventDefault(); nodes[0].focus(); }">
            <div class="mb-3 flex items-center justify-between gap-3">
                <span class="text-sm font-semibold">{{ $label ?: 'Escolher cor' }}</span>
                <x-nui::button variant="ghost" x-on:click="hide()" aria-label="Fechar seletor de cor" class="-mr-1 !size-7 !p-0 rounded-nui-field hover:bg-base-content/5 focus-visible:outline-2 focus-visible:outline-primary">
                    <svg aria-hidden="true" viewBox="0 0 20 20" class="size-4" fill="none" stroke="currentColor" stroke-width="1.7"><path d="m5 5 10 10M5 15 15 5" /></svg>
                </x-nui::button>
            </div>
            <div x-ref="surface" aria-hidden="true" class="nui-color-surface relative h-36 w-full cursor-crosshair touch-none overflow-hidden rounded-nui-field"
                x-bind:style="{ backgroundColor: 'hsl(' + h + ' 100% 50%)' }"
                x-on:pointerdown="$el.setPointerCapture($event.pointerId); point($event)"
                x-on:pointermove="if ($event.buttons === 1) point($event)">
                <span class="pointer-events-none absolute size-3.5 -translate-x-1/2 -translate-y-1/2 rounded-full border-2 border-white shadow-[0_1px_3px_#0009]"
                    x-bind:style="{ left: s + '%', top: (100 - v) + '%' }"></span>
            </div>
            <div class="mt-4 flex flex-col gap-4">
                <x-nui::range label="Matiz" :min="0" :max="360" :show-value="false" x-model="h"
                    class="nui-color-hue-control [&_label]:text-xs [&_label]:text-base-content/70" />
                <div class="grid grid-cols-2 gap-3">
                    <x-nui::number label="Saturação" min="0" max="100" x-bind:value="Math.round(s)" aria-label="Saturação em porcentagem"
                        x-on:input="$dispatch('color-channel', { channel: 's', value: $event.target.value })"
                        class="[&_label]:text-xs [&_label]:text-base-content/70" />
                    <x-nui::number label="Brilho" min="0" max="100" x-bind:value="Math.round(v)" aria-label="Brilho em porcentagem"
                        x-on:input="$dispatch('color-channel', { channel: 'v', value: $event.target.value })"
                        class="[&_label]:text-xs [&_label]:text-base-content/70" />
                </div>
                <x-nui::range label="Opacidade" :min="0" :max="100" suffix="%" x-model="a"
                    class="[&_label]:text-xs [&_label]:text-base-content/70" />
                <x-nui::input id="{{ $id }}-hex" label="Hexadecimal" x-ref="hex" type="text" x-model="draft" maxlength="9" spellcheck="false" autocomplete="off"
                    x-on:change="apply()" x-on:keydown.enter.prevent="apply(); if (!invalid) hide()" x-bind:aria-invalid="invalid.toString()"
                    class="[&_label]:text-xs [&_label]:text-base-content/70 [&_input]:font-mono [&_input]:uppercase" />
                <p id="{{ $id }}-format-error" x-show="invalid" x-cloak class="text-xs text-danger">Use #RRGGBB ou #RRGGBBAA.</p>
            </div>
            @if (count($swatches))
                <div class="mt-4 flex flex-wrap gap-2" role="group" aria-label="Cores sugeridas">
                    @foreach ($swatches as $swatch)
                        <x-nui::button variant="ghost" aria-label="{{ 'Usar '.$swatch }}" aria-pressed="false"
                            :data-color="strtolower($swatch)" x-bind:aria-pressed="value === $el.dataset.color" x-on:click="read($el.dataset.color)"
                            class="!size-6 !p-0 rounded-nui-field border border-base-content/20 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary"
                            style="background-color: {{ $swatch }}">
                            <svg aria-hidden="true" x-show="value === @js(strtolower($swatch))" viewBox="0 0 20 20" class="size-full" fill="none" stroke="white" stroke-width="2" style="filter: drop-shadow(0 1px 1px #0009)"><path d="m5 10 3 3 7-7" /></svg>
                        </x-nui::button>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</x-nui::field>
