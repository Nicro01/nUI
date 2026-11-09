@props(['text', 'position' => 'top'])

@php
    $tipId = 'nui-tip-'.Str::random(6);
    $place = match ($position) {
        'bottom' => 'top-full left-1/2 -translate-x-1/2 mt-2 origin-top',
        'left' => 'right-full top-1/2 -translate-y-1/2 mr-2 origin-right',
        'right' => 'left-full top-1/2 -translate-y-1/2 ml-2 origin-left',
        default => 'bottom-full left-1/2 -translate-x-1/2 mb-2 origin-bottom',
    };
    $arrow = match ($position) {
        'bottom' => 'bottom-full left-1/2 -translate-x-1/2 border-b-[oklch(23%_0.02_260)] border-x-transparent border-t-transparent',
        'left' => 'left-full top-1/2 -translate-y-1/2 border-l-[oklch(23%_0.02_260)] border-y-transparent border-r-transparent',
        'right' => 'right-full top-1/2 -translate-y-1/2 border-r-[oklch(23%_0.02_260)] border-y-transparent border-l-transparent',
        default => 'top-full left-1/2 -translate-x-1/2 border-t-[oklch(23%_0.02_260)] border-x-transparent border-b-transparent',
    };
@endphp

{{--
    Short hint on hover and keyboard focus. Wraps its trigger; the first focusable child gets aria-describedby.
    Esc hides it (WCAG 1.4.13). position: top | bottom | left | right. Grows out of its anchor like the other popovers.
--}}
<span {{ $attributes->class('group/tip relative inline-flex') }}
    x-data="{ hidden: false }"
    x-init="$el.querySelector('a, button, input, select, textarea, [tabindex]')?.setAttribute('aria-describedby', @js($tipId))"
    x-on:keydown.escape.window="hidden = true" x-on:mouseleave="hidden = false" x-on:focusout="hidden = false">
    {{ $slot }}
    <span id="{{ $tipId }}" role="tooltip" x-bind:class="hidden && '!opacity-0 !scale-90'"
        class="pointer-events-none absolute z-40 {{ $place }} w-max max-w-64 px-2.5 py-1.5 rounded-[min(var(--nui-radius),0.5rem)] bg-[oklch(23%_0.02_260)] text-[oklch(95%_0.01_85)] text-xs font-medium leading-snug shadow-[0_8px_20px_-8px_oklch(0%_0_0/0.5)]
            opacity-0 scale-90 transition duration-200 ease-[cubic-bezier(0.16,1,0.3,1)] [transition-delay:0ms]
            group-hover/tip:opacity-100 group-hover/tip:scale-100 group-hover/tip:[transition-delay:120ms]
            group-has-[:focus-visible]/tip:opacity-100 group-has-[:focus-visible]/tip:scale-100">
        {{ $text }}
        <span aria-hidden="true" class="absolute size-0 border-4 {{ $arrow }}"></span>
    </span>
</span>
