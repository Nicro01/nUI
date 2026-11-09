@props(['paginator', 'livewire' => false, 'size' => 'md', 'summary' => true, 'label' => 'Paginação'])

@php
    $numbered = $paginator instanceof \Illuminate\Contracts\Pagination\LengthAwarePaginator;
    $current = $paginator->currentPage();
    $items = [['label' => 'Anterior', 'page' => $current - 1, 'url' => $paginator->previousPageUrl(), 'rel' => 'prev']];

    if ($numbered) {
        $window = \Illuminate\Pagination\UrlWindow::make((clone $paginator)->onEachSide(1));
        $previous = null;

        foreach (array_filter($window) as $pages) {
            foreach ($pages as $page => $url) {
                if ($previous !== null && $page > $previous + 1) {
                    $items[] = ['label' => '…'];
                }

                $items[] = ['label' => $page, 'page' => $page, 'url' => $url];
                $previous = $page;
            }
        }
    }

    $items[] = ['label' => 'Próxima', 'page' => $current + 1, 'url' => $paginator->nextPageUrl(), 'rel' => 'next'];
@endphp

@if ($paginator->hasPages())
    <nav {{ $attributes->merge(['aria-label' => $label])->class('@container flex w-full flex-col gap-3 text-base-content') }}>
        @if ($summary && $numbered)
            <p class="text-sm tabular-nums text-base-content/70">
                Mostrando <span class="font-medium text-base-content">{{ $paginator->firstItem() ?? 0 }}–{{ $paginator->lastItem() ?? 0 }}</span>
                de <span class="font-medium text-base-content">{{ $paginator->total() }}</span> resultados
            </p>
        @endif

        <ul class="flex flex-wrap items-center gap-1" role="list">
            @foreach ($items as $index => $item)
                @if (($item['rel'] ?? null) === 'next')
                    <li class="px-2 text-sm tabular-nums @min-[40rem]:hidden">
                        Página {{ $current }}@if ($numbered) de {{ $paginator->lastPage() }}@endif
                    </li>
                @endif
                <li @class(['hidden @min-[40rem]:block' => !isset($item['rel'])])>
                    @if (!isset($item['page']))
                        <span class="inline-flex h-10 min-w-8 items-center justify-center text-base-content/60" aria-hidden="true">{{ $item['label'] }}</span>
                    @else
                        @php
                            $active = !isset($item['rel']) && $item['page'] === $current;
                            $disabled = $item['url'] === null;
                            $control = new \Illuminate\View\ComponentAttributeBag([
                                'aria-label' => isset($item['rel']) ? $item['label'] : 'Página '.$item['page'],
                                'aria-current' => $active ? 'page' : null,
                                'rel' => $item['rel'] ?? null,
                            ]);

                            if ($livewire) {
                                $control = $control->merge(['wire:key' => 'pagination-'.$paginator->getPageName().'-'.$index]);

                                if (!$active && !$disabled) {
                                    $control = $control->merge([
                                        'wire:click' => 'gotoPage('.$item['page'].', '.\Illuminate\Support\Js::from($paginator->getPageName()).')',
                                        'wire:loading.attr' => 'disabled',
                                        'wire:target' => 'gotoPage',
                                    ]);
                                }
                            }
                        @endphp
                        <x-nui::button
                            :href="!$livewire && !$active && !$disabled ? $item['url'] : null"
                            :variant="$active ? 'primary' : 'ghost'"
                            :size="$size"
                            :disabled="$active || $disabled"
                            :class="\Illuminate\Support\Arr::toCssClasses(['min-w-10 tabular-nums', 'disabled:opacity-100' => $active])"
                            :attributes="$control"
                        >
                            @if (($item['rel'] ?? null) === 'prev')
                                <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="m15 18-6-6 6-6" /></svg>
                            @endif
                            <span @class(['hidden @min-[24rem]:inline' => isset($item['rel'])])>{{ $item['label'] }}</span>
                            @if (($item['rel'] ?? null) === 'next')
                                <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6" /></svg>
                            @endif
                        </x-nui::button>
                    @endif
                </li>
            @endforeach
        </ul>
    </nav>
@endif
