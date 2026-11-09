@props(['variant' => 'primary', 'size' => 'md', 'href' => null, 'loading' => false])

@php
    $classes = [
        'relative inline-flex items-center justify-center gap-2 whitespace-nowrap [font-weight:var(--nui-control-weight)] rounded-nui transition select-none',
        'border-[length:var(--nui-border-width)] border-black/10',
        'hover:brightness-105 active:scale-[0.98] active:shadow-nui-inset',
        'focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary',
        'disabled:opacity-50 disabled:pointer-events-none aria-busy:pointer-events-none',
        '[&_svg]:shrink-0',
        match ($variant) {
            'secondary' => 'bg-secondary text-secondary-content shadow-nui-control',
            'danger' => 'bg-danger text-danger-content shadow-nui-control',
            'ghost' => 'bg-transparent text-base-content border-transparent hover:bg-base-content/[0.06]',
            default => 'bg-primary text-primary-content shadow-nui-control',
        },
        match ($size) {
            'sm' => 'h-8 px-3 text-sm [&_svg]:size-3.5',
            'lg' => 'h-12 px-6 text-lg [&_svg]:size-5',
            default => 'h-10 px-4 [&_svg]:size-4',
        },
    ];
@endphp

@if ($href)
    <a href="{{ $href }}" {{ $attributes->class($classes) }}>{{ $slot }}</a>
@else
    <button @if ($loading) aria-busy="true" @endif {{ $attributes->merge(['type' => 'button'])->class($classes) }}>
        @if ($loading)
            <x-nui::loading size="sm" label="Processando" class="[&_svg]:!size-[1em]" />
        @endif
        {{ $slot }}
    </button>
@endif
