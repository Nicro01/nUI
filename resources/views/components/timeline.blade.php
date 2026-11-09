@props(['layout' => 'vertical', 'marker' => 'icon', 'compact' => false, 'label' => null])

{{--
    Ordered list of events or steps. Items are <x-nui::timeline-item>; <x-nui::timeline-heading> groups them (e.g. by day).
    layout: vertical | alternate (zigzag from 48rem) | horizontal (scrolls sideways) · marker: icon | number | dot · compact.
    Styling follows each item's data-status, so x-bind:data-status restyles an item on the client.
--}}
<ol @if ($label) aria-label="{{ $label }}" @endif
    data-layout="{{ $layout }}" data-marker="{{ $marker }}" @if ($compact) data-compact @endif
    {{ $attributes->class('nui-timeline text-base-content') }}>
    {{ $slot }}
</ol>
