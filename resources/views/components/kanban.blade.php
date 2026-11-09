@props([
    'columns' => [],
    'label' => 'Quadro Kanban',
    'filterable' => true,
    'readonly' => false,
])

@php
    $id = $attributes->get('id') ?? 'nui-kanban-'.Str::random(6);
    $locked = $readonly || (bool) $attributes->get('disabled');
    $seenColumns = [];
    $seenCards = [];
    $initial = [];
    foreach ($columns as $column) {
        $columnId = (string) ($column['id'] ?? '');
        if ($columnId === '' || isset($seenColumns[$columnId])) {
            throw new InvalidArgumentException('Kanban: cada coluna precisa de um id único.');
        }
        $seenColumns[$columnId] = true;
        $cards = [];
        foreach ($column['cards'] ?? [] as $card) {
            $cardId = (string) ($card['id'] ?? '');
            if ($cardId === '' || isset($seenCards[$cardId])) {
                throw new InvalidArgumentException('Kanban: cada card precisa de um id único no quadro.');
            }
            $seenCards[$cardId] = true;
            $cards[] = array_replace([
                'title' => '', 'description' => '', 'priority' => 'normal',
                'tags' => [], 'assignee' => '', 'due' => null,
            ], $card, ['id' => $cardId]);
        }
        $initial[] = array_replace($column, [
            'id' => $columnId, 'title' => (string) ($column['title'] ?? $columnId),
            'limit' => max(0, (int) ($column['limit'] ?? 0)), 'cards' => $cards,
        ]);
    }
    $destinations = array_map(fn (array $column) => ['value' => $column['id'], 'label' => $column['title']], $initial);
@endphp

<div {{ $attributes->whereDoesntStartWith(['wire:model', 'x-model'])->except('disabled')->class('min-w-0 w-full') }}
    role="region" aria-labelledby="{{ $id }}-label"
    x-data="{
        columns: @js($initial),
        locked: @js($locked),
        query: '',
        priority: '',
        dragged: null,
        over: null,
        selected: null,
        destination: '',
        position: 1,
        notice: '',
        issue: '',
        priorities: { low: 'Baixa', normal: 'Normal', high: 'Alta', urgent: 'Urgente' },
        visible(column) {
            const query = this.query.trim().toLocaleLowerCase('pt-BR');
            return column.cards.filter(card => (!this.priority || card.priority === this.priority) &&
                (!query || [card.title, card.description, card.assignee, ...(card.tags || [])].join(' ').toLocaleLowerCase('pt-BR').includes(query)));
        },
        locate(id) {
            for (let index = 0; index < this.columns.length; index++) {
                const cardIndex = this.columns[index].cards.findIndex(card => card.id === id);
                if (cardIndex !== -1) return { column: index, index: cardIndex, card: this.columns[index].cards[cardIndex] };
            }
            return null;
        },
        start(event, card) {
            if (this.locked) return event.preventDefault();
            this.dragged = card.id;
            event.dataTransfer.effectAllowed = 'move';
            event.dataTransfer.setData('text/plain', card.id);
        },
        clearDrag() { this.dragged = null; this.over = null; },
        move(id, targetId, targetIndex) {
            if (this.locked || !id) return false;
            const source = this.locate(id);
            const target = this.columns.findIndex(column => column.id === targetId);
            if (!source || target === -1) return false;
            const destination = this.columns[target];
            if (source.column !== target && destination.limit && destination.cards.length >= destination.limit) {
                this.issue = 'A coluna ' + destination.title + ' atingiu o limite de ' + destination.limit + ' cards.';
                this.notice = this.issue;
                this.$dispatch('kanban-limit', { cardId: id, columnId: targetId, limit: destination.limit });
                this.clearDrag();
                return false;
            }
            const next = this.columns.map(column => ({ ...column, cards: [...column.cards] }));
            const [card] = next[source.column].cards.splice(source.index, 1);
            let index = Math.max(0, Math.min(next[target].cards.length, Number(targetIndex) || 0));
            next[target].cards.splice(index, 0, card);
            const fromId = this.columns[source.column].id;
            this.columns = next;
            this.issue = '';
            this.notice = card.title + ' movido para ' + destination.title + ', posição ' + (index + 1) + '.';
            this.clearDrag();
            this.$dispatch('kanban-moved', { cardId: id, from: fromId, to: targetId, fromIndex: source.index, toIndex: index, columns: JSON.parse(JSON.stringify(next)) });
            return true;
        },
        dropBefore(card, column) {
            const target = this.locate(card.id), source = this.locate(this.dragged);
            if (!target || !source || card.id === this.dragged) return this.clearDrag();
            const index = target.index - (source.column === target.column && source.index < target.index ? 1 : 0);
            this.move(this.dragged, column.id, index);
        },
        requestMove(card) {
            if (this.locked) return;
            const source = this.locate(card.id);
            this.selected = card;
            this.destination = this.columns[source.column].id;
            this.position = source.index + 1;
            this.issue = '';
            this.$refs.moveDialog.showModal();
        },
        confirmMove() {
            if (this.selected && this.move(this.selected.id, this.destination, this.position - 1)) this.$refs.moveDialog.close();
        },
        adjacent(card, direction) {
            const source = this.locate(card.id);
            const target = source ? this.columns[source.column + direction] : null;
            if (target) this.move(card.id, target.id, target.cards.length);
        },
        formatDate(text) {
            if (!text || !/^\d{4}-\d{2}-\d{2}$/.test(text)) return '';
            const [year, month, day] = text.split('-').map(Number);
            return new Date(year, month - 1, day).toLocaleDateString('pt-BR', { day: '2-digit', month: 'short' });
        },
        get maxPosition() {
            const target = this.columns.find(column => column.id === this.destination);
            const source = this.selected ? this.locate(this.selected.id) : null;
            return target ? target.cards.length + (source && this.columns[source.column].id === target.id ? 0 : 1) : 1;
        }
    }"
    x-modelable="columns" {{ $attributes->whereStartsWith(['wire:model', 'x-model']) }}>

    <div class="mb-5 flex flex-wrap items-end justify-between gap-4">
        <div>
            <h3 id="{{ $id }}-label" class="text-base font-semibold text-base-content">{{ $label }}</h3>
            <p class="mt-1 text-xs text-base-content/60">{{ $locked ? 'Quadro somente para consulta.' : 'Arraste os cards ou use a ação Mover.' }}</p>
        </div>
        @if ($filterable)
            <div class="flex min-w-0 flex-wrap items-end gap-3">
                <x-nui::input id="{{ $id }}-search" label="Filtrar cards" type="search" x-model.debounce.150ms="query" placeholder="Título, responsável ou etiqueta" class="w-full sm:w-64" />
                <x-nui::select id="{{ $id }}-priority" label="Prioridade" x-model="priority"
                    :options="['' => 'Todas', 'low' => 'Baixa', 'normal' => 'Normal', 'high' => 'Alta', 'urgent' => 'Urgente']"
                    placeholder="Todas" class="w-full sm:w-36" />
            </div>
        @endif
    </div>

    <div x-show="issue" x-cloak class="mb-4">
        <x-nui::alert variant="warning" title="Movimentação não realizada"><span x-text="issue"></span></x-nui::alert>
    </div>
    <p class="sr-only" role="status" aria-live="polite" x-text="notice"></p>

    <div data-lenis-prevent class="nui-kanban-columns flex min-w-0 snap-x gap-4 overflow-x-auto pb-4">
        <template x-for="column in columns" x-bind:key="column.id">
            <section class="nui-kanban-column flex w-72 min-w-0 shrink-0 snap-start flex-col rounded-nui border border-base-300/80 bg-base-300/40 p-3 shadow-2xs"
                x-bind:aria-label="column.title" x-bind:data-column="column.id"
                x-on:dragover.prevent="if (dragged) over = column.id"
                x-on:drop.prevent="move(dragged, column.id, column.cards.length)">
                <header class="mb-3 flex items-center justify-between gap-3 px-1">
                    <h4 class="min-w-0 truncate text-sm font-semibold text-base-content" x-text="column.title"></h4>
                    <x-nui::tag size="sm"><span class="tabular-nums" x-text="column.cards.length + (column.limit ? ' / ' + column.limit : '')"></span></x-nui::tag>
                </header>

                <div class="flex min-h-28 flex-1 flex-col gap-3 rounded-nui transition-colors"
                    x-bind:class="over === column.id && 'bg-primary/5 outline-2 outline-dashed outline-primary/30 outline-offset-4'">
                    <template x-for="card in visible(column)" x-bind:key="card.id">
                        <x-nui::card padding="sm" class="nui-kanban-card" x-bind:data-card="card.id"
                            x-bind:draggable="!locked" x-on:dragstart="start($event, card)" x-on:dragend="clearDrag()"
                            x-on:dragover.prevent.stop="if (dragged) over = column.id"
                            x-on:drop.prevent.stop="dropBefore(card, column)"
                            x-bind:class="dragged === card.id && 'ring-2 ring-primary'">
                            <div class="flex items-start justify-between gap-3">
                                <span class="min-w-0 text-[0.6875rem] font-medium tracking-wide text-base-content/65" x-text="card.id"></span>
                                <svg x-show="!locked" aria-hidden="true" class="size-4 shrink-0 text-base-content/50" viewBox="0 0 20 20" fill="currentColor">
                                    <circle cx="7" cy="5" r="1" /><circle cx="13" cy="5" r="1" /><circle cx="7" cy="10" r="1" /><circle cx="13" cy="10" r="1" /><circle cx="7" cy="15" r="1" /><circle cx="13" cy="15" r="1" />
                                </svg>
                            </div>
                            <h5 class="mt-3 text-sm font-semibold leading-relaxed text-base-content [overflow-wrap:anywhere]" x-text="card.title"></h5>
                            <p x-show="card.description" class="mt-1.5 line-clamp-2 text-xs leading-relaxed text-base-content/70" x-text="card.description"></p>
                            <div x-show="card.tags?.length" class="mt-3 flex flex-wrap gap-1.5">
                                <template x-for="tag in card.tags || []" x-bind:key="tag"><x-nui::tag size="sm"><span x-text="tag"></span></x-nui::tag></template>
                            </div>
                            <div class="mt-3 flex items-center justify-between gap-2 text-[0.6875rem] text-base-content/65">
                                <span class="inline-flex items-center gap-1.5">
                                    <span aria-hidden="true" class="size-1.5 rounded-full bg-base-content/35" x-bind:class="(card.priority === 'urgent' || card.priority === 'high') && '!bg-danger'"></span>
                                    <span x-text="priorities[card.priority] || priorities.normal"></span>
                                </span>
                                <time x-show="card.due" x-bind:datetime="card.due || ''" x-text="formatDate(card.due)"></time>
                            </div>
                            <div class="mt-5 flex items-center justify-between gap-2">
                                <span class="flex min-w-0 items-center gap-2 text-xs text-base-content/75">
                                    <span x-show="card.assignee" aria-hidden="true" class="grid size-7 shrink-0 place-items-center rounded-full bg-base-200 text-[0.625rem] font-semibold text-base-content" x-text="(card.assignee || '').trim().split(/\s+/).map(part => part[0]).slice(0, 2).join('').toLocaleUpperCase('pt-BR')"></span>
                                    <span class="truncate" x-text="card.assignee || 'Sem responsável'"></span>
                                </span>
                                <x-nui::button variant="ghost" size="sm" class="!h-7 !px-2 !text-xs" :disabled="$locked"
                                    x-on:click="requestMove(card)" x-on:keydown.alt.arrow-right.prevent="adjacent(card, 1)"
                                    x-on:keydown.alt.arrow-left.prevent="adjacent(card, -1)" x-bind:aria-label="'Mover ' + card.title">
                                    Mover
                                </x-nui::button>
                            </div>
                        </x-nui::card>
                    </template>
                    <div x-show="visible(column).length === 0" class="grid min-h-28 place-items-center rounded-nui bg-base-100/50 px-4 text-center text-xs text-base-content/70"
                        x-text="column.cards.length ? 'Nenhum card corresponde ao filtro.' : 'Nenhum card nesta coluna.'"></div>
                </div>
            </section>
        </template>
    </div>
    <p x-show="columns.length === 0" class="rounded-nui bg-base-200 p-8 text-center text-sm text-base-content/70">Nenhuma coluna configurada.</p>

    <x-nui::modal :id="$id.'-move'" title="Mover card" size="sm" x-ref="moveDialog">
        <p class="mb-5 font-medium" x-text="selected?.title || ''"></p>
        <div class="flex flex-col gap-4">
            <x-nui::select id="{{ $id }}-destination" label="Coluna de destino" :options="$destinations" options-bind="columns.map(column => ({ value: column.id, label: column.title }))" x-model="destination" />
            <x-nui::number id="{{ $id }}-position" label="Posição na coluna" min="1" x-bind:max="maxPosition" x-model.number="position" />
            <p x-show="issue" class="text-xs text-danger" role="status" x-text="issue"></p>
            <div class="mt-2 flex justify-end gap-2">
                <x-nui::button variant="ghost" x-on:click="$refs.moveDialog.close()">Cancelar</x-nui::button>
                <x-nui::button x-on:click="confirmMove()">Mover card</x-nui::button>
            </div>
        </div>
    </x-nui::modal>
</div>
