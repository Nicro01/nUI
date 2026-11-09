@props([
    'id' => null,
    'title' => 'Configurações',
    'description' => null,
    'sections' => [],
    'default' => null,
    'searchable' => true,
    'searchPlaceholder' => 'Buscar seção…',
    'size' => 'lg',
    'mobile' => 'sheet',
])

@php
    $id ??= 'nui-settings-'.Str::random(8);

    // Accepts ['perfil' => 'Perfil'], ['Perfil', 'Segurança'] or [['name', 'label', 'description', 'group', 'icon', 'badge', 'disabled']].
    $isList = is_array($sections) && array_is_list($sections);
    $items = collect($sections)->map(function ($section, $key) use ($isList) {
        if (is_array($section)) {
            $name = trim((string) ($section['name'] ?? ''));
        } else {
            $name = trim((string) ($isList ? $section : $key));
            $section = ['name' => $name, 'label' => (string) $section];
        }

        if ($name === '') {
            throw new InvalidArgumentException('Settings modal: cada seção precisa de um name.');
        }

        return [
            'name' => $name,
            'label' => (string) ($section['label'] ?? Str::headline($name)),
            'description' => isset($section['description']) ? (string) $section['description'] : null,
            'group' => isset($section['group']) && filled($section['group']) ? (string) $section['group'] : null,
            'icon' => $section['icon'] ?? null,
            'badge' => $section['badge'] ?? null,
            'disabled' => (bool) ($section['disabled'] ?? false),
        ];
    })->values();

    if ($items->pluck('name')->duplicates()->isNotEmpty()) {
        throw new InvalidArgumentException('Settings modal: cada seção precisa de um name único.');
    }

    $selected = (string) ($default ?? $items->firstWhere('disabled', false)['name'] ?? $items->first()['name'] ?? '');
    $key = fn (string $name): string => Str::slug($name) ?: md5($name);

    $grouped = [];
    foreach ($items as $item) {
        $groupKey = $item['group'] ?? '';
        $grouped[$groupKey][] = $item;
    }

    $widthClass = match ($size) {
        'md' => 'max-w-3xl',
        'xl' => 'max-w-5xl',
        default => 'max-w-4xl',
    };
@endphp

{{--
    Settings Modal built on native <dialog> and composed with nUI primitives (Input, Button, Form, Tag).
    Provides a split layout: vertical sidebar navigation with optional group captions, icons, badges and search,
    paired with scrollable <x-nui::settings-panel> sections and an optional footer action bar.
    Open with document.getElementById('{id}').showModal(). Active section works with x-model and wire:model (x-modelable).
--}}
<dialog id="{{ $id }}"
    @if ($title) aria-labelledby="{{ $id }}-title" @endif
    @if ($description) aria-describedby="{{ $id }}-description" @endif
    onclick="if (event.target === this) this.close()"
    x-data="{
        selected: @js($selected),
        query: '',
        ready: false,
        switched: false,
        sections: @js($items->map(fn (array $item) => Arr::only($item, ['name', 'label', 'description', 'group', 'disabled']))->all()),
        init() {
            if (! this.enabled().some((item) => item.name === this.selected)) {
                this.selected = this.enabled()[0]?.name ?? (this.sections[0]?.name ?? '');
            }
            this.$nextTick(() => { this.ready = true });
            this.$watch('selected', (value) => {
                this.switched = true;
                const current = this.sections.find((item) => item.name === value);
                this.$dispatch('settings-section-changed', { section: value, label: current?.label ?? value });
            });
        },
        normalize(text) {
            return String(text ?? '').normalize('NFD').replace(/\p{Diacritic}/gu, '').toLowerCase();
        },
        matches(name) {
            const q = this.normalize(this.query).trim();
            if (! q) return true;
            const item = this.sections.find((entry) => entry.name === name);
            if (! item) return false;
            return this.normalize([item.label, item.description, item.group].filter(Boolean).join(' ')).includes(q);
        },
        groupHasMatches(group) {
            const g = group ?? null;
            return this.sections.some((item) => (item.group ?? null) === g && this.matches(item.name));
        },
        visible() {
            return this.sections.filter((item) => this.matches(item.name));
        },
        enabled() {
            return this.visible().filter((item) => ! item.disabled);
        },
        tabs() {
            return [...(this.$refs.list?.querySelectorAll('[role=tab]') ?? [])];
        },
        tabOf(name) {
            return this.tabs().find((tab) => tab.dataset.name === name);
        },
        currentSection() {
            return this.sections.find((item) => item.name === this.selected) ?? null;
        },
        select(name, focus = false) {
            const item = this.sections.find((entry) => entry.name === name);
            if (! item || item.disabled) return;
            this.selected = name;
            const tab = this.tabOf(name);
            if (focus && tab) tab.focus();
            tab?.scrollIntoView({ block: 'nearest', inline: 'nearest', behavior: matchMedia('(prefers-reduced-motion: reduce)').matches ? 'auto' : 'smooth' });
        },
        move(step) {
            const items = this.enabled();
            if (! items.length) return;
            const index = items.findIndex((item) => item.name === this.selected);
            const next = items[(index + step + items.length) % items.length];
            if (next) this.select(next.name, true);
        },
        edge(last) {
            const items = this.enabled();
            if (items.length) this.select(items[last ? items.length - 1 : 0].name, true);
        },
        onKey(event) {
            const vertical = matchMedia('(min-width: 640px)').matches;
            const keys = {
                [vertical ? 'ArrowDown' : 'ArrowRight']: () => this.move(1),
                [vertical ? 'ArrowUp' : 'ArrowLeft']: () => this.move(-1),
                Home: () => this.edge(false),
                End: () => this.edge(true),
            };
            if (keys[event.key]) {
                event.preventDefault();
                keys[event.key]();
            }
        },
    }"
    x-modelable="selected"
    {{ $attributes->whereStartsWith(['wire:model', 'x-model']) }}
    x-bind:data-ready="ready"
    x-bind:data-switched="switched"
    @if ($default === null) data-auto @endif
    data-nui-settings-modal
    {{ $attributes->except(['id', 'class'])->whereDoesntStartWith(['wire:model', 'x-model']) }}
    {{ $attributes->only('class')->class([
        'nui-settings-modal m-auto w-[calc(100%-2rem)] p-0 bg-base-100 text-base-content rounded-nui shadow-nui border-[length:var(--nui-border-width)] border-base-300 overflow-hidden',
        'backdrop:bg-black/45 backdrop:backdrop-blur-[2px]',
        'open:flex open:flex-col open:animate-[nui-dialog-in_180ms_cubic-bezier(0.16,1,0.3,1)]',
        'max-h-[min(86dvh,44rem)]',
        $widthClass,
        $mobile === 'sheet' ? 'max-sm:mb-0 max-sm:mx-0 max-sm:mt-auto max-sm:w-full max-sm:max-w-none max-sm:max-h-[92dvh] max-sm:rounded-b-none max-sm:border-x-0 max-sm:border-b-0 max-sm:pb-[env(safe-area-inset-bottom)] max-sm:open:animate-[nui-sheet-up_340ms_cubic-bezier(0.16,1,0.3,1)]' : '',
    ]) }}>

    @if ($mobile === 'sheet')
        <span aria-hidden="true" class="sm:hidden block mx-auto mt-2.5 -mb-1 h-1 w-10 shrink-0 rounded-full bg-base-content/20"></span>
    @endif

    <header class="flex shrink-0 items-start justify-between gap-4 border-b border-base-content/[0.08] px-6 py-4">
        <div class="min-w-0 flex-1">
            @if ($title)
                <h2 id="{{ $id }}-title" class="text-lg font-semibold leading-snug text-base-content">{{ $title }}</h2>
            @endif
            @if ($description)
                <p id="{{ $id }}-description" class="mt-0.5 text-sm text-base-content/65">{{ $description }}</p>
            @endif
        </div>
        <x-nui::form method="dialog">
            <x-nui::button variant="ghost" size="sm" type="submit" aria-label="Fechar"
                class="!p-0 -mr-2 -mt-0.5 grid place-items-center !size-8 rounded-[calc(var(--nui-radius)-4px)] text-base-content/55 hover:text-base-content hover:bg-base-content/[0.06] focus-visible:outline-2 focus-visible:outline-primary">
                <svg aria-hidden="true" class="size-4" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"><path d="m4 4 8 8M12 4l-8 8" /></svg>
            </x-nui::button>
        </x-nui::form>
    </header>

    <div class="flex min-h-0 flex-1 flex-col sm:grid sm:grid-cols-[15rem_minmax(0,1fr)]">
        @if ($items->isNotEmpty() || isset($sidebarFooter))
            <aside class="flex shrink-0 flex-col border-b border-base-content/[0.08] bg-base-200/45 sm:min-h-0 sm:border-b-0 sm:border-r">
                @if ($searchable && $items->count() > 1)
                    <div class="px-3 pt-3 pb-2 max-sm:pb-1.5">
                        <x-nui::input id="{{ $id }}-search" type="search" x-model="query"
                            :placeholder="$searchPlaceholder" aria-label="{{ $searchPlaceholder }}"
                            class="w-full [&_[data-slot=box]]:!min-h-8 [&_input]:!py-1 [&_input]:text-xs">
                            <x-slot:leading>
                                <svg aria-hidden="true" class="!size-3.5" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"><circle cx="7" cy="7" r="4.5" /><path d="m10.5 10.5 3 3" /></svg>
                            </x-slot:leading>
                        </x-nui::input>
                    </div>
                @endif

                <div data-lenis-prevent x-ref="list" role="tablist" aria-label="{{ $title ?? 'Seções de configurações' }}"
                    x-on:keydown="onKey($event)"
                    class="nui-settings-nav flex gap-1 overflow-x-auto px-3 py-2.5 sm:flex-1 sm:flex-col sm:overflow-x-hidden sm:overflow-y-auto [scrollbar-width:thin]">
                    @foreach ($grouped as $groupLabel => $groupItems)
                        @if ($groupLabel !== '')
                            <p x-show="groupHasMatches(@js($groupLabel))"
                                class="hidden px-2.5 pt-3 pb-1 text-[0.6875rem] font-semibold tracking-wider text-base-content/50 uppercase first:pt-0.5 sm:block">
                                {{ $groupLabel }}
                            </p>
                        @endif

                        @foreach ($groupItems as $item)
                            @php
                                $isSelected = $item['name'] === $selected;
                                $slug = $key($item['name']);
                            @endphp
                            <x-nui::button variant="ghost" size="sm" role="tab"
                                data-name="{{ $item['name'] }}"
                                id="{{ $id }}-tab-{{ $slug }}"
                                aria-controls="{{ $id }}-panel-{{ $slug }}"
                                aria-selected="{{ $isSelected ? 'true' : 'false' }}"
                                tabindex="{{ $isSelected ? '0' : '-1' }}"
                                x-show="matches(@js($item['name']))"
                                x-bind:aria-selected="(selected === @js($item['name'])).toString()"
                                x-bind:tabindex="selected === @js($item['name']) ? 0 : -1"
                                x-on:click="select(@js($item['name']))"
                                :disabled="$item['disabled']"
                                x-bind:class="selected === @js($item['name']) ? 'nui-settings-tab-active !bg-base-100 !text-primary !border-base-300 shadow-nui' : '!border-transparent text-base-content/70 hover:text-base-content'"
                                class="nui-settings-tab !h-auto min-h-9 shrink-0 !justify-start gap-2.5 !px-2.5 !py-2 text-left text-sm sm:w-full">
                                @if ($item['icon'])
                                    <span aria-hidden="true" class="flex size-4 shrink-0 items-center justify-center [&_svg]:size-4">{!! $item['icon'] !!}</span>
                                @endif
                                <span class="flex min-w-0 flex-1 flex-col">
                                    <span class="truncate font-medium">{{ $item['label'] }}</span>
                                    @if ($item['description'])
                                        <span class="hidden truncate text-[0.6875rem] font-normal text-base-content/55 sm:block">{{ $item['description'] }}</span>
                                    @endif
                                </span>
                                @if (filled($item['badge']))
                                    <x-nui::tag size="sm" variant="neutral" class="ml-auto shrink-0">{{ $item['badge'] }}</x-nui::tag>
                                @endif
                            </x-nui::button>
                        @endforeach
                    @endforeach

                    <p x-show="visible().length === 0" x-cloak role="status"
                        class="px-2.5 py-4 text-center text-xs text-base-content/55">
                        Nenhuma seção encontrada para “<span x-text="query"></span>”.
                    </p>
                </div>

                @isset($sidebarFooter)
                    <div class="hidden border-t border-base-content/[0.08] p-3 text-xs text-base-content/65 sm:block">
                        {{ $sidebarFooter }}
                    </div>
                @endisset
            </aside>
        @endif

        <div data-lenis-prevent class="nui-settings-panels min-h-0 min-w-0 flex-1 overflow-y-auto p-6 text-sm leading-relaxed">
            {{ $slot }}
        </div>
    </div>

    @isset($actions)
        <x-nui::form method="dialog" class="flex shrink-0 flex-wrap items-center justify-end gap-2 border-t border-base-content/[0.08] bg-base-200/60 px-6 py-4">
            <x-nui::button type="submit" variant="ghost">Cancelar</x-nui::button>
            {{ $actions }}
        </x-nui::form>
    @endisset
</dialog>
