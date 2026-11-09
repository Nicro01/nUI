@props(['value' => 0, 'max' => 100, 'size' => 'md', 'thickness' => null, 'variant' => 'primary', 'label' => null, 'showValue' => true])

@php
    $percent = max(0, min(100, round($value / max($max, 1) * 100)));
    $pixels = match ($size) {
        'sm' => 48,
        'lg' => 112,
        'xl' => 144,
        default => 80,
    };
    $stroke = $thickness ?? max(4, round($pixels / 11));
    $radius = (100 - $stroke) / 2;
    $circumference = 2 * M_PI * $radius;
    $tone = match ($variant) {
        'success' => 'text-success',
        'warning' => 'text-warning',
        'danger' => 'text-danger',
        'neutral' => 'text-base-content/60',
        default => 'text-primary',
    };
@endphp

{{-- Circular progress in SVG. The arc animates on change; the slot replaces the centre text. --}}
<div role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ $percent }}" @if ($label) aria-label="{{ $label }}" @endif
    {{ $attributes->class('relative inline-grid place-items-center shrink-0') }} style="width: {{ $pixels }}px; height: {{ $pixels }}px">
    <svg aria-hidden="true" class="absolute inset-0 -rotate-90 {{ $tone }}" viewBox="0 0 100 100">
        <circle cx="50" cy="50" r="{{ $radius }}" fill="none" stroke="currentColor" stroke-opacity="0.14" stroke-width="{{ $stroke }}" />
        <circle class="nui-radial-arc transition-[stroke-dashoffset] duration-700 ease-[cubic-bezier(0.16,1,0.3,1)]" cx="50" cy="50" r="{{ $radius }}" fill="none" stroke="currentColor"
            stroke-width="{{ $stroke }}" stroke-linecap="round" stroke-dasharray="{{ $circumference }}" stroke-dashoffset="{{ $circumference * (1 - $percent / 100) }}"
            style="--nui-arc-length: {{ $circumference }}" />
    </svg>
    <span class="relative font-semibold tabular-nums text-base-content" style="font-size: {{ max(11, round($pixels / 4.6)) }}px">
        @if ($slot->isNotEmpty())
            {{ $slot }}
        @elseif ($showValue)
            {{ $percent }}<span class="text-[0.6em] font-medium opacity-60">%</span>
        @endif
    </span>
</div>
