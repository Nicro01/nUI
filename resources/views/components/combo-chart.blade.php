@props([
    'title' => null,
    'value' => null,
    'delta' => null,
    'trend' => 'up',
    'labels' => [],
    'bars' => [],
    'line' => [],
    'height' => 220,
])

@use('Nicro\Nui\Support\Chart')

@php
    // bars: ['label' => 'Sessões', 'data' => [..], 'total' => '83.200']
    // line: ['label' => 'Conversão', 'data' => [..], 'total' => '3,7%', 'suffix' => '%', 'decimals' => 1]
    $labels = array_values($labels);
    $count = max(1, count($labels));
    $width = 1000;
    $plotHeight = 300;
    $barData = array_map('floatval', array_values($bars['data'] ?? []));
    $lineData = array_map('floatval', array_values($line['data'] ?? []));
    $suffix = $line['suffix'] ?? '';
    $lineDecimals = (int) ($line['decimals'] ?? 1);
    $barScale = Chart::scale((float) (max($barData ?: [0])));
    $lineScale = Chart::scale((float) (max($lineData ?: [0])));
    $barColor = 'var(--nui-chart-1)';
    $lineColor = 'var(--nui-chart-2)';

    $center = fn (int $index): float => ($index + 0.5) / $count * $width;
    $lineY = fn (float $amount): float => $plotHeight - $amount / $lineScale['max'] * $plotHeight;
    $linePoints = array_map(fn (int $index) => [$center($index), $lineY($lineData[$index])], array_keys($lineData));
    $linePath = Chart::smoothPath($linePoints);
    $lineLabel = fn (float $amount): string => Chart::number($amount, $lineDecimals).$suffix;

    $points = collect($labels)->map(fn (string $label, int $index) => [
        'label' => $label,
        'left' => round($center($index) / $width * 100, 3),
        'bar' => Chart::number($barData[$index] ?? 0),
        'line' => $lineLabel($lineData[$index] ?? 0),
    ])->values()->all();

    $id = 'nui-combo-'.Str::random(6);
    $summary = ($title ? $title.'. ' : '').'Barras de '.($bars['label'] ?? '').' com linha de '.($line['label'] ?? '').', de '.($labels[0] ?? '').' a '.(end($labels) ?: '')
        .'. Eixo da esquerda: '.($bars['label'] ?? '').'; eixo da direita: '.($line['label'] ?? '').'. Use as setas para ler cada período.';
@endphp

{{--
    Combo chart card: a count as bars (left axis) and a rate as a line (right axis) over the same periods.
    The two axes carry their units and each legend tile names its axis, so the scales never get confused.
    Hover or focus + arrows highlights a period with a tooltip; a hidden table carries every value.
--}}
<x-nui::chart.card :title="$title" :value="$value" :delta="$delta" :trend="$trend" {{ $attributes }}>
    @isset($actions)
        <x-slot:actions>{{ $actions }}</x-slot:actions>
    @endisset

    <div class="grid grid-cols-[auto_minmax(0,1fr)_auto] gap-x-3 px-1"
        x-data="{
            points: @js($points),
            active: null,
            get point() { return this.active === null ? null : this.points[this.active] },
            pick(event) {
                const box = $refs.plot.getBoundingClientRect();
                const ratio = Math.min(0.9999, Math.max(0, (event.clientX - box.left) / box.width));
                this.active = Math.floor(ratio * this.points.length);
            },
            step(delta) { this.active = Math.min(this.points.length - 1, Math.max(0, (this.active ?? this.points.length - 1) + delta)) },
        }">
        <div aria-hidden="true" class="relative w-10 text-right text-xs tabular-nums text-base-content/55" style="height: {{ (int) $height }}px">
            @foreach ($barScale['ticks'] as $tick)
                <span class="absolute right-0 -translate-y-1/2 whitespace-nowrap" style="top: {{ round(100 - $tick / $barScale['max'] * 100, 3) }}%">{{ Chart::compact($tick) }}</span>
            @endforeach
        </div>

        <div x-ref="plot" tabindex="0" role="img" aria-label="{{ $summary }}" aria-describedby="{{ $id }}-live"
            class="relative cursor-crosshair rounded-[min(var(--nui-radius),0.375rem)] outline-none focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-primary"
            style="height: {{ (int) $height }}px"
            x-on:pointermove="pick($event)" x-on:pointerleave="active = null"
            x-on:focus="active ??= points.length - 1" x-on:blur="active = null"
            x-on:keydown.right.prevent="step(1)" x-on:keydown.left.prevent="step(-1)"
            x-on:keydown.home.prevent="active = 0" x-on:keydown.end.prevent="active = points.length - 1">
            @foreach ($barScale['ticks'] as $tick)
                <span aria-hidden="true" @class(['absolute inset-x-0 border-t', 'border-base-content/25' => $tick == 0, 'border-dotted border-base-content/20' => $tick != 0])
                    style="top: {{ round(100 - $tick / $barScale['max'] * 100, 3) }}%"></span>
            @endforeach

            <div aria-hidden="true" class="absolute inset-0 grid" style="grid-template-columns: repeat({{ $count }}, minmax(0, 1fr))">
                @foreach ($barData as $index => $amount)
                    <div class="flex items-end justify-center px-[22%]">
                        <span class="nui-chart-bar block w-full max-w-5 rounded-t-[4px] transition-opacity duration-150"
                            x-bind:class="active !== null && active !== {{ $index }} && 'opacity-45'"
                            style="height: {{ round($amount / $barScale['max'] * 100, 3) }}%; background: {{ $barColor }}; --nui-bar-delay: {{ $index * 25 }}ms"></span>
                    </div>
                @endforeach
            </div>

            <svg aria-hidden="true" viewBox="0 0 {{ $width }} {{ $plotHeight }}" preserveAspectRatio="none" class="nui-chart-draw pointer-events-none absolute inset-0 size-full overflow-visible">
                <path d="{{ $linePath }}" fill="none" stroke-width="5" stroke-linejoin="round" vector-effect="non-scaling-stroke" style="stroke: var(--nui-base-100)" />
                <path d="{{ $linePath }}" fill="none" stroke-width="2" stroke-linejoin="round" vector-effect="non-scaling-stroke" style="stroke: {{ $lineColor }}" />
                @foreach ($linePoints as [$pointX, $pointY])
                    <path d="M{{ round($pointX, 2) }},{{ round($pointY, 2) }} h0" stroke-width="9" stroke-linecap="round" vector-effect="non-scaling-stroke" style="stroke: var(--nui-base-100)" />
                    <path d="M{{ round($pointX, 2) }},{{ round($pointY, 2) }} h0" stroke-width="5.5" stroke-linecap="round" vector-effect="non-scaling-stroke" style="stroke: {{ $lineColor }}" />
                @endforeach
            </svg>

            <template x-if="point">
                <div aria-hidden="true" class="nui-chart-tip pointer-events-none absolute top-1 min-w-40 rounded-[min(var(--nui-radius),0.5rem)] bg-[oklch(23%_0.02_260)] px-3 py-2 text-xs text-[oklch(95%_0.01_85)] shadow-[0_8px_20px_-8px_oklch(0%_0_0/0.5)]"
                    x-bind:style="point.left > 60 ? { right: (100 - point.left) + '%', marginRight: '16px' } : { left: point.left + '%', marginLeft: '16px' }">
                    <p class="m-0 mb-1.5 font-semibold" x-text="point.label"></p>
                    <p class="m-0 flex items-center justify-between gap-4 py-0.5">
                        <span class="flex items-center gap-1.5 opacity-75"><span class="size-2 rounded-[2px]" style="background: {{ $barColor }}"></span>{{ $bars['label'] ?? '' }}</span>
                        <span class="font-semibold tabular-nums" x-text="point.bar"></span>
                    </p>
                    <p class="m-0 flex items-center justify-between gap-4 py-0.5">
                        <span class="flex items-center gap-1.5 opacity-75"><span class="size-2 rounded-full" style="background: {{ $lineColor }}"></span>{{ $line['label'] ?? '' }}</span>
                        <span class="font-semibold tabular-nums" x-text="point.line"></span>
                    </p>
                </div>
            </template>
        </div>

        <div aria-hidden="true" class="relative w-9 text-left text-xs tabular-nums text-base-content/55" style="height: {{ (int) $height }}px">
            @foreach ($lineScale['ticks'] as $tick)
                <span class="absolute left-0 -translate-y-1/2 whitespace-nowrap" style="top: {{ round(100 - $tick / $lineScale['max'] * 100, 3) }}%">{{ $lineLabel($tick) }}</span>
            @endforeach
        </div>
        <p id="{{ $id }}-live" class="sr-only" aria-live="polite"
            x-text="point ? point.label + ': ' + @js($bars['label'] ?? '') + ' ' + point.bar + ', ' + @js($line['label'] ?? '') + ' ' + point.line : ''"></p>

        <div aria-hidden="true" class="col-start-2 mt-2 grid text-center text-xs text-base-content/55" style="grid-template-columns: repeat({{ $count }}, minmax(0, 1fr))">
            @foreach ($labels as $index => $label)
                <span @class(['truncate', 'invisible @min-[34rem]:visible' => $count > 6 && $index % 2 === 1])>{{ $label }}</span>
            @endforeach
        </div>
    </div>

    <div class="grid grid-cols-1 gap-2 @min-[26rem]:grid-cols-2">
        <x-nui::chart.stat :label="($bars['label'] ?? '').' · eixo esquerdo'" :value="$bars['total'] ?? Chart::number(array_sum($barData))" :color="$barColor" />
        <x-nui::chart.stat :label="($line['label'] ?? '').' · eixo direito'" :value="$line['total'] ?? null" :color="$lineColor" />
    </div>

    {{-- The wrapper hides the table: a caption is painted outside an sr-only table box. --}}
    <div class="sr-only">
    <table>
        <caption>{{ $title ?? 'Dados do gráfico' }}</caption>
        <thead><tr><th scope="col">Período</th><th scope="col">{{ $bars['label'] ?? '' }}</th><th scope="col">{{ $line['label'] ?? '' }}</th></tr></thead>
        <tbody>
            @foreach ($points as $point)
                <tr><th scope="row">{{ $point['label'] }}</th><td>{{ $point['bar'] }}</td><td>{{ $point['line'] }}</td></tr>
            @endforeach
        </tbody>
    </table>
    </div>
</x-nui::chart.card>
