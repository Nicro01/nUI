@props(['title' => null, 'value' => null, 'delta' => null, 'trend' => 'up'])

{{--
    Shell shared by the chart cards: the same key-cap surface as <x-nui::card>, a title, an optional headline
    figure set in the display face (--nui-font-display, falls back to the body font) with its change tag,
    and an actions slot (period picker, filters). Content and legend tiles go in the default slot.
--}}
<section {{ $attributes->class('nui-chart-card @container flex flex-col gap-4 rounded-nui border-[length:var(--nui-border-width)] border-base-300 border-b-[3px] border-b-base-content/20 bg-base-100 p-4 text-base-content shadow-nui sm:p-5') }}>
    @if ($title || $value !== null || isset($actions))
        <header class="flex flex-wrap items-start justify-between gap-3">
            <div class="flex min-w-0 flex-col gap-1.5">
                @if ($title)<h3 class="m-0 text-sm font-semibold text-base-content/70">{{ $title }}</h3>@endif
                @if ($value !== null)
                    <p class="m-0 flex flex-wrap items-center gap-2.5">
                        <span class="nui-chart-figure text-[2.125rem] leading-none tabular-nums">{{ $value }}</span>
                        @if ($delta)
                            <x-nui::tag :variant="$trend === 'down' ? 'danger' : 'success'" size="sm">
                                <svg aria-hidden="true" viewBox="0 0 12 12" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" @class(['rotate-180' => $trend === 'down'])><path d="M6 9.5v-7m0 0L3 5.5m3-3 3 3" /></svg>
                                <span class="tabular-nums">{{ $delta }}</span>
                                <span class="sr-only">{{ $trend === 'down' ? 'de queda' : 'de alta' }}</span>
                            </x-nui::tag>
                        @endif
                    </p>
                @endif
            </div>
            @isset($actions)<div class="flex shrink-0 items-center gap-2">{{ $actions }}</div>@endisset
        </header>
    @endif
    {{ $slot }}
</section>
