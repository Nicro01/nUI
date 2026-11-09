@props([
    'columns' => [],
    'rows' => [],
    'rowKey' => 'id',
    'caption' => null,
    'label' => 'Grade de dados',
    'description' => null,
    'variant' => 'default',
    'striped' => false,
    'hover' => true,
    'dense' => false,
    'sticky' => true,
    'searchable' => true,
    'searchPlaceholder' => 'Buscar nos registros…',
    'filterable' => true,
    'selectable' => false,
    'editable' => false,
    'readonly' => false,
    'columnToggle' => true,
    'densityToggle' => true,
    'exportable' => false,
    'exportFilename' => 'dados.csv',
    'paginated' => true,
    'perPage' => 10,
    'perPageOptions' => [5, 10, 25, 50],
    'groupBy' => null,
    'groupable' => false,
    'empty' => 'Nenhum registro encontrado.',
])

@php
    $id = $attributes->get('id') ?? 'nui-grid-'.Str::random(6);
    $locked = $readonly || (bool) $attributes->get('disabled');
    $canEdit = $editable && ! $locked;
    $captionId = $id.'-caption';

    $normalizedColumns = [];
    $seenKeys = [];
    foreach ($columns as $key => $column) {
        $column = is_array($column) ? $column : ['key' => is_string($key) ? $key : (string) $column, 'label' => (string) $column];
        $colKey = (string) ($column['key'] ?? $key);
        if ($colKey === '' || isset($seenKeys[$colKey])) {
            throw new InvalidArgumentException('Data grid: cada coluna precisa de um key único.');
        }
        $seenKeys[$colKey] = true;

        $type = (string) ($column['type'] ?? (($column['numeric'] ?? false) ? 'number' : 'text'));
        $numeric = (bool) ($column['numeric'] ?? in_array($type, ['number', 'currency', 'percent'], true));
        $options = collect($column['options'] ?? [])->map(fn ($opt, $optKey) => is_array($opt)
            ? ['value' => (string) ($opt['value'] ?? $optKey), 'label' => (string) ($opt['label'] ?? $opt['value'] ?? $optKey)]
            : ['value' => (string) (array_is_list($column['options'] ?? []) ? $opt : $optKey), 'label' => (string) $opt]
        )->values()->all();

        $normalizedColumns[] = [
            'key' => $colKey,
            'label' => (string) ($column['label'] ?? Str::headline($colKey)),
            'type' => $type,
            'numeric' => $numeric,
            'sortable' => (bool) ($column['sortable'] ?? true),
            'filterable' => (bool) ($column['filterable'] ?? false),
            'editable' => (bool) ($column['editable'] ?? false),
            'align' => (string) ($column['align'] ?? ($numeric ? 'end' : 'start')),
            'pin' => in_array($column['pin'] ?? null, ['start', 'end'], true) ? $column['pin'] : null,
            'hidden' => (bool) ($column['hidden'] ?? false),
            'hideable' => (bool) ($column['hideable'] ?? true),
            'width' => isset($column['width']) ? (int) $column['width'] : null,
            'minWidth' => max(72, (int) ($column['minWidth'] ?? ($numeric ? 104 : 136))),
            'summary' => in_array($column['summary'] ?? null, ['sum', 'avg', 'min', 'max', 'count'], true) ? $column['summary'] : null,
            'prefix' => (string) ($column['prefix'] ?? ($type === 'currency' ? 'R$ ' : '')),
            'suffix' => (string) ($column['suffix'] ?? ($type === 'percent' ? '%' : '')),
            'tags' => (array) ($column['tags'] ?? []),
            'options' => $options,
        ];
    }

    $rawRows = collect($rows instanceof \Illuminate\Contracts\Pagination\Paginator ? $rows->items() : $rows)->values()->all();
    $normalizedRows = [];
    foreach ($rawRows as $index => $row) {
        $item = is_array($row) ? $row : (method_exists($row, 'toArray') ? $row->toArray() : (array) $row);
        $item[$rowKey] = (string) ($item[$rowKey] ?? ($index + 1));
        $normalizedRows[] = $item;
    }

    // Build filter fields for <x-nui::filter> from columns marked filterable
    $filterFields = [];
    if ($filterable) {
        foreach ($normalizedColumns as $col) {
            if (! $col['filterable']) {
                continue;
            }
            if ($col['numeric']) {
                $filterFields[] = [
                    'name' => $col['key'],
                    'label' => $col['label'],
                    'type' => 'range',
                    'prefix' => $col['prefix'],
                    'suffix' => $col['suffix'],
                ];
            } else {
                $opts = $col['options'];
                if (! count($opts)) {
                    $unique = [];
                    foreach ($normalizedRows as $r) {
                        $val = data_get($r, $col['key']);
                        $rawVal = is_array($val) ? ($val['value'] ?? $val['label'] ?? '') : $val;
                        if (is_bool($rawVal)) {
                            $strVal = $rawVal ? '1' : '0';
                            $lblVal = $rawVal ? 'Sim' : 'Não';
                        } else {
                            $strVal = (string) $rawVal;
                            $lblVal = (string) (is_array($val) ? ($val['label'] ?? $strVal) : $strVal);
                        }
                        if ($strVal !== '' && ! isset($unique[$strVal])) {
                            $unique[$strVal] = ['value' => $strVal, 'label' => $lblVal];
                        }
                    }
                    $opts = array_values($unique);
                }
                if (count($opts)) {
                    $filterFields[] = [
                        'name' => $col['key'],
                        'label' => $col['label'],
                        'type' => $col['type'] === 'boolean' ? 'single' : 'multiple',
                        'options' => $opts,
                    ];
                }
            }
        }
    }

    $filterInitial = ['q' => ''];
    foreach ($filterFields as $f) {
        $filterInitial[$f['name']] = match ($f['type']) {
            'single' => null,
            'range' => ['min' => null, 'max' => null],
            default => [],
        };
    }

    $groupOptions = ['' => 'Sem agrupamento'];
    foreach ($normalizedColumns as $col) {
        if (! $col['numeric']) {
            $groupOptions[$col['key']] = $col['label'];
        }
    }

    $perPageSelectOptions = [];
    foreach ($perPageOptions as $opt) {
        $n = max(1, (int) $opt);
        $perPageSelectOptions[(string) $n] = $n.' / pág.';
    }
    $initialPerPage = max(1, (int) $perPage);
    if (! isset($perPageSelectOptions[(string) $initialPerPage])) {
        $perPageSelectOptions[(string) $initialPerPage] = $initialPerPage.' / pág.';
    }

    $hasSummaries = collect($normalizedColumns)->contains(fn (array $col) => $col['summary'] !== null);
    $hasViewMenu = $columnToggle || $densityToggle || ($groupable && count($groupOptions) > 1);
    $hasHeader = $caption || $description || isset($actions);
    $hasControls = $searchable || count($filterFields) > 0 || $hasViewMenu || $exportable;
    $hasToolbar = $hasHeader || $hasControls;
@endphp

{{--
    Advanced Data Grid built on native <table> and composed with nUI primitives:
    Filter, Input, Number, Select, Checkbox, Toggle, Button, Menu, Tag, Modal, Pagination.
    Supports multi-column sorting (Shift+Click), faceted filtering + search, column visibility & resizing,
    sticky pinned columns, row grouping, inline cell editing, bulk selection, summary aggregations, and CSV export.
--}}
<div {{ $attributes->whereDoesntStartWith(['wire:model', 'x-model'])->except(['id', 'disabled'])->class([
        'nui-data-grid flex w-full min-w-0 flex-col overflow-hidden text-base-content',
        $variant === 'plain'
            ? ''
            : 'rounded-nui bg-base-100 shadow-nui border-[length:var(--nui-border-width)] border-base-300 border-b-[3px] border-b-base-content/20',
    ]) }}
    id="{{ $id }}" role="region"
    @if ($caption) aria-labelledby="{{ $captionId }}" @else aria-label="{{ $label }}" @endif
    data-nui-table data-nui-datagrid data-variant="{{ $variant }}"
    @if ($striped) data-striped @endif @if ($hover) data-hover @endif
    x-bind:data-dense="dense ? '' : null"
    @if ($sticky) data-sticky @endif
    x-data="{
        rows: @js($normalizedRows),
        columns: @js($normalizedColumns),
        rowKey: @js($rowKey),
        filters: @js($filterInitial),
        sorts: [],
        selected: [],
        dense: @js((bool) $dense),
        paginated: @js((bool) $paginated),
        page: 1,
        perPage: @js((string) $initialPerPage),
        groupBy: @js((string) ($groupBy ?? '')),
        collapsedGroups: [],
        selectable: @js((bool) $selectable),
        canEdit: @js($canEdit),
        editing: null,
        editRow: null,
        editDraft: {},
        fadeStart: 0,
        fadeEnd: 0,
        scrolls: false,
        status: '',
        resizing: null,
        init() {
            this.columns.forEach(col => {
                if (col.width) col.currentWidth = col.width;
            });
            this.$watch('filters', () => { this.page = 1; }, { deep: true });
            this.$watch('perPage', () => { this.page = 1; });
            this.$watch('groupBy', () => { this.page = 1; this.collapsedGroups = []; });
            this.$nextTick(() => {
                this.measure();
                if (this.$refs.scroll && typeof ResizeObserver !== 'undefined') {
                    new ResizeObserver(() => this.measure()).observe(this.$refs.scroll);
                }
            });
        },
        measure() {
            const el = this.$refs.scroll;
            if (!el) return;
            this.scrolls = el.scrollWidth > el.clientWidth + 1;
            this.fadeStart = Math.min(el.scrollLeft, 24);
            this.fadeEnd = Math.min(Math.max(0, el.scrollWidth - el.clientWidth - el.scrollLeft), 24);
        },
        visibleColumns() {
            return this.columns.filter(col => !col.hidden);
        },
        totalColSpan() {
            return this.visibleColumns().length + (this.selectable ? 1 : 0) + (this.canEdit ? 1 : 0);
        },
        rawOf(row, key) {
            const cell = row?.[key];
            if (cell !== null && typeof cell === 'object' && !Array.isArray(cell)) {
                return cell.value ?? cell.label ?? '';
            }
            return cell ?? '';
        },
        displayOf(row, col) {
            const cell = row?.[col.key];
            if (cell !== null && typeof cell === 'object' && !Array.isArray(cell) && cell.label !== undefined) {
                return String(cell.label);
            }
            const val = this.rawOf(row, col.key);
            if (val === null || val === undefined || val === '') return '—';
            if (col.type === 'boolean' || typeof val === 'boolean') {
                return (val === true || val === 1 || val === '1' || val === 'true') ? 'Sim' : 'Não';
            }
            if (col.options && col.options.length) {
                const match = col.options.find(opt => String(opt.value) === String(val));
                if (match) return match.label;
            }
            if (col.type === 'currency' && !isNaN(Number(val))) {
                return Number(val).toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
            }
            if (col.type === 'percent' && !isNaN(Number(val))) {
                return Number(val).toLocaleString('pt-BR', { maximumFractionDigits: 2 }) + '%';
            }
            if (col.type === 'number' && !isNaN(Number(val)) && (col.prefix || col.suffix)) {
                return col.prefix + Number(val).toLocaleString('pt-BR') + col.suffix;
            }
            if (col.type === 'date' && /^\d{4}-\d{2}-\d{2}$/.test(String(val))) {
                const [y, m, d] = String(val).split('-').map(Number);
                return new Date(y, m - 1, d).toLocaleDateString('pt-BR');
            }
            return String(val);
        },
        tagVariant(row, col) {
            if (!col.tags || Object.keys(col.tags).length === 0) return null;
            const raw = String(this.rawOf(row, col.key));
            const disp = this.displayOf(row, col);
            return col.tags[raw] ?? col.tags[disp] ?? null;
        },
        normalizeText(text) {
            return String(text ?? '').normalize('NFD').replace(/\p{Diacritic}/gu, '').toLowerCase();
        },
        get filteredRows() {
            const q = this.normalizeText(this.filters.q || '').trim();
            return this.rows.filter(row => {
                if (q) {
                    const hay = this.columns.map(col => this.displayOf(row, col) + ' ' + this.rawOf(row, col.key)).join(' ');
                    if (!this.normalizeText(hay).includes(q)) return false;
                }
                for (const col of this.columns) {
                    if (!col.filterable) continue;
                    const f = this.filters[col.key];
                    if (f === undefined || f === null || f === '') continue;
                    const val = this.rawOf(row, col.key);
                    if (col.numeric && typeof f === 'object' && !Array.isArray(f)) {
                        const num = Number(val);
                        if (f.min !== null && f.min !== '' && num < Number(f.min)) return false;
                        if (f.max !== null && f.max !== '' && num > Number(f.max)) return false;
                    } else if (Array.isArray(f)) {
                        if (f.length > 0 && !f.map(String).includes(String(val))) return false;
                    } else {
                        const strVal = typeof val === 'boolean' ? (val ? '1' : '0') : String(val);
                        if (String(f) !== '' && strVal !== String(f)) return false;
                    }
                }
                return true;
            });
        },
        get sortedRows() {
            const list = [...this.filteredRows];
            if (!this.sorts.length) return list;
            return list.sort((a, b) => {
                for (const s of this.sorts) {
                    const col = this.columns.find(c => c.key === s.key);
                    if (!col) continue;
                    const x = this.rawOf(a, col.key), y = this.rawOf(b, col.key);
                    if (x === '' || x === null || y === '' || y === null) {
                        if (x === y) continue;
                        return (x === '' || x === null) ? 1 : -1;
                    }
                    const dir = s.dir === 'desc' ? -1 : 1;
                    const order = col.numeric
                        ? (Number(x) - Number(y))
                        : String(x).localeCompare(String(y), 'pt-BR', { numeric: true, sensitivity: 'base' });
                    if (order !== 0) return order * dir;
                }
                return 0;
            });
        },
        get totalPages() {
            if (!this.paginated) return 1;
            const size = Math.max(1, Number(this.perPage) || 10);
            return Math.max(1, Math.ceil(this.sortedRows.length / size));
        },
        get pagedRows() {
            if (!this.paginated) return this.sortedRows;
            const size = Math.max(1, Number(this.perPage) || 10);
            const current = Math.min(Math.max(1, this.page), this.totalPages);
            const start = (current - 1) * size;
            return this.sortedRows.slice(start, start + size);
        },
        get groupedBlocks() {
            const source = this.pagedRows;
            if (!this.groupBy) {
                return [{ key: '__all__', label: null, rows: source }];
            }
            const col = this.columns.find(c => c.key === this.groupBy);
            if (!col) return [{ key: '__all__', label: null, rows: source }];
            const groups = new Map();
            for (const row of source) {
                const label = this.displayOf(row, col);
                if (!groups.has(label)) groups.set(label, []);
                groups.get(label).push(row);
            }
            return [...groups.entries()].map(([label, items]) => ({
                key: col.key + ':' + label,
                columnLabel: col.label,
                label,
                rows: items,
            }));
        },
        toggleGroup(groupKey) {
            this.collapsedGroups = this.collapsedGroups.includes(groupKey)
                ? this.collapsedGroups.filter(k => k !== groupKey)
                : [...this.collapsedGroups, groupKey];
        },
        isGroupOpen(groupKey) {
            return !this.collapsedGroups.includes(groupKey);
        },
        ariaSort(key) {
            const found = this.sorts.find(s => s.key === key);
            if (!found) return 'none';
            return found.dir === 'desc' ? 'descending' : 'ascending';
        },
        sortIndex(key) {
            if (this.sorts.length <= 1) return null;
            const idx = this.sorts.findIndex(s => s.key === key);
            return idx === -1 ? null : idx + 1;
        },
        toggleSort(col, shift = false) {
            if (!col.sortable) return;
            const idx = this.sorts.findIndex(s => s.key === col.key);
            const current = idx !== -1 ? this.sorts[idx].dir : null;
            const nextDir = current === null ? 'asc' : (current === 'asc' ? 'desc' : null);

            if (shift) {
                const copy = [...this.sorts];
                if (idx !== -1) {
                    if (nextDir) copy[idx] = { key: col.key, dir: nextDir };
                    else copy.splice(idx, 1);
                } else if (nextDir) {
                    copy.push({ key: col.key, dir: nextDir });
                }
                this.sorts = copy;
            } else {
                this.sorts = nextDir ? [{ key: col.key, dir: nextDir }] : [];
            }

            this.status = this.sorts.length
                ? 'Ordenado por ' + this.sorts.map(s => {
                    const c = this.columns.find(item => item.key === s.key);
                    return (c?.label || s.key) + ' (' + (s.dir === 'asc' ? 'crescente' : 'decrescente') + ')';
                }).join(', ') + '.'
                : 'Ordem original restaurada.';
            this.$dispatch('datagrid-sorted', { sorts: JSON.parse(JSON.stringify(this.sorts)) });
        },
        idOf(row) {
            return String(row?.[this.rowKey] ?? '');
        },
        isSelected(row) {
            return this.selected.includes(this.idOf(row));
        },
        toggleRow(row, checked) {
            const id = this.idOf(row);
            if (checked) {
                if (!this.selected.includes(id)) this.selected = [...this.selected, id];
            } else {
                this.selected = this.selected.filter(item => item !== id);
            }
            this.$dispatch('datagrid-selection', [...this.selected]);
        },
        get allVisibleSelected() {
            const visible = this.pagedRows;
            return visible.length > 0 && visible.every(row => this.selected.includes(this.idOf(row)));
        },
        get someVisibleSelected() {
            const visible = this.pagedRows;
            return visible.some(row => this.selected.includes(this.idOf(row))) && !this.allVisibleSelected;
        },
        toggleAllVisible(checked) {
            const ids = this.pagedRows.map(row => this.idOf(row));
            if (checked) {
                this.selected = [...new Set([...this.selected, ...ids])];
            } else {
                this.selected = this.selected.filter(id => !ids.includes(id));
            }
            this.$dispatch('datagrid-selection', [...this.selected]);
        },
        clearSelection() {
            this.selected = [];
            this.$dispatch('datagrid-selection', []);
        },
        startEdit(row, col) {
            if (!this.canEdit || !col.editable) return;
            const id = this.idOf(row);
            const raw = this.rawOf(row, col.key);
            this.editing = { rowId: id, key: col.key, value: raw };
        },
        isEditing(row, col) {
            return this.editing && this.editing.rowId === this.idOf(row) && this.editing.key === col.key;
        },
        commitEdit() {
            if (!this.editing) return;
            const { rowId, key, value } = this.editing;
            const col = this.columns.find(c => c.key === key);
            const rowIndex = this.rows.findIndex(r => this.idOf(r) === rowId);
            if (rowIndex !== -1 && col) {
                const parsed = col.numeric ? (value === '' ? 0 : Number(value)) : value;
                const previous = this.rawOf(this.rows[rowIndex], key);
                if (this.rows[rowIndex][key] !== null && typeof this.rows[rowIndex][key] === 'object' && !Array.isArray(this.rows[rowIndex][key])) {
                    this.rows[rowIndex][key].value = parsed;
                    delete this.rows[rowIndex][key].label;
                } else {
                    this.rows[rowIndex][key] = parsed;
                }
                this.rows = [...this.rows];
                if (String(previous) !== String(parsed)) {
                    this.status = (col.label || key) + ' atualizado.';
                    this.$dispatch('datagrid-cell-updated', { rowId, column: key, value: parsed, row: JSON.parse(JSON.stringify(this.rows[rowIndex])) });
                }
            }
            this.editing = null;
        },
        cancelEdit() {
            this.editing = null;
        },
        openRowEditor(row) {
            if (!this.canEdit) return;
            this.editRow = row;
            const draft = {};
            this.columns.forEach(col => {
                if (col.editable) draft[col.key] = this.rawOf(row, col.key);
            });
            this.editDraft = draft;
            this.$refs.editDialog.showModal();
        },
        saveRowEditor() {
            if (!this.editRow) return;
            const rowId = this.idOf(this.editRow);
            const rowIndex = this.rows.findIndex(r => this.idOf(r) === rowId);
            if (rowIndex !== -1) {
                const updated = { ...this.rows[rowIndex] };
                this.columns.forEach(col => {
                    if (!col.editable) return;
                    const raw = this.editDraft[col.key];
                    const parsed = col.numeric ? (raw === '' ? 0 : Number(raw)) : raw;
                    if (updated[col.key] !== null && typeof updated[col.key] === 'object' && !Array.isArray(updated[col.key])) {
                        updated[col.key] = { ...updated[col.key], value: parsed };
                        delete updated[col.key].label;
                    } else {
                        updated[col.key] = parsed;
                    }
                });
                const next = [...this.rows];
                next[rowIndex] = updated;
                this.rows = next;
                this.status = 'Registro #' + rowId + ' atualizado.';
                this.$dispatch('datagrid-row-updated', { rowId, row: JSON.parse(JSON.stringify(updated)) });
            }
            this.$refs.editDialog.close();
        },
        startResize(event, col) {
            const th = event.target.closest('th');
            const startWidth = col.currentWidth || th?.offsetWidth || col.minWidth;
            this.resizing = { key: col.key, startX: event.clientX, startWidth };
            event.target.setPointerCapture?.(event.pointerId);
        },
        moveResize(event) {
            if (!this.resizing) return;
            const col = this.columns.find(c => c.key === this.resizing.key);
            if (!col) return;
            const delta = event.clientX - this.resizing.startX;
            col.currentWidth = Math.max(col.minWidth || 80, Math.round(this.resizing.startWidth + delta));
            this.measure();
        },
        stopResize() {
            this.resizing = null;
        },
        stepWidth(col, delta) {
            const current = col.currentWidth || col.width || col.minWidth || 140;
            col.currentWidth = Math.max(col.minWidth || 80, current + delta);
            this.measure();
        },
        colStyle(col) {
            const styles = {};
            if (col.currentWidth) {
                styles.width = col.currentWidth + 'px';
                styles.minWidth = col.currentWidth + 'px';
            } else if (col.width) {
                styles.width = col.width + 'px';
                styles.minWidth = col.width + 'px';
            }
            return styles;
        },
        summaryOf(col) {
            if (!col.summary) return '';
            const items = this.filteredRows;
            if (!items.length) return '—';
            if (col.summary === 'count') {
                return items.length.toLocaleString('pt-BR') + (items.length === 1 ? ' item' : ' itens');
            }
            const nums = items.map(r => Number(this.rawOf(r, col.key))).filter(n => !isNaN(n));
            if (!nums.length) return '—';
            let result = 0;
            if (col.summary === 'sum') result = nums.reduce((a, b) => a + b, 0);
            else if (col.summary === 'avg') result = nums.reduce((a, b) => a + b, 0) / nums.length;
            else if (col.summary === 'min') result = Math.min(...nums);
            else if (col.summary === 'max') result = Math.max(...nums);

            const prefixLabel = { sum: 'Soma: ', avg: 'Média: ', min: 'Mín: ', max: 'Máx: ' }[col.summary] || '';
            if (col.type === 'currency') {
                return prefixLabel + result.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
            }
            if (col.type === 'percent') {
                return prefixLabel + result.toLocaleString('pt-BR', { maximumFractionDigits: 1 }) + '%';
            }
            return prefixLabel + col.prefix + result.toLocaleString('pt-BR', { maximumFractionDigits: 2 }) + col.suffix;
        },
        exportCsv() {
            const cols = this.visibleColumns();
            const source = this.selected.length
                ? this.sortedRows.filter(r => this.selected.includes(this.idOf(r)))
                : this.sortedRows;
            const quote = String.fromCharCode(34);
            const cell = val => quote + String(val ?? '').replaceAll(quote, quote + quote) + quote;
            const lines = [
                cols.map(c => cell(c.label)).join(';'),
                ...source.map(row => cols.map(c => cell(this.displayOf(row, c))).join(';')),
            ];
            const blob = new Blob([String.fromCharCode(0xFEFF) + lines.join(String.fromCharCode(13, 10))], { type: 'text/csv;charset=utf-8' });
            const link = Object.assign(document.createElement('a'), {
                href: URL.createObjectURL(blob),
                download: @js($exportFilename),
            });
            document.body.append(link);
            link.click();
            link.remove();
            setTimeout(() => URL.revokeObjectURL(link.href), 600);
            this.status = source.length + ' registros exportados para CSV.';
            this.$dispatch('datagrid-exported', { count: source.length, filename: @js($exportFilename) });
        }
    }"
    x-modelable="rows"
    {{ $attributes->whereStartsWith(['wire:model', 'x-model']) }}
    x-on:pointermove.window="if (resizing) moveResize($event)"
    x-on:pointerup.window="if (resizing) stopResize()">

    @if ($hasToolbar)
        <div data-nui-datagrid-toolbar class="flex flex-col gap-3 {{ $variant === 'plain' ? 'pb-3' : 'px-4 py-3.5 border-b border-base-content/10' }}">
            @php
                $gridToolsHtml = '';
            @endphp
            @if ($hasControls)
                @php
                    ob_start();
                @endphp
                <div class="flex flex-wrap items-center gap-2">
                    @if ($groupable && count($groupOptions) > 1)
                        <x-nui::tag x-show="groupBy !== ''" x-cloak size="lg" variant="primary" class="pr-1">
                            <span class="text-xs opacity-75">Grupo:</span>
                            <span class="text-xs font-semibold" x-text="(columns.find(c => c.key === groupBy) || {}).label || groupBy"></span>
                            <x-nui::button variant="ghost" size="sm" x-on:click="groupBy = ''" aria-label="Remover agrupamento"
                                class="!p-0 !text-current grid place-items-center !size-4 rounded-full opacity-70 hover:opacity-100 hover:bg-current/15">
                                <svg aria-hidden="true" class="!size-2.5" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round"><path d="m4 4 8 8M12 4l-8 8" /></svg>
                            </x-nui::button>
                        </x-nui::tag>
                    @endif

                    @if ($hasViewMenu)
                        <x-nui::menu id="{{ $id }}-cols" size="sm" label="Opções de exibição" align="end" class="nui-datagrid-tool">
                            <x-slot:trigger>
                                <svg aria-hidden="true" class="size-4 opacity-70" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M2.5 4.5h11M2.5 8h11M2.5 11.5h11" /><circle cx="5.5" cy="4.5" r="1.5" fill="var(--nui-base-100)" /><circle cx="10.5" cy="8" r="1.5" fill="var(--nui-base-100)" /><circle cx="6.5" cy="11.5" r="1.5" fill="var(--nui-base-100)" /></svg>
                                <span>Exibição</span>
                            </x-slot:trigger>

                            @if ($densityToggle)
                                <x-nui::menu.group label="Apresentação">
                                    <x-nui::menu.checkbox label="Modo compacto"
                                        aria-label="Alternar densidade compacta"
                                        x-bind:checked="dense"
                                        x-on:change="dense = $el.checked; $nextTick(() => measure())" />
                                </x-nui::menu.group>
                            @endif

                            @if ($groupable && count($groupOptions) > 1)
                                @if ($densityToggle)
                                    <x-nui::menu.separator />
                                @endif
                                <x-nui::menu.submenu id="{{ $id }}-group" label="Agrupar por">
                                    @foreach ($groupOptions as $groupKey => $groupLabel)
                                        <x-nui::menu.item :label="$groupLabel"
                                            x-on:click="groupBy = {{ Js::from((string) $groupKey) }}"
                                            x-bind:class="groupBy === {{ Js::from((string) $groupKey) }} && 'nui-menu-active'">
                                            <x-slot:trailing>
                                                <svg x-show="groupBy === {{ Js::from((string) $groupKey) }}" x-cloak aria-hidden="true" class="size-4 text-primary" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m3.5 8.5 3 3 6-6.5" /></svg>
                                            </x-slot:trailing>
                                        </x-nui::menu.item>
                                    @endforeach
                                </x-nui::menu.submenu>
                            @endif

                            @if ($columnToggle)
                                @if ($densityToggle || ($groupable && count($groupOptions) > 1))
                                    <x-nui::menu.separator />
                                @endif
                                <x-nui::menu.group label="Colunas visíveis">
                                    @foreach ($normalizedColumns as $colIndex => $col)
                                        @if ($col['hideable'])
                                            <x-nui::menu.checkbox :label="$col['label']"
                                                x-bind:checked="!columns[{{ $colIndex }}].hidden"
                                                x-bind:disabled="!columns[{{ $colIndex }}].hidden && visibleColumns().length <= 1"
                                                x-on:change="columns[{{ $colIndex }}].hidden = !$el.checked; $nextTick(() => measure())" />
                                        @endif
                                    @endforeach
                                </x-nui::menu.group>
                            @endif
                        </x-nui::menu>
                    @endif

                    @if ($exportable)
                        <x-nui::button size="sm" variant="secondary" class="nui-datagrid-tool" x-on:click="exportCsv()" x-bind:disabled="sortedRows.length === 0">
                            <svg aria-hidden="true" class="size-4 opacity-70" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M8 2.5v7.5m0 0-2.5-2.5M8 10l2.5-2.5M3 11.5v1a1 1 0 0 0 1 1h8a1 1 0 0 0 1-1v-1" /></svg>
                            <span>Exportar CSV</span>
                        </x-nui::button>
                    @endif
                </div>
                @php
                    $gridToolsHtml = ob_get_clean();
                @endphp
            @endif

            @if ($hasHeader)
                <div class="flex flex-wrap items-center justify-between gap-3">
                    @if ($caption || $description)
                        <div class="min-w-0 flex-1">
                            @if ($caption)<p id="{{ $captionId }}" class="font-semibold leading-snug [overflow-wrap:anywhere]">{{ $caption }}</p>@endif
                            @if ($description)<p class="mt-0.5 text-sm text-base-content/70 [overflow-wrap:anywhere]">{{ $description }}</p>@endif
                        </div>
                    @endif

                    @if (isset($actions) || $gridToolsHtml !== '')
                        <div class="flex flex-wrap items-center gap-2 {{ $caption || $description ? '' : 'ml-auto' }}">
                            {{ $actions ?? '' }}
                            {!! $gridToolsHtml !!}
                        </div>
                    @endif
                </div>
            @endif


            @if ($hasControls)
                @if ($searchable || count($filterFields) > 0)
                    <x-nui::filter id="{{ $id }}-filter" x-model="filters"
                        :search="$searchable ? $searchPlaceholder : null"
                        :fields="$filterFields">
                        @unless ($hasHeader)
                            <x-slot:actions>
                                <div class="ml-auto">{!! $gridToolsHtml !!}</div>
                            </x-slot:actions>
                        @endunless
                        <x-slot:summary>
                            <span class="tabular-nums" x-text="filteredRows.length + (filteredRows.length === 1 ? ' registro' : ' registros')"></span>
                        </x-slot:summary>
                    </x-nui::filter>
                @elseif (! $hasHeader)
                    <div class="flex flex-wrap items-center justify-end gap-2">
                        {!! $gridToolsHtml !!}
                    </div>
                @endif
            @endif
        </div>
    @endif

    @if ($selectable)
        <div x-show="selected.length > 0" x-cloak data-nui-datagrid-bulk
            class="flex flex-wrap items-center justify-between gap-3 bg-primary/8 px-4 py-2.5 text-sm border-b border-primary/20">
            <div class="flex items-center gap-2.5">
                <x-nui::tag size="sm" variant="primary"><span class="tabular-nums" x-text="selected.length"></span></x-nui::tag>
                <span class="font-medium text-base-content" x-text="selected.length === 1 ? 'linha selecionada' : 'linhas selecionadas'"></span>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                @isset($bulkActions)
                    {{ $bulkActions }}
                @endisset
                <x-nui::button size="sm" variant="ghost" x-on:click="clearSelection()">Limpar seleção</x-nui::button>
            </div>
        </div>
    @endif

    <div x-ref="scroll" data-nui-table-scroll data-lenis-prevent role="region" class="min-h-0 flex-1 overflow-auto"
        aria-label="{{ $caption ?? $label }}"
        x-bind:tabindex="scrolls ? 0 : null"
        x-bind:style="{ '--nui-fade-start': fadeStart + 'px', '--nui-fade-end': fadeEnd + 'px' }"
        x-on:scroll.passive="measure()">
        <table @if ($caption) aria-labelledby="{{ $captionId }}" @else aria-label="{{ $label }}" @endif>
            <thead>
                <tr>
                    @if ($selectable)
                        <th scope="col" data-nui-table-check data-pin="start">
                            <x-nui::checkbox aria-label="Selecionar linhas da página"
                                x-bind:checked="allVisibleSelected"
                                x-bind:disabled="pagedRows.length === 0"
                                x-effect="$el.indeterminate = someVisibleSelected"
                                x-on:change="toggleAllVisible($el.checked)" />
                        </th>
                    @endif
                    <template x-for="col in visibleColumns()" x-bind:key="col.key">
                        <th scope="col"
                            x-bind:data-col="col.key"
                            x-bind:data-pin="col.pin || null"
                            x-bind:data-align="col.align !== 'start' ? col.align : null"
                            x-bind:aria-sort="col.sortable ? ariaSort(col.key) : null"
                            x-bind:style="colStyle(col)"
                            class="nui-datagrid-th relative select-none">
                            <template x-if="col.sortable">
                                <button type="button" data-nui-table-sort
                                    x-on:click="toggleSort(col, $event.shiftKey)"
                                    x-bind:title="'Ordenar por ' + col.label + ' (Shift+Clique para acumular)'">
                                    <span x-text="col.label"></span>
                                    <span x-show="sortIndex(col.key)" x-cloak
                                        class="inline-grid size-4 place-items-center rounded-full bg-primary/15 text-[0.625rem] font-bold text-primary tabular-nums"
                                        x-text="sortIndex(col.key)"></span>
                                    <svg aria-hidden="true" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                        <path data-up d="m5 6.5 3-3 3 3" />
                                        <path data-down d="m5 9.5 3 3 3-3" />
                                    </svg>
                                </button>
                            </template>
                            <template x-if="!col.sortable">
                                <span x-text="col.label"></span>
                            </template>

                            <span role="separator" aria-orientation="vertical" tabindex="0"
                                x-bind:aria-label="'Redimensionar coluna ' + col.label"
                                x-on:pointerdown.stop.prevent="startResize($event, col)"
                                x-on:keydown.arrow-right.prevent="stepWidth(col, 16)"
                                x-on:keydown.arrow-left.prevent="stepWidth(col, -16)"
                                class="nui-datagrid-resizer"></span>
                        </th>
                    </template>
                    @if ($canEdit)
                        <th scope="col" data-align="end" data-pin="end" class="w-1 whitespace-nowrap">
                            <span class="sr-only">Ações</span>
                        </th>
                    @endif
                </tr>
            </thead>

            <template x-for="group in groupedBlocks" x-bind:key="group.key">
                <tbody class="nui-datagrid-group">
                    <template x-if="group.label !== null">
                        <tr class="nui-datagrid-group-row">
                            <td x-bind:colspan="totalColSpan()" class="!bg-base-200/80 !py-1.5">
                                <x-nui::button variant="ghost" size="sm" x-on:click="toggleGroup(group.key)"
                                    x-bind:aria-expanded="isGroupOpen(group.key).toString()"
                                    class="!h-7 !px-2 !text-xs font-semibold text-base-content">
                                    <svg aria-hidden="true" class="!size-3.5 transition-transform duration-200"
                                        x-bind:class="isGroupOpen(group.key) && 'rotate-90'"
                                        viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="m6 4 4 4-4 4" />
                                    </svg>
                                    <span class="text-base-content/65" x-text="group.columnLabel + ':'"></span>
                                    <span x-text="group.label"></span>
                                    <x-nui::tag size="sm"><span class="tabular-nums" x-text="group.rows.length"></span></x-nui::tag>
                                </x-nui::button>
                            </td>
                        </tr>
                    </template>

                    <template x-for="row in ( isGroupOpen(group.key) ? group.rows : [] )" x-bind:key="idOf(row)">
                        <tr data-nui-row x-bind:data-selected="isSelected(row) ? '' : null">
                            @if ($selectable)
                                <td data-nui-table-check data-pin="start">
                                    <x-nui::checkbox data-nui-table-select
                                        x-bind:value="idOf(row)"
                                        x-bind:checked="isSelected(row)"
                                        x-bind:aria-label="'Selecionar linha ' + idOf(row)"
                                        x-on:change="toggleRow(row, $el.checked)" />
                                </td>
                            @endif

                            <template x-for="(col, colIdx) in visibleColumns()" x-bind:key="col.key">
                                <td x-bind:data-col="col.key"
                                    x-bind:data-pin="col.pin || null"
                                    x-bind:data-align="col.align !== 'start' ? col.align : null"
                                    x-bind:data-editable="(canEdit && col.editable) ? '' : null"
                                    x-bind:class="colIdx === 0 && 'font-medium'"
                                    x-on:dblclick="startEdit(row, col)">

                                    {{-- Read mode --}}
                                    <template x-if="!isEditing(row, col)">
                                        <div class="flex items-center gap-1.5"
                                            x-bind:class="col.align === 'end' ? 'justify-end' : (col.align === 'center' ? 'justify-center' : 'justify-start')">
                                            <template x-if="tagVariant(row, col) === 'primary'"><x-nui::tag size="sm" dot variant="primary"><span x-text="displayOf(row, col)"></span></x-nui::tag></template>
                                            <template x-if="tagVariant(row, col) === 'success'"><x-nui::tag size="sm" dot variant="success"><span x-text="displayOf(row, col)"></span></x-nui::tag></template>
                                            <template x-if="tagVariant(row, col) === 'warning'"><x-nui::tag size="sm" dot variant="warning"><span x-text="displayOf(row, col)"></span></x-nui::tag></template>
                                            <template x-if="tagVariant(row, col) === 'danger'"><x-nui::tag size="sm" dot variant="danger"><span x-text="displayOf(row, col)"></span></x-nui::tag></template>
                                            <template x-if="tagVariant(row, col) === 'neutral'"><x-nui::tag size="sm" dot variant="neutral"><span x-text="displayOf(row, col)"></span></x-nui::tag></template>
                                            <template x-if="!tagVariant(row, col)">
                                                <span class="truncate" x-text="displayOf(row, col)"></span>
                                            </template>
                                            <template x-if="canEdit && col.editable">
                                                <x-nui::button variant="ghost" size="sm" x-on:click="startEdit(row, col)"
                                                    x-bind:aria-label="'Editar ' + col.label + ' da linha ' + idOf(row)"
                                                    class="nui-datagrid-edit-trigger !size-6 !p-0 opacity-0 focus-visible:opacity-100 text-base-content/45 hover:text-primary">
                                                    <svg aria-hidden="true" class="!size-3.5" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="m10.5 2.5 3 3L6 13H3v-3l7.5-7.5Z" /></svg>
                                                </x-nui::button>
                                            </template>
                                        </div>
                                    </template>

                                    {{-- Inline Edit mode composed with x-nui::input --}}
                                    <template x-if="isEditing(row, col)">
                                        <div class="nui-datagrid-inline-editor min-w-[6.5rem]"
                                            x-init="$nextTick(() => { const el = $el.querySelector('input'); el?.focus(); el?.select?.(); })"
                                            x-on:keydown.escape.stop.prevent="cancelEdit()"
                                            x-on:keydown.enter.stop.prevent="commitEdit()">
                                            <x-nui::input x-bind:type="col.numeric ? 'number' : 'text'"
                                                step="any"
                                                x-model="editing.value"
                                                x-on:blur="commitEdit()"
                                                x-bind:aria-label="col.label"
                                                class="[&_[data-slot=box]]:!min-h-8 [&_input]:!py-1 [&_input]:text-sm [&_input]:tabular-nums" />
                                        </div>
                                    </template>
                                </td>
                            </template>

                            @if ($canEdit)
                                <td data-align="end" data-pin="end" class="w-1 whitespace-nowrap">
                                    <x-nui::button size="sm" variant="ghost" class="!h-7 !px-2 !text-xs"
                                        x-on:click="openRowEditor(row)"
                                        x-bind:aria-label="'Editar registro ' + idOf(row)">
                                        Editar
                                    </x-nui::button>
                                </td>
                            @endif
                        </tr>
                    </template>
                </tbody>
            </template>

            <tbody x-show="pagedRows.length === 0" x-cloak>
                <tr>
                    <td x-bind:colspan="totalColSpan()" data-nui-table-empty>
                        <span role="status">{{ $empty }}</span>
                    </td>
                </tr>
            </tbody>

            @if ($hasSummaries)
                <tfoot x-show="filteredRows.length > 0">
                    <tr>
                        @if ($selectable)
                            <td data-pin="start"></td>
                        @endif
                        <template x-for="(col, index) in visibleColumns()" x-bind:key="'foot-' + col.key">
                            <td x-bind:data-align="col.align !== 'start' ? col.align : null"
                                x-bind:data-pin="col.pin || null"
                                class="tabular-nums text-xs font-semibold"
                                x-text="summaryOf(col) || (index === 0 ? 'Totais' : '')"></td>
                        </template>
                        @if ($canEdit)
                            <td data-pin="end"></td>
                        @endif
                    </tr>
                </tfoot>
            @endif
        </table>
    </div>

    <p class="sr-only" aria-live="polite" x-text="status"></p>

    @if ($paginated || isset($footer))
        <div data-nui-table-footer class="flex flex-wrap items-center justify-between gap-3 text-xs text-base-content/70 {{ $variant === 'plain' ? 'pt-3' : 'px-4 py-2.5 border-t border-base-content/10 bg-base-200/35' }}">
            @if ($paginated)
                <div class="flex flex-wrap items-center gap-2.5">
                    <div class="inline-flex items-center gap-1.5 tabular-nums">
                        <span>Mostrando</span>
                        <span class="inline-flex items-center rounded-[calc(var(--nui-radius-field)-2px)] bg-base-200 px-1.5 py-0.5 font-semibold text-base-content ring-1 ring-inset ring-base-content/12"
                            x-text="sortedRows.length ? ((page - 1) * Number(perPage) + 1) + '–' + Math.min(page * Number(perPage), sortedRows.length) : '0'"></span>
                        <span>de</span>
                        <span class="font-semibold text-base-content" x-text="sortedRows.length"></span>
                        <span class="hidden sm:inline" x-text="sortedRows.length === 1 ? 'registro' : 'registros'"></span>
                    </div>
                    <span aria-hidden="true" class="hidden h-4 w-px bg-base-content/12 sm:inline-block"></span>
                    <div class="w-auto min-w-[8.75rem]">
                        <x-nui::select id="{{ $id }}-per-page" size="sm" label="Exibir" label-style="inline" aria-label="Registros por página"
                            :options="$perPageSelectOptions" x-model="perPage" />
                    </div>
                </div>

                <div class="flex items-center gap-1" role="navigation" aria-label="Paginação da grade">
                    <x-nui::button size="sm" variant="ghost" class="max-sm:hidden" x-on:click="page = 1" x-bind:disabled="page <= 1" aria-label="Primeira página">
                        <svg aria-hidden="true" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="m7.5 12-4-4 4-4M12.5 12l-4-4 4-4" /></svg>
                    </x-nui::button>
                    <x-nui::button size="sm" variant="ghost" x-on:click="page = Math.max(1, page - 1)" x-bind:disabled="page <= 1" aria-label="Página anterior">
                        <svg aria-hidden="true" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="m10 12-4-4 4-4" /></svg>
                        <span class="hidden sm:inline">Anterior</span>
                    </x-nui::button>
                    <span class="inline-flex h-8 items-center gap-1 rounded-nui-field bg-base-200/70 px-2.5 text-xs tabular-nums text-base-content/70 ring-1 ring-inset ring-base-content/10">
                        <span>Pág.</span>
                        <span class="font-semibold text-base-content" x-text="page"></span>
                        <span>/</span>
                        <span class="font-semibold text-base-content" x-text="totalPages"></span>
                    </span>
                    <x-nui::button size="sm" variant="ghost" x-on:click="page = Math.min(totalPages, page + 1)" x-bind:disabled="page >= totalPages" aria-label="Próxima página">
                        <span class="hidden sm:inline">Próxima</span>
                        <svg aria-hidden="true" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="m6 12 4-4-4-4" /></svg>
                    </x-nui::button>
                    <x-nui::button size="sm" variant="ghost" class="max-sm:hidden" x-on:click="page = totalPages" x-bind:disabled="page >= totalPages" aria-label="Última página">
                        <svg aria-hidden="true" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="m3.5 12 4-4-4-4M8.5 12l4-4-4-4" /></svg>
                    </x-nui::button>
                </div>
            @endif

            @isset($footer)
                <div class="flex flex-wrap items-center gap-3">{{ $footer }}</div>
            @endisset
        </div>
    @endif

    @if ($canEdit)
        <x-nui::modal :id="$id.'-edit'" title="Editar registro" size="md" x-ref="editDialog">
            <div class="flex flex-col gap-4">
                @foreach ($normalizedColumns as $col)
                    @continue(! $col['editable'])
                    @if ($col['type'] === 'boolean')
                        <x-nui::toggle :id="$id.'-edit-'.$col['key']" :label="$col['label']"
                            x-model="editDraft[{{ Js::from($col['key']) }}]" />
                    @elseif (count($col['options']) > 0)
                        <x-nui::select :id="$id.'-edit-'.$col['key']" :label="$col['label']"
                            :options="$col['options']" x-model="editDraft[{{ Js::from($col['key']) }}]" />
                    @elseif ($col['type'] === 'date')
                        <x-nui::date :id="$id.'-edit-'.$col['key']" :label="$col['label']"
                            x-model="editDraft[{{ Js::from($col['key']) }}]" />
                    @elseif ($col['numeric'])
                        <x-nui::number :id="$id.'-edit-'.$col['key']" :label="$col['label']"
                            step="any" x-model.number="editDraft[{{ Js::from($col['key']) }}]" />
                    @else
                        <x-nui::input :id="$id.'-edit-'.$col['key']" :label="$col['label']"
                            x-model="editDraft[{{ Js::from($col['key']) }}]" />
                    @endif
                @endforeach
                <div class="mt-2 flex justify-end gap-2">
                    <x-nui::button variant="ghost" x-on:click="$refs.editDialog.close()">Cancelar</x-nui::button>
                    <x-nui::button x-on:click="saveRowEditor()">Salvar alterações</x-nui::button>
                </div>
            </div>
        </x-nui::modal>
    @endif
</div>
