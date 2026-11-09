@props([
    'label' => 'Mockup de tablet',
    'caption' => null,
    'orientation' => 'landscape',
    'statusBar' => true,
    'homeIndicator' => true,
    'stylus' => false,
    'date' => 'Seg · 9 nov',
    'time' => '9:41',
    'tone' => 'dark',
    'size' => 'md',
])

@php
    $id = $attributes->get('id') ?? 'nui-tablet-'.Str::random(6);
    $portrait = $orientation === 'portrait';
    $captionId = $caption || isset($footer) ? $id.'-caption' : null;
    $modelAttrs = $attributes->whereStartsWith(['wire:model', 'x-model']);
@endphp

{{--
    Tactile tablet frame (landscape or portrait) with symmetrical bezel, camera sensor,
    iPadOS-style status bar, optional magnetic stylus on top edge, and home indicator.
    Supports x-model / wire:model for orientation switching.
--}}
<figure id="{{ $id }}"
    role="group"
    @if ($captionId) aria-labelledby="{{ $captionId }}" @else aria-label="{{ $label }}" @endif
    data-nui-mockup="tablet"
    data-tone="{{ $tone }}"
    data-size="{{ $size }}"
    data-orientation="{{ $portrait ? 'portrait' : 'landscape' }}"
    @if ($modelAttrs->isNotEmpty())
        x-data="{ _nuiOrientation: @js($portrait ? 'portrait' : 'landscape') }"
        x-modelable="_nuiOrientation"
        x-bind:data-orientation="_nuiOrientation"
        {{ $modelAttrs }}
    @endif
    {{ $attributes->whereDoesntStartWith(['wire:model', 'x-model'])->except('id')->class('nui-mockup nui-mockup-tablet') }}>

    @isset($toolbar)
        <div class="nui-mockup-controls">
            {{ $toolbar }}
        </div>
    @endisset

    <div class="nui-tablet-chassis">
        @if ($stylus)
            <span aria-hidden="true" class="nui-tablet-stylus">
                <span class="nui-tablet-stylus-tip"></span>
                <span class="nui-tablet-stylus-band"></span>
            </span>
        @endif

        <span aria-hidden="true" class="nui-tablet-key nui-tablet-key-power"></span>
        <span aria-hidden="true" class="nui-tablet-key nui-tablet-key-up"></span>
        <span aria-hidden="true" class="nui-tablet-key nui-tablet-key-down"></span>
        <span aria-hidden="true" class="nui-tablet-camera">
            <span class="nui-tablet-lens"></span>
        </span>

        <div class="nui-tablet-screen">
            @if ($statusBar)
                <div aria-hidden="true" class="nui-tablet-statusbar">
                    <span class="nui-tablet-datetime">
                        <span class="font-semibold">{{ $time }}</span>
                        @if ($date)
                            <span class="opacity-65">{{ $date }}</span>
                        @endif
                    </span>

                    <span class="nui-tablet-multitask">
                        <span></span><span></span><span></span>
                    </span>

                    <span class="nui-tablet-signals">
                        <svg viewBox="0 0 16 12" fill="currentColor"><path d="M8 2.2c2.4 0 4.6.9 6.3 2.5l1.2-1.3A10.6 10.6 0 0 0 8 .4C5.2.4 2.6 1.4.5 3.4l1.2 1.3A9 9 0 0 1 8 2.2Zm0 3.6c1.4 0 2.7.5 3.7 1.4l1.2-1.3A7.2 7.2 0 0 0 8 4c-1.8 0-3.6.7-4.9 1.9l1.2 1.3c1-.9 2.3-1.4 3.7-1.4Zm0 3.6c-.5 0-1 .2-1.4.5L8 11.6l1.4-1.7c-.4-.3-.9-.5-1.4-.5Z" /></svg>
                        <span class="text-[0.6875rem] font-semibold tabular-nums opacity-80">100%</span>
                        <svg viewBox="0 0 27 12" fill="none"><rect x=".5" y=".5" width="22" height="11" rx="3.2" stroke="currentColor" opacity=".4" /><rect x="2" y="2" width="19" height="8" rx="2" fill="currentColor" /><path d="M24.5 4v4c.8-.3 1.3-1.1 1.3-2s-.5-1.7-1.3-2Z" fill="currentColor" opacity=".45" /></svg>
                    </span>
                </div>
            @endif

            @isset($header)
                <div class="nui-tablet-header">
                    {{ $header }}
                </div>
            @endisset

            <div class="nui-tablet-viewport">
                {{ $slot }}
            </div>

            @if ($homeIndicator)
                <div aria-hidden="true" class="nui-tablet-home-bar">
                    <span class="nui-tablet-home-pill"></span>
                </div>
            @endif
        </div>
    </div>

    @if ($caption || isset($footer))
        <figcaption id="{{ $captionId }}" class="nui-mockup-caption">
            {{ $footer ?? $caption }}
        </figcaption>
    @endif
</figure>
