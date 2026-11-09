@props([
    'label' => null,
    'labelStyle' => 'top',
    'size' => 'md',
    'hint' => null,
    'error' => null,
    'options' => [],
    'optionsBind' => null,
    'placeholder' => 'Selecione…',
    'searchable' => false,
    'multiple' => false,
])

@php
    $name = $attributes->get('name');
    $id = $attributes->get('id') ?? $name ?? $attributes->whereStartsWith('wire:model')->first() ?? 'nui-'.Str::random(8);
    $error ??= $name ? ($errors ?? null)?->first($name) : null;
    $labelStyle = $labelStyle === 'floating' ? 'legend' : $labelStyle; // floating needs :placeholder-shown, which buttons lack

    // Accepts ['br' => 'Brasil'], ['Brasil', 'Chile'] or [['value' => 'br', 'label' => 'Brasil', 'icon' => '<svg…>', 'image' => 'url', 'description' => '…']].
    $items = collect($options)->map(fn ($option, $key) => is_array($option)
        ? ['value' => (string) ($option['value'] ?? $key), 'label' => (string) $option['label'], 'description' => $option['description'] ?? null, 'icon' => $option['icon'] ?? null, 'image' => $option['image'] ?? null]
        : ['value' => (string) (array_is_list($options) ? $option : $key), 'label' => (string) $option, 'description' => null, 'icon' => null, 'image' => null]
    )->values();

    $initial = $name ? old($name, $attributes->get('value')) : $attributes->get('value');
    $initial = $multiple ? array_map('strval', (array) ($initial ?? [])) : (filled($initial) ? (string) $initial : null);
@endphp

{{-- Custom listbox. Works in plain forms (hidden inputs) and with wire:model (x-modelable). Icons are trusted HTML from your code. --}}
<x-nui::field :label="$labelStyle === 'top' ? $label : null" :$hint :$error :for="$id" :class="$attributes->get('class')">
    <div class="relative"
        x-data="{
            open: false,
            search: '',
            active: null,
            multiple: @js($multiple),
            searchable: @js($searchable),
            value: @js($initial),
            items: @js($items->map(fn (array $item) => Arr::only($item, ['value', 'label']))),
            normalize(text) { return text.normalize('NFD').replace(/\p{Diacritic}/gu, '').toLowerCase() },
            matches(label) { return this.normalize(label).includes(this.normalize(this.search)) },
            visible() { return this.items.filter((item) => this.matches(item.label)) },
            isSelected(value) { return this.multiple ? this.value.includes(value) : this.value === value },
            labelOf(value) { return this.items.find((item) => item.value === value)?.label ?? '' },
            optionId(value) { return @js($id) + '-option-' + this.items.findIndex((item) => item.value === value) },
            show() {
                if (this.open) return;
                this.open = true;
                this.openPanel();
                this.active = (! this.multiple && this.value) || this.visible()[0]?.value || null;
                this.$nextTick(() => { (this.searchable ? this.$refs.search : this.$refs.list).focus(); this.reveal(); });
            },
            hide(refocus = true) { if (! this.open) return; this.open = false; this.closePanel(); this.search = ''; if (refocus) this.$refs.trigger.focus(); },
            place() {
                const anchor = this.$refs.anchor.getBoundingClientRect(), panel = this.$refs.panel;
                panel.style.minWidth = Math.max(anchor.width, 132) + 'px';
                panel.style.width = anchor.width + 'px';
                const height = panel.offsetHeight, below = innerHeight - anchor.bottom;
                const up = below < height + 16 && anchor.top > below;
                panel.dataset.side = up ? 'top' : 'bottom';
                panel.style.left = Math.max(8, Math.min(anchor.left, innerWidth - panel.offsetWidth - 8)) + 'px';
                panel.style.top = up ? 'auto' : anchor.bottom + 8 + 'px';
                panel.style.bottom = up ? innerHeight - anchor.top + 8 + 'px' : 'auto';
            },
            openPanel() { const panel = this.$refs.panel; panel.classList.remove('nui-pop-out'); panel.showPopover(); this.place(); panel.classList.add('nui-pop-in'); },
            closePanel() { const panel = this.$refs.panel; panel.classList.replace('nui-pop-in', 'nui-pop-out'); setTimeout(() => { if (!this.open && panel.isConnected && panel.matches(':popover-open')) panel.hidePopover(); }, 130); },
            choose(value) {
                if (value === null) return;
                if (this.multiple) { this.value = this.isSelected(value) ? this.value.filter((item) => item !== value) : [...this.value, value]; }
                else { this.value = value; this.hide(); }
            },
            move(step) {
                const list = this.visible();
                if (! list.length) return;
                const index = list.findIndex((item) => item.value === this.active);
                this.active = list[Math.min(Math.max(index + step, 0), list.length - 1)].value;
                this.reveal();
            },
            edge(last) { const list = this.visible(); this.active = list[last ? list.length - 1 : 0]?.value ?? null; this.reveal(); },
            reveal() { this.$nextTick(() => this.active !== null && document.getElementById(this.optionId(this.active))?.scrollIntoView({ block: 'nearest' })) },
            onKey(event) {
                const keys = { ArrowDown: () => this.move(1), ArrowUp: () => this.move(-1), Home: () => this.edge(false), End: () => this.edge(true), Enter: () => this.choose(this.active), Escape: () => this.hide() };
                if (event.key === ' ' && ! this.searchable) keys[' '] = () => this.choose(this.active);
                if (keys[event.key]) { event.preventDefault(); keys[event.key](); }
                if (event.key === 'Tab') this.hide(false);
            },
        }"
        @if ($optionsBind) x-effect="items = ({{ $optionsBind }}).map(item => ({ value: String(item.value), label: String(item.label) }))" @endif
        x-modelable="value"
        {{ $attributes->whereStartsWith(['wire:model', 'x-model']) }}
        x-on:click.outside="open && hide(false)"
        x-on:resize.window="open && place()" x-on:scroll.window.capture="open && place()"
        x-init="$watch('search', () => { active = visible()[0]?.value ?? null; $nextTick(() => open && place()) })">

        @if ($name)
            <template x-if="multiple">
                <div><template x-for="item in value" x-bind:key="item"><input type="hidden" name="{{ str_ends_with($name, '[]') ? $name : $name.'[]' }}" x-bind:value="item"></template></div>
            </template>
            <template x-if="! multiple"><input type="hidden" name="{{ $name }}" x-bind:value="value ?? ''"></template>
        @endif

        <x-nui::box :invalid="(bool) $error" :label="$labelStyle === 'top' ? null : $label" :for="$id" :label-style="$labelStyle" :size="$size" class="pr-2" x-ref="anchor">
            <x-nui::button variant="ghost" size="sm" type="button" id="{{ $id }}" x-ref="trigger" role="combobox" aria-haspopup="listbox" aria-controls="{{ $id }}-listbox" :aria-label="$attributes->get('aria-label')"
                aria-expanded="false" x-bind:aria-expanded="open.toString()"
                :aria-invalid="$error ? 'true' : null" :aria-describedby="$error ? $id.'-error' : ($hint ? $id.'-hint' : null)"
                x-on:click="open ? hide() : show()"
                x-on:keydown.arrow-down.prevent="show()" x-on:keydown.arrow-up.prevent="show()"
                :disabled="$attributes->get('disabled')"
                :class="\Illuminate\Support\Arr::toCssClasses(['!justify-start !h-auto !p-0 !border-0 !bg-transparent !shadow-none flex flex-1 min-w-0 items-center gap-2 text-left outline-none disabled:cursor-not-allowed after:absolute after:inset-0 after:content-[\'\']', $size === 'sm' ? 'py-1 !text-xs font-medium' : 'py-1.5'])">
                {{-- Single: the chosen option's icon/image and label --}}
                <template x-if="! multiple">
                    <span class="flex min-w-0 items-center gap-2.5">
                        @foreach ($items as $item)
                            @if ($item['image'] || $item['icon'])
                                <span x-show="value === @js($item['value'])" x-cloak class="flex shrink-0 [&_svg]:size-4.5">
                                    @if ($item['image'])
                                        <img src="{{ $item['image'] }}" alt="" class="size-6 rounded-full object-cover">
                                    @else
                                        {!! $item['icon'] !!}
                                    @endif
                                </span>
                            @endif
                        @endforeach
                        <span class="truncate" x-text="value ? labelOf(value) : @js($placeholder)" x-bind:class="! value && 'text-base-content/45'">{{ data_get($items->firstWhere('value', $initial), 'label', $placeholder) }}</span>
                    </span>
                </template>
                {{-- Multiple: chips --}}
                <template x-if="multiple">
                    <span class="flex min-w-0 flex-wrap items-center gap-1.5">
                        <span x-show="! value.length" class="text-base-content/45">{{ $placeholder }}</span>
                        <template x-for="item in value" x-bind:key="item">
                            <span class="inline-flex items-center h-6 px-2 rounded-full bg-primary/12 text-primary text-xs font-semibold" x-text="labelOf(item)"></span>
                        </template>
                    </span>
                </template>
            </x-nui::button>
            <svg aria-hidden="true" @class(['shrink-0 text-base-content/55 transition-transform', $size === 'sm' ? 'size-3.5' : 'size-4']) x-bind:class="open && 'rotate-180'" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="m4 6 4 4 4-4" /></svg>
        </x-nui::box>

        {{-- Popover --}}
        {{-- Top-layer popover: renders above dialogs and overflow containers. --}}
        <div popover="manual" x-ref="panel"
            class="fixed m-0 inset-auto overflow-visible p-1.5 bg-base-100 text-base-content rounded-nui shadow-nui border-[length:var(--nui-border-width)] border-base-300">
            @if ($searchable)
                <x-nui::input type="search" x-ref="search" x-model="search" x-on:keydown="onKey($event)" placeholder="Pesquisar…" autocomplete="off"
                    role="searchbox" aria-label="Pesquisar opções" :aria-controls="$id.'-listbox'" x-bind:aria-activedescendant="active !== null ? optionId(active) : ''"
                    class="mb-1.5 w-full [&_input]:text-sm">
                    <x-slot:leading>
                        <svg aria-hidden="true" class="size-4" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"><circle cx="7" cy="7" r="4.5" /><path d="m10.5 10.5 3 3" /></svg>
                    </x-slot:leading>
                </x-nui::input>
            @endif
            <ul data-lenis-prevent id="{{ $id }}-listbox" x-ref="list" role="listbox" tabindex="-1" @if ($label) aria-label="{{ $label }}" @endif
                @if ($multiple) aria-multiselectable="true" @endif
                x-bind:aria-activedescendant="active !== null ? optionId(active) : ''" x-on:keydown="onKey($event)"
                class="max-h-64 overflow-y-auto outline-none flex flex-col gap-0.5">
                @if ($optionsBind)
                    <template x-for="item in items" x-bind:key="item.value">
                        <li x-bind:id="optionId(item.value)" role="option" x-show="matches(item.label)"
                            x-bind:aria-selected="isSelected(item.value).toString()" x-on:click="choose(item.value)"
                            x-on:mousemove="active = item.value" x-bind:class="active === item.value && 'bg-base-content/[0.06]'"
                            class="flex cursor-pointer select-none items-center gap-3 rounded-[calc(var(--nui-radius)-4px)] px-2.5 py-2 text-sm">
                            <span class="min-w-0 flex-1 truncate" x-text="item.label"></span>
                            <span x-show="isSelected(item.value)" aria-hidden="true" class="text-primary">
                                <svg class="size-4" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="2"><path d="m3.5 8.5 3 3 6-7" /></svg>
                            </span>
                        </li>
                    </template>
                @else
                @foreach ($items as $index => $item)
                    <li id="{{ $id }}-option-{{ $index }}" role="option"
                        x-show="matches(@js($item['label']))"
                        x-bind:aria-selected="isSelected(@js($item['value'])).toString()"
                        x-on:click="choose(@js($item['value']))" x-on:mousemove="active = @js($item['value'])"
                        x-bind:class="active === @js($item['value']) && 'bg-base-content/[0.06]'"
                        class="flex items-center gap-3 px-2.5 py-2 rounded-[calc(var(--nui-radius)-4px)] cursor-pointer select-none text-sm">
                        @if ($multiple)
                            <span class="grid place-items-center size-4.5 shrink-0 rounded-[0.3rem] border-[1.5px] transition"
                                x-bind:class="isSelected(@js($item['value'])) ? 'bg-primary border-primary text-primary-content' : 'border-base-content/25 text-transparent'">
                                <svg aria-hidden="true" class="size-3" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round"><path d="m3.5 8.5 3 3 6-7" /></svg>
                            </span>
                        @endif
                        @if ($item['image'])
                            <img src="{{ $item['image'] }}" alt="" class="size-7 shrink-0 rounded-full object-cover">
                        @elseif ($item['icon'])
                            <span class="grid place-items-center size-7 shrink-0 rounded-[calc(var(--nui-radius-field)-4px)] bg-base-content/[0.05] text-base-content/75 [&_svg]:size-4">{!! $item['icon'] !!}</span>
                        @endif
                        <span class="flex min-w-0 flex-1 flex-col">
                            <span class="truncate font-medium">{{ $item['label'] }}</span>
                            @if ($item['description'])
                                <span class="truncate text-xs text-base-content/60">{{ $item['description'] }}</span>
                            @endif
                        </span>
                        @unless ($multiple)
                            <svg aria-hidden="true" x-show="isSelected(@js($item['value']))" x-cloak class="size-4 shrink-0 text-primary" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="m3.5 8.5 3 3 6-7" /></svg>
                        @endunless
                    </li>
                @endforeach
                @endif
                <li x-show="! visible().length" class="px-2.5 py-6 text-center text-sm text-base-content/55" role="presentation">Nenhum resultado para “<span x-text="search"></span>”</li>
            </ul>
        </div>
    </div>
</x-nui::field>
