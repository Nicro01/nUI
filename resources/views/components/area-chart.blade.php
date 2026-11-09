@props([
    'title' => null,
    'value' => null,
    'delta' => null,
    'trend' => 'up',
    'labels' => [],
    'series' => [],
    'stacked' => true,
    'height' => 220,
    'decimals' => 0,
])

@use('Nicro\Nui\Support\Chart')

@php
    // series: [['label' => 'Orgânico', 'data' => [..], 'total' => '50.500'], …], up to three, bottom layer first, in chart-ink order.
    $palette = ['var(--nui-chart-1)', 'var(--nui-chart-2)', 'var(--nui-chart-3)'];
    $labels = array_values($labels);
    $count = max(1, count($labels));
    $width = 1000;
    $plotHeight = 300;
    $list = collect($series)->take(3)->values()->map(fn (array $item, int $index) => [
        'label' => $item['label'],
        'data' => array_map('floatval', array_values($item['data'] ?? [])),
        'total' => $item['total'] ?? Chart::number(array_sum($item['data'] ?? []), $decimals),
        'color' => $palette[$index],
    ]);

    $running = array_fill(0, $count, 0.0);
    $layers = [];
    foreach ($list as $item) {
        $base = $running;
        foreach ($item['data'] as $index => $amount) {
            $running[$index] = ($stacked ? ($running[$index] ?? 0) : 0) + $amount;
        }
        $layers[] = ['base' => $stacked ? $base : array_fill(0, $count, 0.0), 'top' => $stacked ? $running : $item['data']];
    }

    $peak = collect($layers)->flatMap(fn (array $layer) => $layer['top'])->max() ?? 0;
    $scale = Chart::scale((float) $peak);
    $x = fn (int $index): float => $count === 1 ? $width / 2 : $index / ($count - 1) * $width;
    $y = fn (float $amount): float => $plotHeight - $amount / $scale['max'] * $plotHeight;

    $shapes = collect($layers)->map(function (array $layer, int $index) use ($x, $y, $list, $plotHeight, $width) {
        $top = array_map(fn (int $i) => [$x($i), $y($layer['top'][$i] ?? 0)], array_keys($layer['top']));
        $bottom = array_reverse(array_map(fn (int $i) => [$x($i), $y($layer['base'][$i] ?? 0)], array_keys($layer['base'])));
        $line = Chart::smoothPath($top);
        $floor = array_sum($layer['base']) == 0 ? ' L'.$width.','.$plotHeight.' L0,'.$plotHeight : ' L'.substr(Chart::smoothPath($bottom), 1);

        return ['line' => $line, 'area' => $line.$floor.' Z', 'color' => $list[$index]['color']];
    });

    $points = collect($labels)->map(fn (string $label, int $index) => [
        'label' => $label,
        'left' => round($x($index) / $width * 100, 3),
        'rows' => $list->map(fn (array $item, int $layer) => [
            'label' => $item['label'],
            'value' => Chart::number($item['data'][$index] ?? 0, $decimals),
            'color' => $item['color'],
            'top' => round($y($layers[$layer]['top'][$index] ?? 0) / $plotHeight * 100, 3),
        ])->values()->all(),
    ])->values()->all();

    $id = 'nui-area-'.Str::random(6);
    $summary = ($title ? $title.'. ' : '').'Gráfico de área'.($stacked ? ' empilhado' : '').' de '.($labels[0] ?? '').' a '.(end($labels) ?: '').': '
        .$list->map(fn (array $item) => $item['label'].' '.$item['total'])->implode(', ').'. Use as setas para ler cada ponto.';
@endphp

{{--
    Area chart card: change over time for up to three series, stacked by default (stacked="false" overlays them).
    One y-axis from zero, recessive grid, 2px lines with a soft fill. Hover or focus + arrows moves a crosshair
    with a tooltip; a hidden table carries every value for screen readers.
--}}
<x-nui::chart.card :title="$title" :value="$value" :delta="$delta" :trend="$trend" {{ $attributes }}>
    @isset($actions)
        <x-slot:actions>{{ $actions }}</x-slot:actions>
    @endisset

    <div class="grid grid-cols-[auto_minmax(0,1fr)] gap-x-3 px-1"
        x-data="{
            points: @js($points),
            active: null,
            get point() { return this.active === null ? null : this.points[this.active] },
            pick(event) {
                const box = $refs.plot.getBoundingClientRect();
                const ratio = Math.min(1, Math.max(0, (event.clientX - box.left) / box.width));
                this.active = Math.round(ratio * (this.points.length - 1));
            },
            step(delta) { this.active = Math.min(this.points.length - 1, Math.max(0, (this.active ?? this.points.length - 1) + delta)) },
        }">
        <div aria-hidden="true" class="relative w-10 text-right text-xs tabular-nums text-base-content/55" style="height: {{ (int) $height }}px">
            @foreach ($scale['ticks'] as $tick)
                <span class="absolute right-0 -translate-y-1/2 whitespace-nowrap" style="top: {{ round(100 - $tick / $scale['max'] * 100, 3) }}%">{{ Chart::compact($tick) }}</span>
            @endforeach
        </div>

        <div x-ref="plot" tabindex="0" role="img" aria-label="{{ $summary }}" aria-describedby="{{ $id }}-live"
            class="nui-chart-plot relative cursor-crosshair rounded-[min(var(--nui-radius),0.375rem)] outline-none focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-primary"
            style="height: {{ (int) $height }}px"
            x-on:pointermove="pick($event)" x-on:pointerleave="active = null"
            x-on:focus="active ??= points.length - 1" x-on:blur="active = null"
            x-on:keydown.right.prevent="step(1)" x-on:keydown.left.prevent="step(-1)"
            x-on:keydown.home.prevent="active = 0" x-on:keydown.end.prevent="active = points.length - 1">
            @foreach ($scale['ticks'] as $tick)
                <span aria-hidden="true" @class(['absolute inset-x-0 border-t', 'border-base-content/25' => $tick == 0, 'border-dotted border-base-content/20' => $tick != 0])
                    style="top: {{ round(100 - $tick / $scale['max'] * 100, 3) }}%"></span>
            @endforeach

            <svg aria-hidden="true" viewBox="0 0 {{ $width }} {{ $plotHeight }}" preserveAspectRatio="none" class="nui-chart-draw absolute inset-0 size-full overflow-visible">
                @foreach ($shapes as $shape)
                    <path d="{{ $shape['area'] }}" style="fill: color-mix(in oklch, {{ $shape['color'] }} 20%, transparent)" />
                @endforeach
                @foreach ($shapes as $shape)
                    <path d="{{ $shape['line'] }}" fill="none" stroke-width="2" stroke-linejoin="round" vector-effect="non-scaling-stroke" style="stroke: {{ $shape['color'] }}" />
                @endforeach
            </svg>

            <template x-if="point">
                <div aria-hidden="true" class="pointer-events-none absolute inset-0">
                    <span class="absolute inset-y-0 w-px -translate-x-1/2 bg-base-content/35" x-bind:style="{ left: point.left + '%' }"></span>
                    <template x-for="row in point.rows" x-bind:key="row.label">
                        <span class="absolute size-2.5 -translate-x-1/2 -translate-y-1/2 rounded-full ring-2 ring-base-100"
                            x-bind:style="{ left: point.left + '%', top: row.top + '%', background: row.color }"></span>
                    </template>
                    <div class="nui-chart-tip absolute top-1 min-w-36 rounded-[min(var(--nui-radius),0.5rem)] bg-[oklch(23%_0.02_260)] px-3 py-2 text-xs text-[oklch(95%_0.01_85)] shadow-[0_8px_20px_-8px_oklch(0%_0_0/0.5)]"
                        x-bind:style="point.left > 60 ? { right: (100 - point.left) + '%', marginRight: '12px' } : { left: point.left + '%', marginLeft: '12px' }">
                        <p class="m-0 mb-1.5 font-semibold" x-text="point.label"></p>
                        <template x-for="row in [...point.rows].reverse()" x-bind:key="row.label">
                            <p class="m-0 flex items-center justify-between gap-4 py-0.5">
                                <span class="flex items-center gap-1.5 opacity-75"><span class="size-2 rounded-[2px]" x-bind:style="{ background: row.color }"></span><span x-text="row.label"></span></span>
                                <span class="font-semibold tabular-nums" x-text="row.value"></span>
                            </p>
                        </template>
                    </div>
                </div>
            </template>
        </div>
        <p id="{{ $id }}-live" class="sr-only" aria-live="polite" x-text="point ? point.label + ': ' + point.rows.map((row) => row.label + ' ' + row.value).join(', ') : ''"></p>

        <div aria-hidden="true" class="relative col-start-2 mt-2 h-5 text-xs text-base-content/55">
            @foreach ($labels as $index => $label)
                @php($left = $count === 1 ? 50 : $index / ($count - 1) * 100)
                <span @class([
                    'absolute top-0 whitespace-nowrap',
                    '-translate-x-1/2' => $index > 0 && $index < $count - 1,
                    '-translate-x-full' => $index === $count - 1 && $count > 1,
                    'hidden @min-[34rem]:block' => $count > 6 && $index % 2 === 1 && $index !== $count - 1,
                ]) style="left: {{ round($left, 3) }}%">{{ $label }}</span>
            @endforeach
        </div>
    </div>

    <div @class(['grid grid-cols-1 gap-2', '@min-[26rem]:grid-cols-2' => $list->count() === 2, '@min-[26rem]:grid-cols-3' => $list->count() === 3])>
        @foreach ($list as $item)
            <x-nui::chart.stat :label="$item['label']" :value="$item['total']" :color="$item['color']" />
        @endforeach
    </div>

    {{-- The wrapper hides the table: a caption is painted outside an sr-only table box. --}}
    <div class="sr-only">
    <table>
        <caption>{{ $title ?? 'Dados do gráfico' }}</caption>
        <thead><tr><th scope="col">Período</th>@foreach ($list as $item)<th scope="col">{{ $item['label'] }}</th>@endforeach</tr></thead>
        <tbody>
            @foreach ($labels as $index => $label)
                <tr><th scope="row">{{ $label }}</th>@foreach ($list as $item)<td>{{ Chart::number($item['data'][$index] ?? 0, $decimals) }}</td>@endforeach</tr>
            @endforeach
        </tbody>
    </table>
    </div>
</x-nui::chart.card>
