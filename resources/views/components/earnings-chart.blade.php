@props([
    'title' => null,
    'periods' => [],
    'default' => null,
    'prefix' => 'R$ ',
    'height' => 200,
])

@use('Nicro\Nui\Support\Chart')

@php
    // periods: ['semanal' => ['label' => 'Semanal', 'value' => 'R$ 7.462', 'delta' => '+14,8%', 'trend' => 'up', 'labels' => [..], 'data' => [..]], …]
    $periods = collect($periods)->map(function (array $period, string $key) use ($prefix) {
        $data = array_map('floatval', array_values($period['data'] ?? []));
        $scale = Chart::scale((float) max($data ?: [0]));

        return [
            'key' => $key,
            'label' => $period['label'] ?? $key,
            'value' => $period['value'] ?? $prefix.Chart::number(array_sum($data)),
            'delta' => $period['delta'] ?? null,
            'trend' => $period['trend'] ?? 'up',
            'labels' => array_values($period['labels'] ?? []),
            'data' => $data,
            'scale' => $scale,
            'points' => collect($period['labels'] ?? [])->values()->map(fn (string $label, int $index) => [
                'label' => $label,
                'value' => $prefix.Chart::number($data[$index] ?? 0),
                'left' => count($data) ? round(($index + 0.5) / count($data) * 100, 3) : 50,
            ])->all(),
        ];
    });
    $selected = (string) ($default ?? $periods->keys()->first());
    $id = 'nui-earnings-'.Str::random(6);
@endphp

{{--
    Earnings card: one money figure per period as a column of brass ink in a key-cap track (the track is the
    period's room, the ink what was earned). Several periods switch with a segmented control; each keeps its
    own headline figure and change tag. Hover or focus + arrows reads a column; a hidden table has every value.
--}}
<x-nui::chart.card :title="$title" {{ $attributes }}
    x-data="{ period: {{ Js::from($selected) }}, active: null }">
    @if ($periods->count() > 1)
        <x-slot:actions>
            <div role="tablist" aria-label="Período" class="flex gap-1 rounded-nui border border-base-300 bg-base-200 p-1 shadow-nui-inset"
                x-on:keydown.right.prevent="$focus.wrap().next(); $focus.focused().click()" x-on:keydown.left.prevent="$focus.wrap().previous(); $focus.focused().click()">
                @foreach ($periods as $period)
                    <button type="button" role="tab" id="{{ $id }}-tab-{{ $period['key'] }}" aria-controls="{{ $id }}-panel-{{ $period['key'] }}"
                        x-bind:aria-selected="period === @js($period['key'])" x-bind:tabindex="period === @js($period['key']) ? 0 : -1"
                        aria-selected="{{ $period['key'] === $selected ? 'true' : 'false' }}" tabindex="{{ $period['key'] === $selected ? 0 : -1 }}"
                        x-on:click="period = @js($period['key']); active = null"
                        class="nui-earnings-tab rounded-[calc(var(--nui-radius)-0.25rem)] px-3 py-1.5 text-sm font-medium text-base-content/60 outline-none transition-colors hover:text-base-content focus-visible:outline-2 focus-visible:-outline-offset-2 focus-visible:outline-primary aria-selected:bg-base-100 aria-selected:text-base-content aria-selected:shadow-nui-control">
                        {{ $period['label'] }}
                    </button>
                @endforeach
            </div>
        </x-slot:actions>
    @endif

    @foreach ($periods as $period)
        @php($count = max(1, count($period['data'])))
        <div id="{{ $id }}-panel-{{ $period['key'] }}" @if ($periods->count() > 1) role="tabpanel" aria-labelledby="{{ $id }}-tab-{{ $period['key'] }}" @endif
            class="flex flex-col gap-4" @if ($period['key'] !== $selected) hidden @endif x-bind:hidden="period !== @js($period['key'])"
            x-data="{ points: @js($period['points']), get point() { return active === null ? null : this.points[active] } }">
            <p class="m-0 flex flex-wrap items-center gap-2.5">
                <span class="nui-chart-figure text-[2.125rem] leading-none tabular-nums">{{ $period['value'] }}</span>
                @if ($period['delta'])
                    <x-nui::tag :variant="$period['trend'] === 'down' ? 'danger' : 'success'" size="sm">
                        <svg aria-hidden="true" viewBox="0 0 12 12" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" @class(['rotate-180' => $period['trend'] === 'down'])><path d="M6 9.5v-7m0 0L3 5.5m3-3 3 3" /></svg>
                        <span class="tabular-nums">{{ $period['delta'] }}</span>
                        <span class="sr-only">{{ $period['trend'] === 'down' ? 'de queda' : 'de alta' }}</span>
                    </x-nui::tag>
                @endif
            </p>

            <div class="grid grid-cols-[auto_minmax(0,1fr)] gap-x-3">
                <div aria-hidden="true" class="relative w-12 text-right text-xs tabular-nums text-base-content/55" style="height: {{ (int) $height }}px">
                    @foreach ($period['scale']['ticks'] as $tick)
                        <span class="absolute right-0 -translate-y-1/2 whitespace-nowrap" style="top: {{ round(100 - $tick / $period['scale']['max'] * 100, 3) }}%">{{ $prefix.Chart::compact($tick) }}</span>
                    @endforeach
                </div>

                <div tabindex="0" role="img" aria-describedby="{{ $id }}-{{ $period['key'] }}-live"
                    aria-label="{{ ($title ? $title.', ' : '').$period['label'] }}: {{ $period['value'] }}. Use as setas para ler cada coluna."
                    class="relative grid gap-[clamp(0.25rem,1.6cqw,0.75rem)] rounded-[min(var(--nui-radius),0.375rem)] outline-none focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-primary"
                    style="height: {{ (int) $height }}px; grid-template-columns: repeat({{ $count }}, minmax(0, 1fr))"
                    x-on:pointerleave="active = null" x-on:focus="active ??= points.length - 1" x-on:blur="active = null"
                    x-on:keydown.right.prevent="active = Math.min(points.length - 1, (active ?? -1) + 1)"
                    x-on:keydown.left.prevent="active = Math.max(0, (active ?? points.length) - 1)">
                    @foreach ($period['data'] as $index => $amount)
                        <div class="nui-earnings-track relative flex items-end overflow-hidden" x-on:pointerenter="active = {{ $index }}">
                            <span class="nui-earnings-bar block w-full" x-bind:class="active !== null && active !== {{ $index }} && 'opacity-55'"
                                style="height: max(0.5rem, {{ round($amount / $period['scale']['max'] * 100, 3) }}%); --nui-bar-delay: {{ $index * 30 }}ms"></span>
                        </div>
                    @endforeach

                    <template x-if="point">
                        <div aria-hidden="true" class="nui-chart-tip pointer-events-none absolute top-1 z-10 rounded-[min(var(--nui-radius),0.5rem)] bg-[oklch(23%_0.02_260)] px-3 py-2 text-xs text-[oklch(95%_0.01_85)] shadow-[0_8px_20px_-8px_oklch(0%_0_0/0.5)]"
                            x-bind:style="point.left > 60 ? { right: (100 - point.left) + '%', marginRight: '18px' } : { left: point.left + '%', marginLeft: '18px' }">
                            <p class="m-0 opacity-75" x-text="point.label"></p>
                            <p class="m-0 mt-0.5 text-sm font-semibold tabular-nums" x-text="point.value"></p>
                        </div>
                    </template>
                </div>
                <p id="{{ $id }}-{{ $period['key'] }}-live" class="sr-only" aria-live="polite" x-text="point ? point.label + ': ' + point.value : ''"></p>

                <div aria-hidden="true" class="col-start-2 mt-2 grid gap-[clamp(0.25rem,1.6cqw,0.75rem)] text-center text-xs text-base-content/55" style="grid-template-columns: repeat({{ $count }}, minmax(0, 1fr))">
                    @foreach ($period['labels'] as $index => $label)
                        <span @class(['truncate', 'invisible @min-[30rem]:visible' => $count > 8 && $index % 2 === 1])>{{ $label }}</span>
                    @endforeach
                </div>
            </div>

            {{-- The wrapper hides the table: a caption is painted outside an sr-only table box. --}}
            <div class="sr-only">
                <table>
                    <caption>{{ ($title ? $title.', ' : '').$period['label'] }}</caption>
                    <thead><tr><th scope="col">Período</th><th scope="col">Valor</th></tr></thead>
                    <tbody>
                        @foreach ($period['points'] as $point)
                            <tr><th scope="row">{{ $point['label'] }}</th><td>{{ $point['value'] }}</td></tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endforeach
</x-nui::chart.card>
