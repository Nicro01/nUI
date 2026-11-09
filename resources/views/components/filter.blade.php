@props([
    'fields' => [],
    'search' => null,
    'name' => null,
    'label' => 'Filtros',
    'clearLabel' => 'Limpar filtros',
])

@php
    $id = $attributes->get('id') ?? 'nui-filter-'.Str::random(8);

    // Each field: ['name', 'label', 'type' => multiple | single | range, 'options' (multiple/single), 'min', 'max', 'step', 'prefix', 'suffix', 'icon'].
    $normalize = fn (array $options): array => collect($options)->map(fn ($option, $key) => is_array($option)
        ? ['value' => (string) ($option['value'] ?? $key), 'label' => (string) $option['label'], 'count' => $option['count'] ?? null]
        : ['value' => (string) (array_is_list($options) ? $option : $key), 'label' => (string) $option, 'count' => null]
    )->values()->all();

    $items = collect($fields)->map(fn (array $field) => [
        'name' => $field['name'],
        'label' => $field['label'] ?? Str::headline($field['name']),
        'type' => $field['type'] ?? 'multiple',
        'options' => $normalize($field['options'] ?? []),
        'min' => $field['min'] ?? null,
        'max' => $field['max'] ?? null,
        'step' => $field['step'] ?? 1,
        'prefix' => $field['prefix'] ?? '',
        'suffix' => $field['suffix'] ?? '',
        'icon' => $field['icon'] ?? null,
    ])->values();

    $empty = ['q' => ''];
    foreach ($items as $item) {
        $empty[$item['name']] = match ($item['type']) {
            'single' => null,
            'range' => ['min' => null, 'max' => null],
            default => [],
        };
    }

    $initial = array_replace_recursive($empty, (array) ($name ? old($name, $attributes->get('value', [])) : $attributes->get('value', [])));
    $chip = [
        'nui-filter-chip inline-flex items-center gap-2 h-9 max-w-full px-3 rounded-nui text-sm [font-weight:var(--nui-control-weight)] cursor-pointer select-none transition',
        'border border-base-content/15 border-b-[3px] border-b-base-content/25 shadow-nui',
        'focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary active:translate-y-px [&_svg]:size-4 [&_svg]:shrink-0',
    ];
@endphp

{{--
    Filter bar: search, one key-cap button per field that opens a panel (multiple choice, single choice or range),
    removable tags for what is active and "clear all". The whole value is one object — { q, field: [...] | 'x' | {min, max} } —
    exposed through x-modelable for x-model / wire:model, posted as hidden inputs when "name" is set, and announced
    with a "filter-change" event (detail = value). Icons are trusted HTML from your code.
--}}
<div role="search" aria-label="{{ $label }}"
    x-data="{
        value: @js($initial),
        empty: @js($empty),
        fields: @js($items->map(fn (array $item) => Arr::only($item, ['name', 'label', 'type', 'options', 'prefix', 'suffix']))),
        open: null,
        reopened: false,
        find: '',
        init() {
            this.$watch('value', () => this.$dispatch('filter-change', JSON.parse(JSON.stringify(this.value))), { deep: true });
        },
        field(name) { return this.fields.find((field) => field.name === name) },
        labelOf(field, value) { return field.options.find((option) => option.value === String(value))?.label ?? value },
        money(field, number) { return field.prefix + Number(number).toLocaleString('pt-BR') + field.suffix },
        isActive(name) {
            const field = this.field(name), current = this.value[name];
            if (field.type === 'range') return current && (current.min !== null && current.min !== '' || current.max !== null && current.max !== '');
            return field.type === 'single' ? current !== null && current !== '' : (current ?? []).length > 0;
        },
        summary(name) {
            const field = this.field(name), current = this.value[name];
            if (! this.isActive(name)) return '';
            if (field.type === 'range') {
                const low = current.min !== null && current.min !== '', high = current.max !== null && current.max !== '';
                return low && high ? this.money(field, current.min) + ' a ' + this.money(field, current.max) : (low ? 'a partir de ' + this.money(field, current.min) : 'até ' + this.money(field, current.max));
            }
            if (field.type === 'single') return this.labelOf(field, current);
            return current.length === 1 ? this.labelOf(field, current[0]) : current.length + ' selecionados';
        },
        tags() {
            const list = [];
            this.fields.forEach((field) => {
                if (! this.isActive(field.name)) return;
                if (field.type === 'multiple') this.value[field.name].forEach((item) => list.push({ key: field.name + ':' + item, field: field.name, item, text: field.label + ': ' + this.labelOf(field, item) }));
                else list.push({ key: field.name, field: field.name, item: null, text: field.label + ': ' + this.summary(field.name) });
            });
            return list;
        },
        get count() { return this.tags().length + (this.value.q ? 1 : 0) },
        remove(tag) {
            const field = this.field(tag.field);
            if (field.type === 'multiple') this.value[tag.field] = this.value[tag.field].filter((item) => item !== tag.item);
            else this.reset(tag.field);
        },
        reset(name) { this.value[name] = JSON.parse(JSON.stringify(this.empty[name])) },
        clearAll() { this.value = JSON.parse(JSON.stringify(this.empty)) },
        toggle(name) { if (this.reopened) { this.reopened = false; return this.close(); } this.open === name ? this.close() : this.show(name) },
        show(name) {
            this.close(false);
            this.open = name; this.find = '';
            const panel = document.getElementById(@js($id) + '-panel-' + name);
            panel.showPopover();
            this.place(name);
            panel.classList.remove('nui-pop-in'); void panel.offsetWidth; panel.classList.add('nui-pop-in');
            this.$nextTick(() => panel.querySelector('input:not([type=hidden]), button')?.focus());
        },
        close(refocus = true) {
            if (! this.open) return;
            const name = this.open, panel = document.getElementById(@js($id) + '-panel-' + name);
            this.open = null;
            if (panel?.matches(':popover-open')) panel.hidePopover();
            if (refocus) document.getElementById(@js($id) + '-chip-' + name)?.focus({ preventScroll: true });
        },
        place(name = this.open) {
            if (! name) return;
            const chip = document.getElementById(@js($id) + '-chip-' + name).getBoundingClientRect(), panel = document.getElementById(@js($id) + '-panel-' + name);
            const below = innerHeight - chip.bottom - 8, up = panel.offsetHeight > below && chip.top > below;
            panel.dataset.side = up ? 'top' : 'bottom';
            panel.style.left = Math.max(12, Math.min(chip.left, innerWidth - panel.offsetWidth - 12)) + 'px';
            panel.style.top = (up ? chip.top - panel.offsetHeight - 8 : chip.bottom + 8) + 'px';
        },
        matches(label) { const clean = (text) => text.normalize('NFD').replace(/\p{Diacritic}/gu, '').toLowerCase(); return clean(label).includes(clean(this.find)) },
    }"
    x-modelable="value"
    {{ $attributes->whereStartsWith(['wire:model', 'x-model']) }}
    x-on:resize.window="place()" x-on:scroll.window.capture="open && place()"
    x-on:keydown.escape="open && (close(), $event.stopPropagation())"
    {{ $attributes->except(['id', 'class', 'value'])->whereDoesntStartWith(['wire:model', 'x-model']) }}
    {{ $attributes->only('class')->class('nui-filter flex flex-col gap-3 min-w-0') }}
    id="{{ $id }}">

    @if ($name)
        <input type="hidden" name="{{ $name }}[q]" x-bind:value="value.q">
        @foreach ($items as $item)
            @if ($item['type'] === 'multiple')
                <template x-for="selected in value[@js($item['name'])]" x-bind:key="selected"><input type="hidden" name="{{ $name }}[{{ $item['name'] }}][]" x-bind:value="selected"></template>
            @elseif ($item['type'] === 'single')
                <input type="hidden" name="{{ $name }}[{{ $item['name'] }}]" x-bind:value="value[@js($item['name'])] ?? ''">
            @else
                <input type="hidden" name="{{ $name }}[{{ $item['name'] }}][min]" x-bind:value="value[@js($item['name'])].min ?? ''">
                <input type="hidden" name="{{ $name }}[{{ $item['name'] }}][max]" x-bind:value="value[@js($item['name'])].max ?? ''">
            @endif
        @endforeach
    @endif

    <div class="flex flex-wrap items-center gap-2">
        @if ($search !== null)
            <label class="relative flex-1 min-w-[min(100%,14rem)]">
                <span class="sr-only">{{ $search }}</span>
                <svg aria-hidden="true" class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 size-4 text-base-content/45" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"><circle cx="7" cy="7" r="4.5" /><path d="m10.5 10.5 3 3" /></svg>
                <input type="search" x-model.debounce.250ms="value.q" placeholder="{{ $search }}"
                    class="w-full h-9 pl-9 pr-3 rounded-nui-field bg-field text-sm text-base-content placeholder:text-base-content/45 border-[length:var(--nui-field-border-width)] border-field-border shadow-nui-inset outline-none focus-visible:outline-2 focus-visible:outline-offset-1 focus-visible:outline-primary">
            </label>
        @endif

        @foreach ($items as $item)
            @php
                $fieldName = $item['name'];
                $panelId = $id.'-panel-'.$fieldName;
            @endphp
            <button type="button" id="{{ $id }}-chip-{{ $fieldName }}" aria-haspopup="dialog" aria-controls="{{ $panelId }}"
                x-bind:aria-expanded="(open === @js($fieldName)).toString()" x-on:pointerdown="reopened = open === @js($fieldName)" x-on:click="toggle(@js($fieldName))"
                x-bind:class="isActive(@js($fieldName)) ? 'bg-primary/10 text-primary border-primary/45 border-b-primary/60' : 'bg-base-100 text-base-content hover:bg-base-200'"
                @class($chip)>
                @if ($item['icon'])
                    <span aria-hidden="true" class="inline-flex">{!! $item['icon'] !!}</span>
                @endif
                <span>{{ $item['label'] }}</span>
                <span x-show="isActive(@js($fieldName))" x-cloak class="truncate max-w-40 font-normal">
                    <span aria-hidden="true" class="inline-block w-px h-3.5 mx-0.5 align-[-2px] bg-current opacity-30"></span> <span x-text="summary(@js($fieldName))"></span>
                </span>
                <svg aria-hidden="true" class="opacity-60 transition-transform duration-300" x-bind:class="open === @js($fieldName) && 'rotate-180'" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="m4 6 4 4 4-4" /></svg>
            </button>

            <div popover="auto" id="{{ $panelId }}" role="dialog" aria-label="{{ $item['label'] }}" tabindex="-1"
                x-on:toggle="if ($event.newState === 'closed' && open === @js($fieldName)) open = null"
                x-on:focusout="$nextTick(() => { if (open === @js($fieldName) && ! $el.contains(document.activeElement) && document.activeElement !== document.getElementById(@js($id.'-chip-'.$fieldName))) close(false) })"
                class="nui-filter-panel outline-none fixed m-0 inset-auto w-[min(18rem,calc(100vw-24px))] max-h-[min(24rem,70vh)] flex-col bg-base-100 text-base-content rounded-nui border-[length:var(--nui-border-width)] border-base-300 shadow-[0_1px_2px_oklch(0%_0_0/0.06),0_16px_36px_-14px_oklch(0%_0_0/0.35)] [&:popover-open]:flex">
                <div class="flex flex-col min-h-0">
                    @if (in_array($item['type'], ['multiple', 'single'], true) && count($item['options']) > 7)
                        <div class="p-2 border-b border-base-300">
                            <input type="search" x-model="find" placeholder="Procurar em {{ Str::lower($item['label']) }}" aria-label="Procurar em {{ Str::lower($item['label']) }}"
                                class="w-full h-8 px-2.5 rounded-nui-field bg-field text-sm border-[length:var(--nui-field-border-width)] border-field-border shadow-nui-inset outline-none focus-visible:outline-2 focus-visible:outline-primary">
                        </div>
                    @endif

                    @if ($item['type'] === 'range')
                        <div class="grid grid-cols-2 gap-3 p-4">
                            @foreach (['min' => 'Mínimo', 'max' => 'Máximo'] as $edge => $edgeLabel)
                                <label class="flex flex-col gap-1.5 text-xs font-medium text-base-content/70">
                                    {{ $edgeLabel }}
                                    <span class="flex items-center h-9 px-2.5 gap-1 rounded-nui-field bg-field border-[length:var(--nui-field-border-width)] border-field-border shadow-nui-inset focus-within:outline-2 focus-within:outline-primary">
                                        @if ($item['prefix'])
                                            <span class="text-sm text-base-content/50">{{ trim($item['prefix']) }}</span>
                                        @endif
                                        <input type="number" inputmode="decimal" x-model.number.debounce.300ms="value[@js($fieldName)].{{ $edge }}"
                                            @if ($item['min'] !== null) min="{{ $item['min'] }}" @endif @if ($item['max'] !== null) max="{{ $item['max'] }}" @endif step="{{ $item['step'] }}"
                                            placeholder="{{ $edge === 'min' ? ($item['min'] ?? '') : ($item['max'] ?? '') }}"
                                            class="w-full min-w-0 bg-transparent text-sm tabular-nums text-base-content outline-none [appearance:textfield] [&::-webkit-inner-spin-button]:appearance-none">
                                        @if ($item['suffix'])
                                            <span class="text-sm text-base-content/50">{{ trim($item['suffix']) }}</span>
                                        @endif
                                    </span>
                                </label>
                            @endforeach
                        </div>
                    @else
                        <div data-lenis-prevent class="overflow-y-auto overscroll-contain p-2 flex flex-col" role="group" aria-label="{{ $item['label'] }}">
                            @foreach ($item['options'] as $option)
                                <div x-show="matches(@js($option['label']))"
                                    x-on:click="if (!$event.target.closest('label') && !$event.target.closest('input')) { $el.querySelector('input')?.click(); }"
                                    class="nui-filter-option flex items-center justify-between gap-3 px-2 py-1.5 rounded-[min(var(--nui-radius-field),0.5rem)] hover:bg-base-content/[0.04] has-[:focus-visible]:bg-base-content/[0.04] cursor-pointer select-none [&>label]:flex-1 [&>label]:min-w-0 [&>label]:cursor-pointer [&>label]:items-center [&>label>span:first-child]:mt-0 [&>label>span:last-child]:flex-1 [&>label>span:last-child]:min-w-0">
                                    @if ($item['type'] === 'single')
                                        <x-nui::radio :label="$option['label']" :name="$id.'-'.$fieldName" :value="$option['value']" x-model="value[{{ Js::from($fieldName) }}]" />
                                    @else
                                        <x-nui::checkbox :label="$option['label']" :value="$option['value']" x-model="value[{{ Js::from($fieldName) }}]" />
                                    @endif
                                    @if ($option['count'] !== null)
                                        <span class="text-xs tabular-nums text-base-content/50 shrink-0 pointer-events-none">{{ $option['count'] }}</span>
                                    @endif
                                </div>
                            @endforeach
                            <p x-show="! @js(collect($item['options'])->pluck('label')->all()).some((label) => matches(label))" x-cloak class="px-2 py-3 text-sm text-base-content/60">Nada encontrado para “<span x-text="find"></span>”.</p>
                        </div>
                    @endif

                    <div class="flex items-center justify-between gap-2 px-3 py-2 border-t border-base-300">
                        <x-nui::button variant="ghost" size="sm" x-on:click="reset({{ Js::from($fieldName) }})" x-bind:disabled="! isActive({{ Js::from($fieldName) }})">Limpar</x-nui::button>
                        <x-nui::button size="sm" x-on:click="close()">Pronto</x-nui::button>
                    </div>
                </div>
            </div>
        @endforeach

        {{ $actions ?? '' }}
    </div>

    <div x-show="count > 0" x-cloak class="flex flex-wrap items-center gap-2" aria-live="polite">
        <span class="sr-only" x-text="count + (count === 1 ? ' filtro ativo' : ' filtros ativos')"></span>
        <template x-if="value.q">
            <span class="nui-filter-tag inline-flex items-center gap-1 h-7 pl-2.5 pr-1 rounded-full bg-base-content/[0.07] border-b-2 border-b-base-content/15 text-xs font-medium text-base-content/80">
                <span class="truncate max-w-48" x-text="'Busca: “' + value.q + '”'"></span>
                <button type="button" x-on:click="value.q = ''" x-bind:aria-label="'Remover busca ' + value.q" class="grid place-items-center size-5 rounded-full cursor-pointer hover:bg-base-content/10 focus-visible:outline-2 focus-visible:outline-primary">
                    <svg aria-hidden="true" class="size-3" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="m4.5 4.5 7 7M11.5 4.5l-7 7" /></svg>
                </button>
            </span>
        </template>
        <template x-for="tag in tags()" x-bind:key="tag.key">
            <span class="nui-filter-tag inline-flex items-center gap-1 h-7 pl-2.5 pr-1 rounded-full bg-primary/10 text-primary border-b-2 border-b-primary/25 text-xs font-medium">
                <span class="truncate max-w-56" x-text="tag.text"></span>
                <button type="button" x-on:click="remove(tag)" x-bind:aria-label="'Remover filtro ' + tag.text" class="grid place-items-center size-5 rounded-full cursor-pointer hover:bg-primary/15 focus-visible:outline-2 focus-visible:outline-primary">
                    <svg aria-hidden="true" class="size-3" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="m4.5 4.5 7 7M11.5 4.5l-7 7" /></svg>
                </button>
            </span>
        </template>
        <button type="button" x-on:click="clearAll()" class="h-7 px-2 rounded-[min(var(--nui-radius-field),0.375rem)] text-xs font-medium text-base-content/70 underline underline-offset-2 hover:text-base-content cursor-pointer focus-visible:outline-2 focus-visible:outline-primary">{{ $clearLabel }}</button>
        @isset($summary)
            <span class="ml-auto text-sm text-base-content/60">{{ $summary }}</span>
        @endisset
    </div>
</div>
