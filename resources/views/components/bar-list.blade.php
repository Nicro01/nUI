@props(['title' => null, 'groups' => [], 'metric' => null, 'format' => 'percent'])

@php
    // groups: ['Dispositivos' => [['label' => 'Desktop', 'value' => 61, 'icon' => '<svg…>'], …], 'Navegadores' => […]].
    // A plain list (no group keys) renders without tabs.
    $grouped = collect($groups)->every(fn ($group) => is_array($group) && array_is_list($group) && is_array($group[0] ?? null));
    $groupList = $grouped ? $groups : ['' => $groups];
    $show = fn (float|int $value): string => $format === 'percent'
        ? \Nicro\Nui\Support\Chart::number($value, fmod((float) $value, 1) == 0.0 ? 0 : 1).'%'
        : \Nicro\Nui\Support\Chart::number($value);
    $tabs = collect(array_keys($groupList))->mapWithKeys(fn (string $name) => [Str::slug($name) ?: 'grupo' => $name])->all();
@endphp

{{--
    Ranked list with a proportional bar behind each row: one hue, longest bar = largest value.
    Values stay as text on the right, so the bar is never the only way to read them.
    groups with keys become tabs (Nicro Tabs); metric labels the value column.
--}}
<x-nui::chart.card :title="$title" {{ $attributes }}>
    @isset($actions)
        <x-slot:actions>{{ $actions }}</x-slot:actions>
    @endisset
    @if ($grouped && count($groupList) > 1)
        <x-nui::tabs :tabs="$tabs" size="sm" class="nui-bar-list-tabs" :label="$title ?? 'Categorias'">
            @foreach ($groupList as $name => $rows)
                <x-nui::tab-panel :name="Str::slug($name) ?: 'grupo'" class="mt-3"><x-nui::chart.bar-rows :$rows :$show :$metric /></x-nui::tab-panel>
            @endforeach
        </x-nui::tabs>
    @else
        <x-nui::chart.bar-rows :rows="array_values($groupList)[0]" :$show :$metric />
    @endif
</x-nui::chart.card>
