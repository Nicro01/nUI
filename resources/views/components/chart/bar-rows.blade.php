@props(['rows' => [], 'show', 'metric' => null])

@php($max = max(1e-9, (float) collect($rows)->max('value')))

{{--
    Rows of <x-nui::bar-list>, shared by every tab panel. Each row is a compartment of the type case
    (a key-cap track) with the bar inked in brass inside it; the figure is set in the display face.
--}}
<div class="flex flex-col gap-2">
    @if ($metric)
        <div class="nui-bar-list-metric flex justify-end px-3">{{ $metric }}</div>
    @endif
    <ul class="m-0 flex list-none flex-col gap-2 p-0">
        @foreach ($rows as $row)
            <li class="nui-bar-list-row relative flex min-h-11 items-center justify-between gap-3 overflow-hidden px-3 text-sm">
                <span aria-hidden="true" class="nui-bar-list-bar absolute inset-y-0 left-0"
                    style="width: max(0.375rem, {{ round(max(0, (float) $row['value']) / $max * 100, 2) }}%); --nui-bar-delay: {{ $loop->index * 60 }}ms"></span>
                <span class="relative flex min-w-0 items-center gap-2.5 font-medium">
                    @if (! empty($row['icon']))<span aria-hidden="true" class="flex size-4 shrink-0 text-base-content/65 [&_svg]:size-4">{!! $row['icon'] !!}</span>@endif
                    <span class="truncate">{{ $row['label'] }}</span>
                </span>
                <span class="nui-chart-figure relative shrink-0 text-[1.0625rem] leading-none tabular-nums">{{ $show($row['value']) }}</span>
            </li>
        @endforeach
    </ul>
</div>
