@props([
    'title' => null,
    'description' => null,
    'image' => null,
    'imageAlt' => '',
    'imagePosition' => 'top',
    'href' => null,
    'variant' => 'default',
    'padding' => 'md',
    'hover' => false,
])

@php
    $pad = match ($padding) {
        'none' => '',
        'sm' => 'p-4',
        'lg' => 'p-8',
        default => 'p-6',
    };
    $footerPad = match ($padding) {
        'sm' => 'px-4',
        'lg' => 'px-8',
        default => 'px-6',
    };
    $side = $image && $imagePosition === 'start';
    $titleId = $title ? 'card-'.Str::random(6) : null;
@endphp

{{--
    Surface for grouped content.
    variant: default | outline | elevated | subtle · padding: none | sm | md | lg · hover: lift + image zoom (opt-in)
    image + imagePosition (top | start) · href turns the whole card into one link (focus ring on the card).
    Slots: header (right of the title), default, actions (buttons, right-aligned), footer (full width, separated).
--}}
<article @if ($titleId) aria-labelledby="{{ $titleId }}" @endif
    {{ $attributes->class([
        'group/card relative flex overflow-hidden text-base-content rounded-nui transition duration-300',
        $side ? 'flex-col sm:flex-row' : 'flex-col',
        match ($variant) {
            'outline' => 'bg-transparent border border-base-content/15 border-b-[3px] border-b-base-content/20',
            'elevated' => 'bg-base-100 shadow-[0_1px_2px_oklch(0%_0_0/0.06),0_18px_40px_-18px_oklch(0%_0_0/0.3)]',
            'subtle' => 'bg-base-200',
            default => 'bg-base-100 shadow-nui border-[length:var(--nui-border-width)] border-base-300 border-b-[3px] border-b-base-content/20',
        },
        $hover ? 'hover:-translate-y-0.5 hover:shadow-[0_1px_2px_oklch(0%_0_0/0.06),0_18px_40px_-18px_oklch(0%_0_0/0.35)]' : '',
        $href ? 'has-[a[data-card-link]:focus-visible]:outline-2 has-[a[data-card-link]:focus-visible]:outline-offset-2 has-[a[data-card-link]:focus-visible]:outline-primary' : '',
    ]) }}>
    @if ($image)
        <div @class(['shrink-0 overflow-hidden bg-base-200', $side ? 'sm:w-2/5 aspect-[16/10] sm:aspect-auto' : 'aspect-[16/9]'])>
            <img src="{{ $image }}" alt="{{ $imageAlt }}" loading="lazy" @class(['size-full object-cover transition duration-500', 'group-hover/card:scale-[1.03]' => $hover])>
        </div>
    @endif

    {{-- Body and footer share one column, so a side image never squeezes them. --}}
    <div class="flex flex-1 flex-col min-w-0">
        <div class="flex flex-1 flex-col {{ $pad }}">
            @if ($title || $description || isset($header))
                <div class="flex items-start gap-3 mb-4 last:mb-0">
                    <div class="flex-1 min-w-0">
                        @if ($title)
                            <h3 id="{{ $titleId }}" class="text-lg font-semibold leading-snug">
                                @if ($href)
                                    {{-- Stretched link covers the whole card; header/actions/footer sit above it and stay clickable. --}}
                                    <a href="{{ $href }}" data-card-link class="outline-none after:absolute after:inset-0 after:content-['']">{{ $title }}</a>
                                @else
                                    {{ $title }}
                                @endif
                            </h3>
                        @endif
                        @if ($description)
                            <p class="mt-1 text-sm text-base-content/65">{{ $description }}</p>
                        @endif
                    </div>
                    @isset($header)
                        <div class="relative z-10 shrink-0">{{ $header }}</div>
                    @endisset
                </div>
            @endif

            @if ($slot->isNotEmpty())
                <div class="flex-1">{{ $slot }}</div>
            @endif

            @isset($actions)
                <div class="relative z-10 mt-6 flex flex-wrap justify-end gap-2">{{ $actions }}</div>
            @endisset
        </div>

        @isset($footer)
            <div class="relative z-10 {{ $footerPad }} py-3 border-t border-base-content/[0.08] bg-base-content/[0.02] text-sm text-base-content/70">{{ $footer }}</div>
        @endisset
    </div>
</article>
