@props(['variant' => 'spinner', 'size' => 'md', 'label' => 'Carregando'])

@php
    $box = match ($size) {
        'xs' => 'size-3',
        'sm' => 'size-4',
        'lg' => 'size-8',
        'xl' => 'size-12',
        default => 'size-6',
    };
@endphp

{{-- Loading indicator. variant: spinner | dots | bars | ring. Inherits the text colour (use text-primary etc.). --}}
<span role="status" {{ $attributes->class('inline-flex items-center justify-center shrink-0 text-current') }}>
    @switch($variant)
        @case('dots')
            <span aria-hidden="true" class="{{ $box }} inline-flex items-center justify-between">
                @foreach ([0, 150, 300] as $delay)
                    <span class="nui-loading-dot w-[22%] aspect-square rounded-full bg-current" style="animation-delay: {{ $delay }}ms"></span>
                @endforeach
            </span>
            @break
        @case('bars')
            <span aria-hidden="true" class="{{ $box }} inline-flex items-end justify-between">
                @foreach ([0, 120, 240, 360] as $delay)
                    <span class="nui-loading-bar w-[16%] h-full rounded-full bg-current" style="animation-delay: {{ $delay }}ms"></span>
                @endforeach
            </span>
            @break
        @case('ring')
            <svg aria-hidden="true" class="{{ $box }} animate-spin [animation-duration:1.4s]" viewBox="0 0 24 24" fill="none">
                <circle cx="12" cy="12" r="9" stroke="currentColor" stroke-opacity="0.18" stroke-width="3" />
                <circle class="nui-loading-ring" cx="12" cy="12" r="9" stroke="currentColor" stroke-width="3" stroke-linecap="round" />
            </svg>
            @break
        @default
            <svg aria-hidden="true" class="{{ $box }} animate-spin" viewBox="0 0 24 24" fill="none">
                <circle cx="12" cy="12" r="9" stroke="currentColor" stroke-opacity="0.2" stroke-width="3" />
                <path d="M21 12a9 9 0 0 0-9-9" stroke="currentColor" stroke-width="3" stroke-linecap="round" />
            </svg>
    @endswitch
    <span class="sr-only">{{ $label }}</span>
</span>
