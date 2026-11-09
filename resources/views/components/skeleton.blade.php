@props(['variant' => 'rect', 'lines' => 3])

@php
    $base = 'nui-skeleton block bg-base-content/[0.08]';
@endphp

{{--
    Placeholder while content loads. variant: rect (size it with classes) | circle | text (several lines, last one shorter).
    Hidden from screen readers; announce loading elsewhere (e.g. aria-busy on the container).
--}}
@if ($variant === 'text')
    <span aria-hidden="true" {{ $attributes->class('flex flex-col gap-2.5 w-full') }}>
        @for ($line = 1; $line <= (int) $lines; $line++)
            <span class="{{ $base }} h-3 rounded-full {{ $line === (int) $lines && $lines > 1 ? 'w-3/5' : 'w-full' }}"></span>
        @endfor
    </span>
@else
    <span aria-hidden="true" {{ $attributes->class([$base, $variant === 'circle' ? 'rounded-full aspect-square size-10' : 'rounded-nui-field h-4 w-full']) }}></span>
@endif
