@props([
    'title' => null,
    'value' => null,
    'delta' => null,
    'trend' => 'up',
    'labels' => [],
    'statuses' => [],
    'height' => 200,
])

@use('Nicro\Nui\Support\Chart')

@php
    // statuses: [['label' => 'Entregues', 'data' => [..]], ['label' => 'Em trânsito', 'data' => [..]], ['label' => 'Cancelados', 'data' => [..]]]
    // Up to three, bottom segment first, in chart-ink order. Status colours (success/danger) stay reserved for alerts.
    $palette = ['var(--nui-chart-1)', 'var(--nui-chart-2)', 'var(--nui-chart-3)'];
    $labels = array_values($labels);
    $count = max(1, count($labels));
    $list = collect($statuses)->take(3)->values()->map(fn (array $status, int $index) => [
        'label' => $status['label'],
        'data' => array_map('floatval', array_values($status['data'] ?? [])),
        'color' => $palette[$index],
    ]);
    $totals = array_map(fn (int $index) => $list->sum(fn (array $status) => $status['data'][$index] ?? 0), range(0, $count - 1));
    $scale = Chart::scale((float) max($totals ?: [0]));
    $grand = array_sum($totals);
    $value ??= Chart::number($grand);

    $points = collect($labels)->map(fn (string $label, int $index) => [
        'label' => $label,
        'total' => Chart::number($totals[$index]),
        'left' => round(($index + 0.5) / $count * 100, 3),
        'rows' => $list->map(fn (array $status) => ['label' => $status['label'], 'value' => Chart::number($status['data'][$index] ?? 0), 'color' => $status['color']])->reverse()->values()->all(),
    ])->values()->all();

    $id = 'nui-orders-'.Str::random(6);
    $summary = ($title ? $title.'. ' : '').'Colunas empilhadas por situação, de '.($labels[0] ?? '').' a '.(end($labels) ?: '').': '
        .$list->map(fn (array $status) => $status['label'].' '.Chart::number(array_sum($status['data'])))->implode(', ').'. Use as setas para ler cada dia.';
@endphp

{{--
    Orders card: how many orders came in per period, each column stacked by status (bottom first), with a 2px
    surface gap between segments. Tiles give each status's total and share. Hover or focus + arrows reads a column.
--}}
<x-nui::chart.card :title="$title" :value="$value" :delta="$delta" :trend="$trend" {{ $attributes }}>
    @isset($actions)
        <x-slot:actions>{{ $actions }}</x-slot:actions>
    @endisset

    <div class="grid grid-cols-[auto_minmax(0,1fr)] gap-x-3"
        x-data="{ points: @js($points), active: null, get point() { return this.active === null ? null : this.points[this.active] } }">
        <div aria-hidden="true" class="relative w-10 text-right text-xs tabular-nums text-base-content/55" style="height: {{ (int) $height }}px">
            @foreach ($scale['ticks'] as $tick)
                <span class="absolute right-0 -translate-y-1/2 whitespace-nowrap" style="top: {{ round(100 - $tick / $scale['max'] * 100, 3) }}%">{{ Chart::compact($tick) }}</span>
            @endforeach
        </div>

        <div tabindex="0" role="img" aria-label="{{ $summary }}" aria-describedby="{{ $id }}-live"
            class="relative rounded-[min(var(--nui-radius),0.375rem)] outline-none focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-primary"
            style="height: {{ (int) $height }}px"
            x-on:pointerleave="active = null" x-on:focus="active ??= points.length - 1" x-on:blur="active = null"
            x-on:keydown.right.prevent="active = Math.min(points.length - 1, (active ?? -1) + 1)"
            x-on:keydown.left.prevent="active = Math.max(0, (active ?? points.length) - 1)">
            @foreach ($scale['ticks'] as $tick)
                <span aria-hidden="true" @class(['absolute inset-x-0 border-t', 'border-base-content/25' => $tick == 0, 'border-dotted border-base-content/20' => $tick != 0])
                    style="top: {{ round(100 - $tick / $scale['max'] * 100, 3) }}%"></span>
            @endforeach

            <div aria-hidden="true" class="absolute inset-0 grid" style="grid-template-columns: repeat({{ $count }}, minmax(0, 1fr))">
                @foreach ($totals as $index => $total)
                    <div class="flex cursor-crosshair items-end justify-center px-[18%]" x-on:pointerenter="active = {{ $index }}">
                        <div class="nui-orders-stack flex w-full max-w-7 flex-col-reverse gap-[2px] transition-opacity duration-150"
                            x-bind:class="active !== null && active !== {{ $index }} && 'opacity-50'"
                            style="height: {{ round($total / $scale['max'] * 100, 3) }}%; --nui-bar-delay: {{ $index * 30 }}ms">
                            @foreach ($list as $status)
                                @if (($status['data'][$index] ?? 0) > 0)
                                    <span class="block min-h-[2px] w-full" style="flex: {{ $status['data'][$index] }} 1 0; background: {{ $status['color'] }}"></span>
                                @endif
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>

            <template x-if="point">
                <div aria-hidden="true" class="nui-chart-tip pointer-events-none absolute top-1 z-10 min-w-40 rounded-[min(var(--nui-radius),0.5rem)] bg-[oklch(23%_0.02_260)] px-3 py-2 text-xs text-[oklch(95%_0.01_85)] shadow-[0_8px_20px_-8px_oklch(0%_0_0/0.5)]"
                    x-bind:style="point.left > 60 ? { right: (100 - point.left) + '%', marginRight: '20px' } : { left: point.left + '%', marginLeft: '20px' }">
                    <p class="m-0 mb-1.5 flex justify-between gap-4 font-semibold"><span x-text="point.label"></span><span class="tabular-nums" x-text="point.total + ' pedidos'"></span></p>
                    <template x-for="row in point.rows" x-bind:key="row.label">
                        <p class="m-0 flex items-center justify-between gap-4 py-0.5">
                            <span class="flex items-center gap-1.5 opacity-75"><span class="size-2 rounded-[2px]" x-bind:style="{ background: row.color }"></span><span x-text="row.label"></span></span>
                            <span class="font-semibold tabular-nums" x-text="row.value"></span>
                        </p>
                    </template>
                </div>
            </template>
        </div>
        <p id="{{ $id }}-live" class="sr-only" aria-live="polite" x-text="point ? point.label + ': ' + point.total + ' pedidos; ' + point.rows.map((row) => row.label + ' ' + row.value).join(', ') : ''"></p>

        <div aria-hidden="true" class="col-start-2 mt-2 grid text-center text-xs text-base-content/55" style="grid-template-columns: repeat({{ $count }}, minmax(0, 1fr))">
            @foreach ($labels as $index => $label)
                <span @class(['truncate', 'invisible @min-[30rem]:visible' => $count > 8 && $index % 2 === 1])>{{ $label }}</span>
            @endforeach
        </div>
    </div>

    <div @class(['grid grid-cols-1 gap-2', '@min-[26rem]:grid-cols-2' => $list->count() === 2, '@min-[26rem]:grid-cols-3' => $list->count() === 3])>
        @foreach ($list as $status)
            @php($sum = array_sum($status['data']))
            <x-nui::chart.stat :label="$status['label']" :color="$status['color']"
                :value="Chart::number($sum).' · '.Chart::number($grand > 0 ? $sum / $grand * 100 : 0).'%'" />
        @endforeach
    </div>

    {{-- The wrapper hides the table: a caption is painted outside an sr-only table box. --}}
    <div class="sr-only">
        <table>
            <caption>{{ $title ?? 'Pedidos' }}</caption>
            <thead><tr><th scope="col">Período</th>@foreach ($list as $status)<th scope="col">{{ $status['label'] }}</th>@endforeach<th scope="col">Total</th></tr></thead>
            <tbody>
                @foreach ($labels as $index => $label)
                    <tr><th scope="row">{{ $label }}</th>@foreach ($list as $status)<td>{{ Chart::number($status['data'][$index] ?? 0) }}</td>@endforeach<td>{{ Chart::number($totals[$index]) }}</td></tr>
                @endforeach
            </tbody>
        </table>
    </div>
</x-nui::chart.card>
