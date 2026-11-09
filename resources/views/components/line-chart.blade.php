@props([
    'title' => null,
    'value' => null,
    'delta' => null,
    'trend' => 'up',
    'labels' => [],
    'series' => [],
    'compare' => null,
    'height' => 220,
    'decimals' => 0,
    'suffix' => '',
])

@use('Nicro\Nui\Support\Chart')

@php
    // series: [['label' => 'Receita', 'data' => [..]], …] up to three, in chart-ink order.
    // compare: ['label' => 'Período anterior', 'data' => [..]] drawn as a dashed ink line behind the series.
    $palette = ['var(--nui-chart-1)', 'var(--nui-chart-2)', 'var(--nui-chart-3)'];
    $labels = array_values($labels);
    $count = max(1, count($labels));
    $width = 1000;
    $plotHeight = 300;
    $format = fn (float $amount): string => Chart::number($amount, $decimals).$suffix;

    $lines = collect($series)->take(3)->values()->map(fn (array $item, int $index) => [
        'label' => $item['label'],
        'data' => array_map('floatval', array_values($item['data'] ?? [])),
        'color' => $palette[$index],
        'dashed' => false,
    ]);
    if ($compare) {
        $lines->push(['label' => $compare['label'] ?? 'Período anterior', 'data' => array_map('floatval', array_values($compare['data'] ?? [])), 'color' => 'color-mix(in oklch, var(--nui-base-content) 45%, transparent)', 'dashed' => true]);
    }

    $scale = Chart::scale((float) ($lines->flatMap(fn (array $line) => $line['data'])->max() ?? 0));
    $x = fn (int $index): float => $count === 1 ? $width / 2 : $index / ($count - 1) * $width;
    $y = fn (float $amount): float => $plotHeight - $amount / $scale['max'] * $plotHeight;

    $paths = $lines->map(fn (array $line) => $line + [
        'path' => Chart::smoothPath(array_map(fn (int $i) => [$x($i), $y($line['data'][$i])], array_keys($line['data']))),
        'end' => count($line['data']) ? ['top' => round($y(end($line['data'])) / $plotHeight * 100, 3), 'text' => $format(end($line['data']))] : null,
    ]);

    // End labels: keep at least ~9% of the height between them so close finishes never overprint.
    $gap = 18 / max(1, (int) $height) * 100;
    $placed = [];
    foreach ($paths->filter(fn (array $line) => $line['end'] && ! $line['dashed'])->sortBy(fn (array $line) => $line['end']['top']) as $key => $line) {
        $top = $line['end']['top'];
        $previous = end($placed);
        $placed[$key] = $previous === false ? $top : max($top, $previous + $gap);
    }
    $paths = $paths->map(fn (array $line, int $key) => isset($placed[$key]) ? array_replace_recursive($line, ['end' => ['label' => min(100, $placed[$key])]]) : $line);

    $points = collect($labels)->map(fn (string $label, int $index) => [
        'label' => $label,
        'left' => round($x($index) / $width * 100, 3),
        'rows' => $lines->map(fn (array $line) => [
            'label' => $line['label'],
            'value' => $format($line['data'][$index] ?? 0),
            'color' => $line['color'],
            'dashed' => $line['dashed'],
            'top' => round($y($line['data'][$index] ?? 0) / $plotHeight * 100, 3),
        ])->values()->all(),
    ])->values()->all();

    $id = 'nui-line-'.Str::random(6);
    $summary = ($title ? $title.'. ' : '').'Gráfico de linhas de '.($labels[0] ?? '').' a '.(end($labels) ?: '').': '
        .$paths->map(fn (array $line) => $line['label'].' termina em '.($line['end']['text'] ?? '0'))->implode(', ').'. Use as setas para ler cada ponto.';
@endphp

{{--
    Line chart card: trends for up to three series, plus an optional dashed comparison (the previous period).
    Each line is labelled at its end with its last value, so identity never rests on colour alone.
    Hover or focus + arrows moves a crosshair with a tooltip; a hidden table carries every value.
--}}
<x-nui::chart.card :title="$title" :value="$value" :delta="$delta" :trend="$trend" {{ $attributes }}>
    @isset($actions)
        <x-slot:actions>{{ $actions }}</x-slot:actions>
    @endisset

    <div class="grid grid-cols-[auto_minmax(0,1fr)_auto] gap-x-3"
        x-data="{
            points: @js($points),
            active: null,
            get point() { return this.active === null ? null : this.points[this.active] },
            pick(event) {
                const box = $refs.plot.getBoundingClientRect();
                this.active = Math.round(Math.min(1, Math.max(0, (event.clientX - box.left) / box.width)) * (this.points.length - 1));
            },
            step(delta) { this.active = Math.min(this.points.length - 1, Math.max(0, (this.active ?? this.points.length - 1) + delta)) },
        }">
        <div aria-hidden="true" class="relative w-10 text-right text-xs tabular-nums text-base-content/55" style="height: {{ (int) $height }}px">
            @foreach ($scale['ticks'] as $tick)
                <span class="absolute right-0 -translate-y-1/2 whitespace-nowrap" style="top: {{ round(100 - $tick / $scale['max'] * 100, 3) }}%">{{ Chart::compact($tick) }}{{ $suffix }}</span>
            @endforeach
        </div>

        <div x-ref="plot" tabindex="0" role="img" aria-label="{{ $summary }}" aria-describedby="{{ $id }}-live"
            class="relative cursor-crosshair rounded-[min(var(--nui-radius),0.375rem)] outline-none focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-primary"
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
                @foreach ($paths->sortByDesc('dashed') as $line)
                    @unless ($line['dashed'])
                        <path d="{{ $line['path'] }}" fill="none" stroke-width="6" stroke-linejoin="round" stroke-linecap="round" vector-effect="non-scaling-stroke" style="stroke: var(--nui-base-100)" />
                    @endunless
                    <path d="{{ $line['path'] }}" fill="none" stroke-width="2" stroke-linejoin="round" stroke-linecap="round" vector-effect="non-scaling-stroke"
                        @if ($line['dashed']) stroke-dasharray="5 5" @endif style="stroke: {{ $line['color'] }}" />
                @endforeach
            </svg>

            @foreach ($paths as $line)
                @if ($line['end'] && ! $line['dashed'])
                    <span aria-hidden="true" class="absolute right-0 size-2.5 translate-x-1/2 -translate-y-1/2 rounded-full ring-2 ring-base-100" style="top: {{ $line['end']['top'] }}%; background: {{ $line['color'] }}"></span>
                @endif
            @endforeach

            <template x-if="point">
                <div aria-hidden="true" class="pointer-events-none absolute inset-0">
                    <span class="absolute inset-y-0 w-px -translate-x-1/2 bg-base-content/35" x-bind:style="{ left: point.left + '%' }"></span>
                    <template x-for="row in point.rows" x-bind:key="row.label">
                        <span class="absolute size-2.5 -translate-x-1/2 -translate-y-1/2 rounded-full ring-2 ring-base-100"
                            x-bind:style="{ left: point.left + '%', top: row.top + '%', background: row.color }"></span>
                    </template>
                    <div class="nui-chart-tip absolute top-1 z-10 min-w-40 rounded-[min(var(--nui-radius),0.5rem)] bg-[oklch(23%_0.02_260)] px-3 py-2 text-xs text-[oklch(95%_0.01_85)] shadow-[0_8px_20px_-8px_oklch(0%_0_0/0.5)]"
                        x-bind:style="point.left > 60 ? { right: (100 - point.left) + '%', marginRight: '12px' } : { left: point.left + '%', marginLeft: '12px' }">
                        <p class="m-0 mb-1.5 font-semibold" x-text="point.label"></p>
                        <template x-for="row in point.rows" x-bind:key="row.label">
                            <p class="m-0 flex items-center justify-between gap-4 py-0.5">
                                <span class="flex items-center gap-1.5 opacity-75">
                                    <span class="h-0.5 w-3 rounded-full" x-bind:style="row.dashed ? { backgroundImage: 'linear-gradient(90deg, currentColor 50%, transparent 50%)', backgroundSize: '4px 2px' } : { background: row.color }"></span>
                                    <span x-text="row.label"></span>
                                </span>
                                <span class="font-semibold tabular-nums" x-text="row.value"></span>
                            </p>
                        </template>
                    </div>
                </div>
            </template>
        </div>

        {{-- End labels are absolutely placed, so the column takes the width of the longest one to stay inside the card. --}}
        @php($endWidth = $paths->filter(fn (array $line) => $line['end'] && ! $line['dashed'])->map(fn (array $line) => mb_strlen($line['end']['text']))->max() ?? 0)
        <div aria-hidden="true" class="relative text-xs font-semibold tabular-nums" style="height: {{ (int) $height }}px; width: calc({{ $endWidth }}ch + 0.75rem)">
            @foreach ($paths as $line)
                @if ($line['end'] && ! $line['dashed'])
                    <span class="absolute left-1 -translate-y-1/2 whitespace-nowrap" style="top: {{ $line['end']['label'] ?? $line['end']['top'] }}%">{{ $line['end']['text'] }}</span>
                @endif
            @endforeach
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

    <div class="flex flex-wrap gap-x-5 gap-y-2 text-xs text-base-content/70">
        @foreach ($paths as $line)
            <span class="flex items-center gap-2">
                <span aria-hidden="true" class="h-0.5 w-4 rounded-full"
                    style="{{ $line['dashed'] ? 'background-image: linear-gradient(90deg, '.$line['color'].' 50%, transparent 50%); background-size: 5px 2px' : 'background: '.$line['color'] }}"></span>
                {{ $line['label'] }}
            </span>
        @endforeach
    </div>

    {{-- The wrapper hides the table: a caption is painted outside an sr-only table box. --}}
    <div class="sr-only">
        <table>
            <caption>{{ $title ?? 'Dados do gráfico' }}</caption>
            <thead><tr><th scope="col">Período</th>@foreach ($paths as $line)<th scope="col">{{ $line['label'] }}</th>@endforeach</tr></thead>
            <tbody>
                @foreach ($points as $point)
                    <tr><th scope="row">{{ $point['label'] }}</th>@foreach ($point['rows'] as $row)<td>{{ $row['value'] }}</td>@endforeach</tr>
                @endforeach
            </tbody>
        </table>
    </div>
</x-nui::chart.card>
