@props([
    'items' => [],
    'label' => 'Navegação estrutural',
    'separator' => 'slash',
    'navigate' => false,
])

@if (count($items))
    <nav {{ $attributes->merge(['aria-label' => $label])->class('min-w-0 text-sm text-base-content/65') }}>
        <ol class="flex min-w-0 flex-wrap items-center gap-x-2 gap-y-1.5">
            @foreach ($items as $item)
                <li data-breadcrumb-item class="inline-flex min-w-0 max-w-full items-center gap-2">
                    @unless ($loop->first)
                        <svg aria-hidden="true" class="size-3.5 shrink-0 text-base-content/40" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                            @if ($separator === 'chevron')
                                <path d="m8 5 5 5-5 5" />
                            @else
                                <path d="m12 4-4 12" />
                            @endif
                        </svg>
                    @endunless

                    @if (isset($item['href']))
                        <a data-breadcrumb-label href="{{ $item['href'] }}"
                            @if ($loop->last) aria-current="page" @endif
                            @if ($navigate) wire:navigate @endif
                            @class([
                                'min-w-0 rounded-sm [overflow-wrap:anywhere] underline-offset-4 transition-colors hover:text-primary hover:underline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary',
                                'font-medium text-base-content' => $loop->last,
                            ])>{{ $item['label'] }}</a>
                    @else
                        <span data-breadcrumb-label @if ($loop->last) aria-current="page" @endif
                            @class(['min-w-0 [overflow-wrap:anywhere]', 'font-medium text-base-content' => $loop->last])>{{ $item['label'] }}</span>
                    @endif
                </li>
            @endforeach
        </ol>
    </nav>
@endif
