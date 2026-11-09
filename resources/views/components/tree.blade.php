@props([
    'items' => [],
    'default' => null,
    'multiple' => false,
    'expanded' => false,
    'icons' => true,
    'guides' => true,
    'navigate' => false,
    'label' => 'Árvore',
])

@php
    $id = $attributes->get('id') ?? 'nui-tree-'.Str::random(8);

    /*
     * Normalizes nodes to ['key', 'label', 'icon', 'badge', 'href', 'disabled', 'expanded', 'children'].
     * A node's key is its "value" or, without one, its label path ("docs/guias").
     */
    $normalize = function (array $nodes, string $parent = '') use (&$normalize): array {
        return collect($nodes)->values()->map(function ($node) use ($normalize, $parent) {
            $node = is_array($node) ? $node : ['label' => (string) $node];
            $key = (string) ($node['value'] ?? ltrim($parent.'/'.(Str::slug($node['label']) ?: md5($node['label'])), '/'));

            return [
                'key' => $key,
                'label' => (string) $node['label'],
                'icon' => $node['icon'] ?? null,
                'badge' => $node['badge'] ?? null,
                'href' => $node['href'] ?? null,
                'disabled' => (bool) ($node['disabled'] ?? false),
                'expanded' => (bool) ($node['expanded'] ?? false),
                'children' => $normalize($node['children'] ?? [], $key),
            ];
        })->all();
    };
    $nodes = $normalize($items);

    // key => leaf keys beneath it (a leaf maps to itself); branch key => its ancestors, for auto-expanding.
    $leaves = [];
    $ancestors = [];
    $open = [];
    $walk = function (array $nodes, array $path) use (&$walk, &$leaves, &$ancestors, &$open, $expanded): array {
        $collected = [];
        foreach ($nodes as $node) {
            $ancestors[$node['key']] = $path;
            if ($node['children']) {
                if ($expanded || $node['expanded']) {
                    $open[] = $node['key'];
                }
                $leaves[$node['key']] = $walk($node['children'], [...$path, $node['key']]);
            } else {
                $leaves[$node['key']] = [$node['key']];
            }
            $collected = [...$collected, ...$leaves[$node['key']]];
        }

        return $collected;
    };
    $walk($nodes, []);

    $selected = $multiple ? array_values(array_map('strval', (array) ($default ?? []))) : ($default === null ? null : (string) $default);
    foreach ((array) $selected as $key) {
        $open = [...$open, ...($ancestors[$key] ?? [])];
    }
    $open = array_values(array_unique($open));

    $state = ['open' => $open, 'selected' => $selected, 'multiple' => (bool) $multiple, 'leaves' => $leaves, 'navigate' => (bool) $navigate];
@endphp

{{--
    Tree view on the WAI-ARIA tree pattern. Up/Down move through visible nodes, Right opens or enters
    a branch, Left closes or goes to the parent, Home/End jump, Enter/Space select, * opens siblings,
    and typing a letter jumps to the next matching label. The selected key works with x-model and
    wire:model (x-modelable): a string, or with "multiple" an array of leaf keys, where branches show
    a mixed check. Nodes with href navigate on activation. Icons are trusted HTML from your code.
--}}
<div
    x-data="{
        ...@js($state),
        active: null,
        typed: '',
        typedAt: 0,
        init() {
            this.active = this.nodes().find((node) => this.isSelected(node.dataset.node))?.dataset.node ?? this.nodes()[0]?.dataset.node ?? null;
        },
        nodes() { return [...this.$refs.tree.querySelectorAll('[role=treeitem]')].filter((node) => ! node.parentElement.closest('.nui-tree-collapse:not([data-open])')) },
        node(key) { return this.$refs.tree.querySelector('[data-node=' + CSS.escape(key) + ']') },
        isOpen(key) { return this.open.includes(key) },
        toggle(key, force) {
            const opened = force ?? ! this.isOpen(key);
            this.open = opened ? [...new Set([...this.open, key])] : this.open.filter((item) => item !== key);
            this.$dispatch('nui-tree-toggle', { node: key, open: opened });
        },
        isSelected(key) {
            if (! this.multiple) return this.selected === key;
            const leaves = this.leaves[key] ?? [];
            return leaves.length > 0 && leaves.every((leaf) => (this.selected ?? []).includes(leaf));
        },
        isMixed(key) {
            if (! this.multiple || this.isSelected(key)) return false;
            return (this.leaves[key] ?? []).some((leaf) => (this.selected ?? []).includes(leaf));
        },
        checked(key) { return this.isMixed(key) ? 'mixed' : String(this.isSelected(key)) },
        activate(element) {
            const key = element.dataset.node;
            this.focus(element);
            if (element.getAttribute('aria-disabled') === 'true') return;
            if (this.multiple) {
                const leaves = this.leaves[key] ?? [], current = this.selected ?? [];
                this.selected = this.isSelected(key) ? current.filter((leaf) => ! leaves.includes(leaf)) : [...new Set([...current, ...leaves])];
            } else {
                this.selected = key;
                if (element.dataset.branch !== undefined) this.toggle(key);
            }
            this.$dispatch('nui-tree-select', { node: key, selected: this.selected });
            const href = element.dataset.href;
            if (href) this.navigate && window.Livewire?.navigate ? window.Livewire.navigate(href) : (window.location.href = href);
        },
        rowClick(element) {
            if (this.multiple && element.dataset.branch !== undefined) { this.focus(element); this.toggle(element.dataset.node); return; }
            this.activate(element);
        },
        focus(element) {
            if (! element) return;
            this.active = element.dataset.node;
            element.focus();
        },
        parentOf(element) { return element.parentElement.closest('[role=treeitem]') },
        onKey(event) {
            const element = event.target.closest('[role=treeitem]');
            if (! element) return;
            const nodes = this.nodes(), index = nodes.indexOf(element), key = element.dataset.node, branch = element.dataset.branch !== undefined;
            const actions = {
                ArrowDown: () => this.focus(nodes[index + 1]),
                ArrowUp: () => this.focus(nodes[index - 1]),
                Home: () => this.focus(nodes[0]),
                End: () => this.focus(nodes[nodes.length - 1]),
                ArrowRight: () => branch && (this.isOpen(key) ? this.$nextTick(() => this.focus(this.nodes()[index + 1])) : this.toggle(key, true)),
                ArrowLeft: () => branch && this.isOpen(key) ? this.toggle(key, false) : this.focus(this.parentOf(element)),
                Enter: () => this.activate(element),
                ' ': () => this.activate(element),
                '*': () => [...element.parentElement.children].filter((sibling) => sibling.dataset.branch !== undefined).forEach((sibling) => this.toggle(sibling.dataset.node, true)),
            };
            if (actions[event.key]) { event.preventDefault(); actions[event.key](); return; }
            if (event.key.length === 1 && ! event.ctrlKey && ! event.metaKey && ! event.altKey) {
                this.typed = (Date.now() - this.typedAt > 600 ? '' : this.typed) + event.key.toLowerCase();
                this.typedAt = Date.now();
                const ordered = [...nodes.slice(index + 1), ...nodes.slice(0, index + 1)];
                this.focus(ordered.find((node) => node.dataset.label.startsWith(this.typed)));
            }
        },
        expandAll() { this.open = Object.keys(this.leaves).filter((key) => this.node(key)?.dataset.branch !== undefined) },
        collapseAll() { this.open = [] },
    }"
    x-modelable="selected"
    {{ $attributes->whereStartsWith(['wire:model', 'x-model']) }}
    {{ $attributes->except(['id', 'class'])->whereDoesntStartWith(['wire:model', 'x-model']) }}
    {{ $attributes->only('class')->class(['nui-tree min-w-0 text-sm', 'nui-tree-guides' => $guides]) }}
    id="{{ $id }}">
    <ul x-ref="tree" role="tree" aria-label="{{ $label }}" @if ($multiple) aria-multiselectable="true" @endif
        x-on:keydown="onKey($event)" class="nui-tree-root flex flex-col">
        @foreach ($nodes as $node)
            <x-nui::tree.node :$node :level="1" :setsize="count($nodes)" :posinset="$loop->iteration"
                :open="in_array($node['key'], $open, true)" :open-keys="$open" :$selected :$leaves :$multiple :$icons :tree="$id" />
        @endforeach
    </ul>
</div>
