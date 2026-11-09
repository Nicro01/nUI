{{-- One slide of <x-nui::carousel>. Width comes from the carousel's per-view; the "N de M" label is set by the carousel. --}}
<div role="group" aria-roledescription="slide"
    {{ $attributes->class('nui-carousel-slide snap-start min-w-0') }}>
    {{ $slot }}
</div>
