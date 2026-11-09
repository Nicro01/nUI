@props([
    'label' => 'Mockup de computador',
    'caption' => null,
    'variant' => 'laptop',
    'url' => null,
    'title' => null,
    'chrome' => true,
    'tone' => 'dark',
    'size' => 'md',
])

@php
    $id = $attributes->get('id') ?? 'nui-desktop-'.Str::random(6);
    $kind = in_array($variant, ['laptop', 'monitor', 'window'], true) ? $variant : 'laptop';
    $modelAttrs = $attributes->whereStartsWith(['wire:model', 'x-model']);
    $showChrome = $chrome && ($url !== null || $title !== null || isset($header) || isset($actions) || $kind === 'window' || $modelAttrs->isNotEmpty());
    $captionId = $caption || isset($footer) ? $id.'-caption' : null;
    $secure = $url && (str_starts_with($url, 'https://') || ! str_starts_with($url, 'http://'));
    $cleanUrl = $url ? preg_replace('#^https?://#', '', $url) : null;
@endphp

{{--
    Tactile desktop/computer frame: laptop (lid + keyboard deck), monitor (studio display + stand),
    or window (standalone desktop window). Includes optional window chrome with traffic lights,
    address bar or title bar, and actions slot. Supports x-model / wire:model on url.
--}}
<figure id="{{ $id }}"
    role="group"
    @if ($captionId) aria-labelledby="{{ $captionId }}" @else aria-label="{{ $label }}" @endif
    data-nui-mockup="desktop"
    data-variant="{{ $kind }}"
    data-tone="{{ $tone }}"
    data-size="{{ $size }}"
    @if ($modelAttrs->isNotEmpty())
        x-data="{ _nuiUrl: @js((string) ($url ?? '')) }"
        x-modelable="_nuiUrl"
        {{ $modelAttrs }}
    @endif
    {{ $attributes->whereDoesntStartWith(['wire:model', 'x-model'])->except('id')->class('nui-mockup nui-mockup-desktop') }}>

    @isset($toolbar)
        <div class="nui-mockup-controls">
            {{ $toolbar }}
        </div>
    @endisset

    <div class="nui-desktop-unit">
        <div class="nui-desktop-lid">
            @if ($kind !== 'window')
                <span aria-hidden="true" class="nui-desktop-camera">
                    <span class="nui-desktop-lens"></span>
                </span>
            @endif

            <div class="nui-desktop-screen">
                @if ($showChrome)
                    <div class="nui-desktop-chrome">
                        <span aria-hidden="true" class="nui-desktop-lights">
                            <span class="nui-desktop-dot nui-desktop-dot-close"></span>
                            <span class="nui-desktop-dot nui-desktop-dot-min"></span>
                            <span class="nui-desktop-dot nui-desktop-dot-max"></span>
                        </span>

                        @isset($header)
                            <div class="flex-1 min-w-0">{{ $header }}</div>
                        @elseif ($url !== null || $modelAttrs->isNotEmpty())
                            <div class="nui-desktop-address" aria-label="Barra de endereço">
                                @if ($secure || $url === null)
                                    <svg aria-hidden="true" class="size-3 shrink-0 opacity-55" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><rect x="3.5" y="7" width="9" height="6.5" rx="1.5" /><path d="M5.5 7V5a2.5 2.5 0 0 1 5 0v2" /></svg>
                                @endif
                                @if ($modelAttrs->isNotEmpty())
                                    <span class="truncate font-mono text-[0.75rem]" x-text="(_nuiUrl || '').replace(/^https?:\/\//, '')">{{ $cleanUrl }}</span>
                                @else
                                    <span class="truncate font-mono text-[0.75rem]">{{ $cleanUrl }}</span>
                                @endif
                            </div>
                        @elseif ($title)
                            <div class="nui-desktop-title">{{ $title }}</div>
                        @else
                            <div class="flex-1"></div>
                        @endisset

                        @isset($actions)
                            <div class="flex shrink-0 items-center gap-1.5">{{ $actions }}</div>
                        @else
                            <span aria-hidden="true" class="w-11 shrink-0 max-sm:hidden"></span>
                        @endisset
                    </div>
                @endif

                <div class="nui-desktop-viewport">
                    {{ $slot }}
                </div>
            </div>

            @if ($kind === 'monitor')
                <div aria-hidden="true" class="nui-desktop-chin">
                    <span class="nui-desktop-led"></span>
                </div>
            @endif
        </div>

        @if ($kind === 'laptop')
            <div aria-hidden="true" class="nui-desktop-deck">
                <span class="nui-desktop-notch"></span>
            </div>
        @elseif ($kind === 'monitor')
            <div aria-hidden="true" class="nui-desktop-stand">
                <span class="nui-desktop-neck"></span>
                <span class="nui-desktop-foot"></span>
            </div>
        @endif
    </div>

    @if ($caption || isset($footer))
        <figcaption id="{{ $captionId }}" class="nui-mockup-caption">
            {{ $footer ?? $caption }}
        </figcaption>
    @endif
</figure>
