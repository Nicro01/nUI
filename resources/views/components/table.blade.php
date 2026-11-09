@props([
    'columns' => [],
    'rows' => [],
    'rowKey' => 'id',
    'caption' => null,
    'label' => 'Tabela',
    'description' => null,
    'variant' => 'default',
    'striped' => false,
    'hover' => false,
    'dense' => false,
    'sticky' => false,
    'selectable' => false,
    'name' => null,
    'selected' => [],
    'empty' => 'Nenhum registro para exibir.',
])

@php
    $id = $attributes->get('id') ?? 'nui-table-'.Str::random(6);

    $normalizedColumns = [];
    foreach ($columns as $key => $column) {
        $column = is_array($column) ? $column : ['key' => is_string($key) ? $key : (string) $column, 'label' => (string) $column];
        $column['key'] = (string) ($column['key'] ?? $key);
        $column['numeric'] = (bool) ($column['numeric'] ?? false);
        $normalizedColumns[] = array_replace([
            'label' => $column['key'],
            'sortable' => false,
            'align' => $column['numeric'] ? 'end' : 'start',
            'tags' => [],
        ], $column);
    }

    $rowList = collect($rows instanceof \Illuminate\Contracts\Pagination\Paginator ? $rows->items() : $rows)->values()->all();
    $selectedKeys = array_map('strval', collect($selected)->all());
    $columnCount = count($normalizedColumns) + ($selectable ? 1 : 0);
    $hasHeader = $caption || $description || $selectable || isset($actions);
    $captionId = $id.'-caption';
@endphp

{{--
    Data table on a native <table>.
    Data mode: columns (key, label, sortable, numeric, align, tags) + rows (arrays or models). A cell value can be
    ['value' => 1290.5, 'label' => 'R$ 1.290,50'] to sort by one thing and show another. Sorting happens in the
    browser and reorders the server-rendered rows; selectable adds native checkboxes (name / wire:model / x-model).
    Composition mode: write <thead>/<tbody> in the slot and use <x-nui::table.column> for server-side sorting.
    Slots: actions (beside the caption), footer (below the table: totals, pagination).
--}}
<div {{ $attributes->whereDoesntStartWith(['wire:model', 'x-model'])->except('id')->class([
        'flex w-full min-w-0 flex-col overflow-hidden text-base-content',
        $variant === 'plain'
            ? ''
            : 'rounded-nui bg-base-100 border border-base-300',
    ]) }}
    id="{{ $id }}" data-nui-table data-variant="{{ $variant }}"
    @if ($striped) data-striped @endif @if ($hover) data-hover @endif
    @if ($dense) data-dense @endif @if ($sticky) data-sticky @endif
    x-data="{
        sortKey: null,
        sortDir: 'none',
        sortStatus: '',
        count: 0,
        total: 0,
        bulk: false,
        fadeStart: 0,
        fadeEnd: 0,
        scrolls: false,
        init() {
            this.total = this.boxes().length;
            this.sync(false);
            this.measure();
            this.resizeObserver = new ResizeObserver(() => this.measure());
            this.resizeObserver.observe(this.$refs.scroll);
        },
        destroy() { this.resizeObserver?.disconnect(); },
        measure() {
            const el = this.$refs.scroll;
            if (!el) return;
            this.scrolls = el.scrollWidth > el.clientWidth + 1;
            this.fadeStart = Math.min(el.scrollLeft, 24);
            this.fadeEnd = Math.min(el.scrollWidth - el.clientWidth - el.scrollLeft, 24);
        },
        ariaSort(key) {
            return this.sortKey === key ? this.sortDir : 'none';
        },
        sortColumn(th) {
            const index = [...th.parentElement.children].indexOf(th);
            const body = this.$root.querySelector('tbody');
            const text = row => (row.cells[index]?.dataset.sort ?? row.cells[index]?.textContent ?? '').trim();
            const number = value => value.replace(/[^\d,.-]/g, '').replace(/\./g, '').replace(',', '.');
            [...body.rows].forEach((row, i) => row.dataset.index ??= i);
            const numeric = [...body.rows].every(row => text(row) === '' || (number(text(row)) !== '' && ! isNaN(number(text(row)))));
            this.sort('col-' + index, th.textContent.trim(), numeric, row => numeric ? number(text(row)) : text(row), body);
            th.parentElement.querySelectorAll('th[aria-sort]').forEach(header => header.setAttribute('aria-sort', header === th ? this.ariaSort('col-' + index) : 'none'));
        },
        sort(key, label, numeric, valueOf = null, body = null) {
            this.sortDir = this.sortKey !== key ? 'ascending' : ({ ascending: 'descending', descending: 'none', none: 'ascending' })[this.sortDir];
            this.sortKey = this.sortDir === 'none' ? null : key;
            body ??= this.$refs.body;
            const rows = [...body.querySelectorAll(':scope > tr[data-index]')];
            const before = new Map(rows.map(row => [row, row.getBoundingClientRect().top]));
            valueOf ??= row => row.querySelector(':scope > [data-col=\'' + CSS.escape(key) + '\']')?.dataset.sort ?? '';
            const direction = this.sortDir === 'descending' ? -1 : 1;
            rows.sort((a, b) => {
                if (! this.sortKey) return a.dataset.index - b.dataset.index;
                const x = valueOf(a), y = valueOf(b);
                if (x === '' || y === '') return (x === '') - (y === '') || a.dataset.index - b.dataset.index;
                const order = numeric ? Number(x) - Number(y) : x.localeCompare(y, 'pt-BR', { numeric: true, sensitivity: 'base' });
                return order * direction || a.dataset.index - b.dataset.index;
            });
            body.append(...rows);
            if (! matchMedia('(prefers-reduced-motion: reduce)').matches) {
                rows.forEach(row => {
                    const shift = before.get(row) - row.getBoundingClientRect().top;
                    if (shift) row.animate([{ transform: 'translateY(' + shift + 'px)' }, { transform: 'none' }], { duration: 320, easing: 'cubic-bezier(0.16, 1, 0.3, 1)' });
                });
            }
            this.sortStatus = this.sortKey
                ? 'Ordenado por ' + label + ', ' + (this.sortDir === 'ascending' ? 'crescente' : 'decrescente') + '.'
                : 'Ordem original restaurada.';
            this.$dispatch('table-sorted', { column: this.sortKey, direction: this.sortKey ? (this.sortDir === 'ascending' ? 'asc' : 'desc') : null });
        },
        boxes() {
            return [...this.$root.querySelectorAll('input[data-nui-table-select]:not(:disabled)')];
        },
        sync(announce = true) {
            if (this.bulk) return;
            const keys = this.boxes().filter(box => box.checked).map(box => box.value);
            this.count = keys.length;
            if (announce) this.$dispatch('table-selection', keys);
        },
        toggleAll(on) {
            this.bulk = true;
            this.boxes().forEach(box => {
                if (box.checked !== on) {
                    box.checked = on;
                    box.dispatchEvent(new Event('change', { bubbles: true }));
                }
            });
            this.bulk = false;
            this.sync();
        },
    }">
    @if ($hasHeader)
        <div data-nui-table-header class="flex flex-wrap items-center gap-x-4 gap-y-2 {{ $variant === 'plain' ? 'pb-3' : 'px-4 py-3.5 border-b border-base-content/10' }}">
            @if ($caption || $description)
                <div class="min-w-0 flex-1">
                    @if ($caption)<p id="{{ $captionId }}" class="font-semibold leading-snug [overflow-wrap:anywhere]">{{ $caption }}</p>@endif
                    @if ($description)<p class="mt-0.5 text-sm text-base-content/70 [overflow-wrap:anywhere]">{{ $description }}</p>@endif
                </div>
            @endif
            @if ($selectable)
                <p class="text-sm text-base-content/70 tabular-nums" aria-live="polite"
                    x-text="count ? count + ' de ' + total + (count === 1 ? ' selecionada' : ' selecionadas') : 'Nenhuma linha selecionada'">Nenhuma linha selecionada</p>
            @endif
            @isset($actions)
                <div class="flex flex-wrap items-center gap-2 {{ $caption || $description ? '' : 'ml-auto' }}">{{ $actions }}</div>
            @endisset
        </div>
    @endif

    <div x-ref="scroll" data-nui-table-scroll data-lenis-prevent role="region" class="min-h-0 flex-1 overflow-auto"
        @if ($caption) aria-labelledby="{{ $captionId }}" @else aria-label="{{ $label }}" @endif
        x-bind:tabindex="scrolls ? 0 : null"
        x-bind:style="{ '--nui-fade-start': fadeStart + 'px', '--nui-fade-end': fadeEnd + 'px' }"
        x-on:scroll.passive="measure()">
        <table @if ($caption) aria-labelledby="{{ $captionId }}" @else aria-label="{{ $label }}" @endif>
            @if (count($normalizedColumns) > 0)
                <thead>
                    <tr>
                        @if ($selectable)
                            <th scope="col" data-nui-table-check>
                                <x-nui::checkbox aria-label="Selecionar todas as linhas"
                                    x-bind:checked="total > 0 && count === total"
                                    x-bind:disabled="total === 0"
                                    x-effect="$el.indeterminate = count > 0 && count < total"
                                    x-on:change="toggleAll($el.checked)" />
                            </th>
                        @endif
                        @foreach ($normalizedColumns as $column)
                            @if ($column['sortable'])
                                <x-nui::table.column sortable :align="$column['align']"
                                    x-bind:aria-sort="ariaSort({{ Js::from($column['key']) }})"
                                    x-on:click="sort({{ Js::from($column['key']) }}, {{ Js::from($column['label']) }}, {{ Js::from($column['numeric']) }})">
                                    {{ $column['label'] }}
                                </x-nui::table.column>
                            @else
                                <x-nui::table.column :align="$column['align']">{{ $column['label'] }}</x-nui::table.column>
                            @endif
                        @endforeach
                    </tr>
                </thead>
                <tbody x-ref="body" x-on:change="if ($event.target.matches('[data-nui-table-select]')) sync()">
                    @foreach ($rowList as $index => $row)
                        @php
                            $rowId = (string) (data_get($row, $rowKey) ?? $index);
                            $rowLabel = data_get($row, $normalizedColumns[0]['key']);
                        @endphp
                        <tr data-nui-row data-index="{{ $index }}" wire:key="{{ $id }}-{{ $rowId }}">
                            @if ($selectable)
                                <td data-nui-table-check>
                                    <x-nui::checkbox data-nui-table-select :value="$rowId" :name="$name ? $name.'[]' : null"
                                        :checked="in_array($rowId, $selectedKeys, true)"
                                        aria-label="Selecionar {{ is_array($rowLabel) ? ($rowLabel['label'] ?? $rowLabel['value'] ?? $rowId) : ($rowLabel ?? $rowId) }}"
                                        {{ $attributes->whereStartsWith(['wire:model', 'x-model']) }} />
                                </td>
                            @endif
                            @foreach ($normalizedColumns as $columnIndex => $column)
                                @php
                                    $raw = data_get($row, $column['key']);
                                    $display = (string) (is_array($raw) ? ($raw['label'] ?? $raw['value'] ?? '') : (is_bool($raw) ? ($raw ? 'Sim' : 'Não') : $raw));
                                    $sortValue = (string) (is_array($raw) ? ($raw['value'] ?? $display) : (is_bool($raw) ? (int) $raw : $raw));
                                    $cellTag = $columnIndex === 0 ? 'th' : 'td';
                                @endphp
                                <{{ $cellTag }} @if ($columnIndex === 0) scope="row" @endif data-col="{{ $column['key'] }}"
                                    @if ($column['sortable']) data-sort="{{ $sortValue }}" @endif
                                    @if ($column['align'] !== 'start') data-align="{{ $column['align'] }}" @endif>
                                    @if (isset($column['tags'][$display]))
                                        <x-nui::tag size="sm" dot :variant="$column['tags'][$display]">{{ $display }}</x-nui::tag>
                                    @else
                                        {{ $display }}
                                    @endif
                                </{{ $cellTag }}>
                            @endforeach
                        </tr>
                    @endforeach
                    @if (count($rowList) === 0)
                        <tr>
                            <td colspan="{{ $columnCount }}" data-nui-table-empty><span role="status">{{ $empty }}</span></td>
                        </tr>
                    @endif
                </tbody>
            @endif
            {{ $slot }}
        </table>
    </div>

    <p class="sr-only" aria-live="polite" x-text="sortStatus"></p>

    @isset($footer)
        <div data-nui-table-footer class="flex flex-wrap items-center gap-3 text-sm text-base-content/70 {{ $variant === 'plain' ? 'pt-3' : 'px-4 py-3 border-t border-base-content/10' }}">{{ $footer }}</div>
    @endisset
</div>
