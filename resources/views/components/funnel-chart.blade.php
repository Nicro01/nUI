@props([
    'title' => null,
    'value' => null,
    'delta' => null,
    'trend' => 'up',
    'stages' => [],
    'height' => 180,
])

@use('Nicro\Nui\Support\Chart')

@php
    // stages: [['label' => 'Abriram o link', 'value' => 197], ['label' => 'Começaram', 'value' => 110], …], widest first.
    $stages = collect($stages)->values()->map(fn (array $stage) => ['label' => $stage['label'], 'value' => max(0, (float) $stage['value'])]);
    $count = max(1, $stages->count());
    $first = max(1e-9, (float) ($stages->first()['value'] ?? 0));
    $width = 1000;
    $plotHeight = 300;
    $middle = $plotHeight / 2;
    $span = $width / $count;
    $half = fn (float $amount): float => max(3, $amount / $first * $plotHeight * 0.42);

    $shape = function (int $index, float $grow) use ($stages, $span, $middle, $half): string {
        $here = $half($stages[$index]['value']) + $grow;
        $next = $half(($stages[$index + 1] ?? $stages[$index])['value']) + $grow;
        $start = $index * $span;
        $end = $start + $span;
        $flat = $start + $span * 0.42;
        $bend = $start + $span * 0.74;
        $point = fn (float $x, float $y): string => round($x, 2).','.round($y, 2);

        return 'M'.$point($start, $middle - $here).' L'.$point($flat, $middle - $here)
            .' C'.$point($bend, $middle - $here).' '.$point($bend, $middle - $next).' '.$point($end, $middle - $next)
            .' L'.$point($end, $middle + $next)
            .' C'.$point($bend, $middle + $next).' '.$point($bend, $middle + $here).' '.$point($flat, $middle + $here)
            .' L'.$point($start, $middle + $here).' Z';
    };

    $points = $stages->map(fn (array $stage, int $index) => [
        'label' => $stage['label'],
        'value' => Chart::number($stage['value']),
        'total' => Chart::number($stage['value'] / $first * 100).'%',
        'step' => $index === 0 ? null : Chart::number($stage['value'] / max(1e-9, $stages[$index - 1]['value']) * 100).'% da etapa anterior',
        'left' => round(($index + 0.21) / $count * 100, 3),
    ])->all();

    $id = 'nui-funnel-'.Str::random(6);
    $summary = ($title ? $title.'. ' : '').'Funil com '.$count.' etapas: '
        .collect($points)->map(fn (array $point) => $point['label'].' '.$point['value'].' ('.$point['total'].')')->implode(', ').'. Use as setas para ler cada etapa.';
@endphp

{{--
    Funnel card: how many people survive each step. One ink for the whole flow (height carries the count, the
    stages are one population), the share of the first step on each band, and the step-to-step rate on hover.
--}}
<x-nui::chart.card :title="$title" :value="$value" :delta="$delta" :trend="$trend" {{ $attributes }}>
    @isset($actions)
        <x-slot:actions>{{ $actions }}</x-slot:actions>
    @endisset

    <div x-data="{ points: @js($points), active: null, get point() { return this.active === null ? null : this.points[this.active] } }">
        <div tabindex="0" role="img" aria-label="{{ $summary }}" aria-describedby="{{ $id }}-live"
            class="relative grid rounded-[min(var(--nui-radius),0.375rem)] outline-none focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-primary"
            style="height: {{ (int) $height }}px; grid-template-columns: repeat({{ $count }}, minmax(0, 1fr))"
            x-on:pointerleave="active = null" x-on:focus="active ??= 0" x-on:blur="active = null"
            x-on:keydown.right.prevent="active = Math.min(points.length - 1, (active ?? -1) + 1)"
            x-on:keydown.left.prevent="active = Math.max(0, (active ?? points.length) - 1)">
            <svg aria-hidden="true" viewBox="0 0 {{ $width }} {{ $plotHeight }}" preserveAspectRatio="none" class="nui-funnel-draw pointer-events-none absolute inset-0 size-full">
                @foreach ($stages as $index => $stage)
                    <path d="{{ $shape($index, 22) }}" class="nui-funnel-halo" />
                @endforeach
                @foreach ($stages as $index => $stage)
                    <path d="{{ $shape($index, 0) }}" class="nui-funnel-band transition-opacity duration-150"
                        x-bind:class="active !== null && active !== {{ $index }} && 'opacity-50'" />
                @endforeach
                @for ($index = 1; $index < $count; $index++)
                    <line x1="{{ round($index * $span, 2) }}" x2="{{ round($index * $span, 2) }}" y1="0" y2="{{ $plotHeight }}" stroke-width="3" vector-effect="non-scaling-stroke" style="stroke: var(--nui-base-100)" />
                @endfor
            </svg>

            @foreach ($points as $index => $point)
                <div class="relative flex items-center" x-on:pointerenter="active = {{ $index }}">
                    <span aria-hidden="true" class="ml-[21%] -translate-x-1/2 rounded-full border-b-2 border-b-base-content/20 bg-base-100 px-2 py-0.5 text-xs font-semibold tabular-nums text-base-content shadow-nui-control">{{ $point['total'] }}</span>
                </div>
            @endforeach

            <template x-if="point">
                <div aria-hidden="true" class="nui-chart-tip pointer-events-none absolute top-1 z-10 rounded-[min(var(--nui-radius),0.5rem)] bg-[oklch(23%_0.02_260)] px-3 py-2 text-xs text-[oklch(95%_0.01_85)] shadow-[0_8px_20px_-8px_oklch(0%_0_0/0.5)]"
                    x-bind:style="point.left > 55 ? { right: (100 - point.left) + '%', marginRight: '28px' } : { left: point.left + '%', marginLeft: '28px' }">
                    <p class="m-0 font-semibold" x-text="point.label"></p>
                    <p class="m-0 mt-1 flex justify-between gap-4"><span class="opacity-75">Pessoas</span><span class="font-semibold tabular-nums" x-text="point.value"></span></p>
                    <p class="m-0 flex justify-between gap-4"><span class="opacity-75">Do total</span><span class="font-semibold tabular-nums" x-text="point.total"></span></p>
                    <template x-if="point.step"><p class="m-0 mt-1 opacity-75" x-text="point.step"></p></template>
                </div>
            </template>
        </div>
        <p id="{{ $id }}-live" class="sr-only" aria-live="polite"
            x-text="point ? point.label + ': ' + point.value + ', ' + point.total + ' do total' + (point.step ? ', ' + point.step : '') : ''"></p>
    </div>

    <ol @class(['m-0 grid list-none grid-cols-2 gap-2 p-0', '@min-[30rem]:grid-cols-3' => $count === 3, '@min-[30rem]:grid-cols-4' => $count >= 4])>
        @foreach ($points as $index => $point)
            <li class="flex min-w-0 flex-col gap-1 rounded-[min(var(--nui-radius-field),0.5rem)] border border-base-content/12 border-b-2 border-b-base-content/22 bg-base-200 px-3 py-2.5">
                <span class="flex min-w-0 items-center gap-2 text-xs font-medium text-base-content/70">
                    <span aria-hidden="true" class="grid size-4 shrink-0 place-items-center rounded-[3px] bg-[var(--nui-chart-1)] text-[0.625rem] font-bold text-[var(--nui-base-100)]">{{ $index + 1 }}</span>
                    <span class="truncate">{{ $point['label'] }}</span>
                </span>
                <span class="text-[0.9375rem] font-semibold tabular-nums">{{ $point['value'] }}</span>
                @if ($point['step'])<span class="text-[0.6875rem] text-base-content/60">{{ $point['step'] }}</span>@endif
            </li>
        @endforeach
    </ol>
</x-nui::chart.card>
