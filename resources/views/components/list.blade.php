@props([
    'items' => [],
    'divided' => true,
    'dense' => false,
    'navigate' => false,
    'empty' => 'Nenhum item para exibir.',
])

<ul role="list" {{ $attributes->class([
    'min-w-0 overflow-hidden rounded-nui border-[length:var(--nui-border-width)] border-base-300 bg-base-100 text-base-content shadow-nui',
    'divide-y divide-base-content/10' => $divided,
    '[&>li>[data-nui-list-row]]:!py-2.5 [&>li>[data-nui-list-row]]:min-h-11' => $dense,
]) }}>
    @foreach ($items as $item)
        @php($entry = is_array($item) ? $item : ['label' => (string) $item])
        <x-nui::list.item :wire:key="$entry['id'] ?? null" :label="$entry['label'] ?? ''" :description="$entry['description'] ?? null"
            :href="$entry['href'] ?? null" :active="(bool) ($entry['active'] ?? false)" :disabled="(bool) ($entry['disabled'] ?? false)">
            @if (isset($entry['badge']))
                <x-slot:trailing>
                    <x-nui::tag size="sm" :variant="$entry['badgeVariant'] ?? 'neutral'" class="max-w-full" :title="(string) $entry['badge']">
                        <span class="truncate">{{ $entry['badge'] }}</span>
                    </x-nui::tag>
                </x-slot:trailing>
            @endif
        </x-nui::list.item>
    @endforeach

    {{ $slot }}

    @if (count($items) === 0 && $slot->isEmpty())
        <li role="presentation" class="px-4 py-8 text-center text-sm text-base-content/70">
            <span role="status">{{ $empty }}</span>
        </li>
    @endif
</ul>
