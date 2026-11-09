@props(['title' => null, 'rings' => [], 'size' => 220])

@php
    // Up to three rings, outermost first, in the fixed chart-ink order (never cycled).
    $palette = ['var(--nui-chart-1)', 'var(--nui-chart-2)', 'var(--nui-chart-3)'];
    $stroke = 16;
    $gap = 5;
    $items = collect($rings)->take(3)->values()->map(function (array $ring, int $index) use ($palette, $stroke, $gap) {
        $goal = max(1e-9, (float) ($ring['goal'] ?? 100));
        $ratio = max(0, (float) ($ring['value'] ?? 0)) / $goal;

        return [
            'label' => $ring['label'],
            'display' => $ring['display'] ?? \Nicro\Nui\Support\Chart::number($ring['value'] ?? 0),
            'percent' => (int) round($ratio * 100),
            'progress' => min(1, $ratio),
            'radius' => 100 - $stroke / 2 - $index * ($stroke + $gap),
            'color' => $palette[$index],
        ];
    });
    $summary = $items->map(fn (array $ring): string => $ring['label'].': '.$ring['display'].', '.$ring['percent'].'% da meta')->implode('; ');
@endphp

{{--
    Activity rings: progress toward up to three goals, outermost ring first.
    rings: [['label' => 'Movimento', 'value' => 1592, 'goal' => 2000, 'display' => '1.592 kcal'], …].
    Tiles name each ring (colour is never the only cue); the rings draw in once, and stay still with reduced motion.
--}}
<x-nui::chart.card :title="$title" {{ $attributes }}>
    @isset($actions)
        <x-slot:actions>{{ $actions }}</x-slot:actions>
    @endisset
    <div class="grid grid-cols-1 gap-2 @min-[19rem]:grid-cols-3">
        @foreach ($items as $ring)
            <x-nui::chart.stat :label="$ring['label']" :value="$ring['display']" :color="$ring['color']" />
        @endforeach
    </div>
    <div class="flex justify-center px-2 pb-3 pt-2">
        <svg role="img" aria-label="{{ $summary }}" viewBox="0 0 200 200" class="nui-rings w-full -rotate-90" style="max-width: {{ (int) $size }}px">
            @foreach ($items as $ring)
                <circle cx="100" cy="100" r="{{ $ring['radius'] }}" fill="none" stroke-width="{{ $stroke }}"
                    style="stroke: color-mix(in oklch, {{ $ring['color'] }} 18%, transparent)" />
                <circle cx="100" cy="100" r="{{ $ring['radius'] }}" fill="none" stroke-width="{{ $stroke }}" stroke-linecap="round"
                    pathLength="100" stroke-dasharray="{{ round($ring['progress'] * 100, 2) }} 100" class="nui-ring"
                    style="stroke: {{ $ring['color'] }}; --nui-ring-delay: {{ $loop->index * 90 }}ms">
                    <title>{{ $ring['label'] }}: {{ $ring['percent'] }}% da meta</title>
                </circle>
            @endforeach
        </svg>
    </div>
</x-nui::chart.card>
