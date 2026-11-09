@props([
    'sortable' => false,
    'sorted' => false,
    'direction' => 'asc',
    'align' => 'start',
])

@php
    $clickAttributes = ['wire:click', 'x-on:click', '@click'];
    $sortState = $sortable ? ($sorted ? ($direction === 'desc' ? 'descending' : 'ascending') : 'none') : null;
@endphp

{{--
    Header cell. sortable draws a button with the sort mark; the state lives in aria-sort,
    so the mark follows it whether Blade (sorted + direction) or Alpine (x-bind:aria-sort) sets it.
    Click handlers (wire:click, x-on:click) go to the button, everything else to the <th>.
--}}
<th scope="col" @if ($sortState) aria-sort="{{ $sortState }}" @endif @if ($align !== 'start') data-align="{{ $align }}" @endif
    {{ $sortable ? $attributes->whereDoesntStartWith($clickAttributes) : $attributes }}>
    @if ($sortable)
        {{-- Without a click handler, the table sorts the rows in the browser by this column's cell text. --}}
        <button type="button" data-nui-table-sort
            @if ($attributes->whereStartsWith($clickAttributes)->isEmpty()) x-on:click="sortColumn($el.closest('th'))" @endif
            {{ $attributes->whereStartsWith($clickAttributes) }}>
            <span>{{ $slot }}</span>
            <svg aria-hidden="true" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                <path data-up d="m5 6.5 3-3 3 3" />
                <path data-down d="m5 9.5 3 3 3-3" />
            </svg>
        </button>
    @else
        {{ $slot }}
    @endif
</th>
