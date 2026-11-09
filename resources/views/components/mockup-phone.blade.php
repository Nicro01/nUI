@props([
    'label' => 'Mockup de celular',
    'caption' => null,
    'orientation' => 'portrait',
    'camera' => 'island',
    'statusBar' => true,
    'homeIndicator' => true,
    'time' => '9:41',
    'tone' => 'dark',
    'size' => 'md',
])

@php
    $id = $attributes->get('id') ?? 'nui-phone-'.Str::random(6);
    $landscape = $orientation === 'landscape';
    $notch = in_array($camera, ['island', 'notch', 'punch', 'none'], true) ? $camera : 'island';
    $captionId = $caption || isset($footer) ? $id.'-caption' : null;
    $modelAttrs = $attributes->whereStartsWith(['wire:model', 'x-model']);
@endphp

{{--
    Tactile phone frame (portrait or landscape) with status bar, camera cutout (island, notch, punch, none),
    hardware side buttons, and home indicator. Supports Alpine x-model / Livewire wire:model for orientation,
    or server-side orientation prop.
--}}
<figure id="{{ $id }}"
    role="group"
    @if ($captionId) aria-labelledby="{{ $captionId }}" @else aria-label="{{ $label }}" @endif
    data-nui-mockup="phone"
    data-tone="{{ $tone }}"
    data-size="{{ $size }}"
    data-camera="{{ $notch }}"
    data-orientation="{{ $landscape ? 'landscape' : 'portrait' }}"
    @if ($modelAttrs->isNotEmpty())
        x-data="{ _nuiOrientation: @js($landscape ? 'landscape' : 'portrait') }"
        x-modelable="_nuiOrientation"
        x-bind:data-orientation="_nuiOrientation"
        {{ $modelAttrs }}
    @endif
    {{ $attributes->whereDoesntStartWith(['wire:model', 'x-model'])->except('id')->class('nui-mockup nui-mockup-phone') }}>

    @isset($toolbar)
        <div class="nui-mockup-controls">
            {{ $toolbar }}
        </div>
    @endisset

    <div class="nui-phone-chassis">
        {{-- Physical side keys (key-cap relief) --}}
        <span aria-hidden="true" class="nui-phone-key nui-phone-key-action"></span>
        <span aria-hidden="true" class="nui-phone-key nui-phone-key-up"></span>
        <span aria-hidden="true" class="nui-phone-key nui-phone-key-down"></span>
        <span aria-hidden="true" class="nui-phone-key nui-phone-key-power"></span>

        <div class="nui-phone-screen">
            @if ($statusBar || $notch !== 'none')
                <div aria-hidden="true" class="nui-phone-statusbar">
                    @if ($statusBar)
                        <span class="nui-phone-time">{{ $time }}</span>
                    @else
                        <span></span>
                    @endif

                    @if ($notch === 'island')
                        <span class="nui-phone-camera nui-phone-island">
                            <span class="nui-phone-lens"></span>
                        </span>
                    @elseif ($notch === 'notch')
                        <span class="nui-phone-camera nui-phone-notch">
                            <span class="nui-phone-speaker"></span>
                            <span class="nui-phone-lens"></span>
                        </span>
                    @elseif ($notch === 'punch')
                        <span class="nui-phone-camera nui-phone-punch">
                            <span class="nui-phone-lens"></span>
                        </span>
                    @endif

                    @if ($statusBar)
                        <span class="nui-phone-signals">
                            <svg viewBox="0 0 18 12" fill="currentColor"><rect x="0" y="8" width="3" height="4" rx="1" /><rect x="5" y="5.5" width="3" height="6.5" rx="1" /><rect x="10" y="3" width="3" height="9" rx="1" /><rect x="15" y="0" width="3" height="12" rx="1" /></svg>
                            <svg viewBox="0 0 16 12" fill="currentColor"><path d="M8 2.2c2.4 0 4.6.9 6.3 2.5l1.2-1.3A10.6 10.6 0 0 0 8 .4C5.2.4 2.6 1.4.5 3.4l1.2 1.3A9 9 0 0 1 8 2.2Zm0 3.6c1.4 0 2.7.5 3.7 1.4l1.2-1.3A7.2 7.2 0 0 0 8 4c-1.8 0-3.6.7-4.9 1.9l1.2 1.3c1-.9 2.3-1.4 3.7-1.4Zm0 3.6c-.5 0-1 .2-1.4.5L8 11.6l1.4-1.7c-.4-.3-.9-.5-1.4-.5Z" /></svg>
                            <svg viewBox="0 0 27 12" fill="none"><rect x=".5" y=".5" width="22" height="11" rx="3.2" stroke="currentColor" opacity=".4" /><rect x="2" y="2" width="16" height="8" rx="2" fill="currentColor" /><path d="M24.5 4v4c.8-.3 1.3-1.1 1.3-2s-.5-1.7-1.3-2Z" fill="currentColor" opacity=".45" /></svg>
                        </span>
                    @else
                        <span></span>
                    @endif
                </div>
            @endif

            @isset($header)
                <div class="nui-phone-header">
                    {{ $header }}
                </div>
            @endisset

            <div class="nui-phone-viewport">
                {{ $slot }}
            </div>

            @if ($homeIndicator)
                <div aria-hidden="true" class="nui-phone-home-bar">
                    <span class="nui-phone-home-pill"></span>
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
