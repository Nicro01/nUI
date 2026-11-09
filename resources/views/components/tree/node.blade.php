@props(['node', 'level', 'setsize', 'posinset', 'open' => false, 'openKeys' => [], 'selected' => null, 'leaves' => [], 'multiple' => false, 'icons' => true, 'tree'])

@php
    $key = $node['key'];
    $branch = count($node['children']) > 0;
    $nodeLeaves = $leaves[$key] ?? [$key];
    $isSelected = $multiple
        ? count($nodeLeaves) > 0 && array_diff($nodeLeaves, (array) $selected) === []
        : $selected === $key;
    $isMixed = $multiple && ! $isSelected && array_intersect($nodeLeaves, (array) $selected) !== [];
    $js = Js::from($key);
@endphp

{{-- One node of <x-nui::tree>; branches render their children recursively. Behaviour lives on the tree root. --}}
<li role="treeitem" id="{{ $tree }}-{{ md5($key) }}" data-node="{{ $key }}" data-label="{{ Str::lower($node['label']) }}"
    @if ($branch) data-branch aria-expanded="{{ $open ? 'true' : 'false' }}" x-bind:aria-expanded="isOpen({{ $js }}).toString()" @endif
    @if ($node['href']) data-href="{{ $node['href'] }}" @endif
    aria-level="{{ $level }}" aria-setsize="{{ $setsize }}" aria-posinset="{{ $posinset }}"
    @if ($node['disabled']) aria-disabled="true" @endif
    @if ($multiple)
        aria-checked="{{ $isMixed ? 'mixed' : ($isSelected ? 'true' : 'false') }}" x-bind:aria-checked="checked({{ $js }})"
    @else
        aria-selected="{{ $isSelected ? 'true' : 'false' }}" x-bind:aria-selected="isSelected({{ $js }}).toString()"
    @endif
    tabindex="{{ $level === 1 && $posinset === 1 ? '0' : '-1' }}" x-bind:tabindex="active === {{ $js }} ? 0 : -1"
    x-on:focus.self="active = {{ $js }}"
    class="nui-tree-item outline-none" style="--nui-tree-level: {{ $level - 1 }}">
    <div class="nui-tree-row" x-on:click="rowClick($el.parentElement)">
        @if ($branch)
            <span class="nui-tree-toggle" aria-hidden="true" x-on:click.stop="toggle({{ $js }}); focus($el.closest('[role=treeitem]'))">
                <svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="m6 3.5 4.5 4.5L6 12.5" /></svg>
            </span>
        @else
            <span class="nui-tree-toggle nui-tree-spacer" aria-hidden="true"></span>
        @endif

        @if ($multiple)
            <span class="nui-tree-check" aria-hidden="true" x-on:click.stop="activate($el.closest('[role=treeitem]'))">
                <svg class="nui-tree-check-on" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="m3.5 8.5 3 3 6-7" /></svg>
                <svg class="nui-tree-check-mixed" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><path d="M4 8h8" /></svg>
            </span>
        @endif

        @if ($icons)
            <span class="nui-tree-icon" aria-hidden="true">
                @if ($node['icon'])
                    {!! $node['icon'] !!}
                @elseif ($branch)
                    <svg class="nui-tree-folder" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round"><path d="M1.75 4.25c0-.69.56-1.25 1.25-1.25h3.1l1.5 1.75H13c.69 0 1.25.56 1.25 1.25v6c0 .69-.56 1.25-1.25 1.25H3c-.69 0-1.25-.56-1.25-1.25z" /></svg>
                    <svg class="nui-tree-folder-open" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round"><path d="M1.75 12V4.25c0-.69.56-1.25 1.25-1.25h3.1l1.5 1.75H12c.69 0 1.25.56 1.25 1.25V7" /><path d="M1.75 12 3.6 7.6A1 1 0 0 1 4.52 7h9.4a.75.75 0 0 1 .69 1.04L12.9 12.2a1 1 0 0 1-.92.55H2.5" /></svg>
                @else
                    <svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round"><path d="M9.25 1.75H4.5c-.69 0-1.25.56-1.25 1.25v10c0 .69.56 1.25 1.25 1.25h7c.69 0 1.25-.56 1.25-1.25V5.25z" /><path d="M9.25 1.75v3.5h3.5" /></svg>
                @endif
            </span>
        @endif

        <span class="nui-tree-label">{{ $node['label'] }}</span>

        @if (filled($node['badge']))
            <span class="nui-tree-badge">{{ $node['badge'] }}</span>
        @endif
    </div>

    @if ($branch)
        <div class="nui-tree-collapse" @if ($open) data-open @endif x-bind:data-open="isOpen({{ $js }})">
            <ul role="group" class="nui-tree-group">
                @foreach ($node['children'] as $child)
                    <x-nui::tree.node :node="$child" :level="$level + 1" :setsize="count($node['children'])" :posinset="$loop->iteration"
                        :open="in_array($child['key'], $openKeys, true)" :$selected :$leaves :$multiple :$icons :$tree :open-keys="$openKeys" />
                @endforeach
            </ul>
        </div>
    @endif
</li>
