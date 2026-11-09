@props(['value' => null, 'max' => 100, 'label' => null, 'showValue' => false, 'variant' => 'primary', 'size' => 'md', 'bind' => null])

@php
    $indeterminate = $value === null && $bind === null;
    $percent = $indeterminate ? 0 : max(0, min(100, round($value / max($max, 1) * 100)));
    $fill = match ($variant) {
        'success' => 'bg-success',
        'warning' => 'bg-warning',
        'danger' => 'bg-danger',
        'neutral' => 'bg-base-content/60',
        default => 'bg-primary',
    };
    $height = match ($size) {
        'sm' => 'h-1.5',
        'lg' => 'h-3.5',
        default => 'h-2.5',
    };
@endphp

{{-- Linear progress. Without value it becomes indeterminate. bind = Alpine expression (0-100) to follow live data. Width changes animate, so binding value (Alpine/Livewire) morphs smoothly. --}}
<div {{ $attributes->class('flex flex-col gap-1.5 w-full') }}>
    @if ($label || $showValue)
        <div class="flex items-baseline justify-between gap-3 text-sm">
            @if ($label)
                <span class="font-medium text-base-content">{{ $label }}</span>
            @endif
            @if ($showValue && ! $indeterminate)
                <span class="ml-auto tabular-nums text-base-content/60">{{ $percent }}%</span>
            @endif
        </div>
    @endif
    <div role="progressbar" @if ($label) aria-label="{{ $label }}" @endif aria-valuemin="0" aria-valuemax="100" @unless ($indeterminate) aria-valuenow="{{ $percent }}" @endunless @if ($bind) x-bind:aria-valuenow="Math.round({{ $bind }})" @endif
        class="relative {{ $height }} w-full overflow-hidden rounded-full bg-base-content/10 shadow-nui-inset">
        @if ($indeterminate)
            <span class="nui-progress-indeterminate absolute inset-y-0 w-2/5 rounded-full {{ $fill }}"></span>
        @else
            <span class="block h-full rounded-full {{ $fill }} shadow-[inset_0_-1px_0_oklch(0%_0_0/0.15)] transition-[width] duration-500 ease-[cubic-bezier(0.16,1,0.3,1)]" style="width: {{ $percent }}%" @if ($bind) x-bind:style="'width: ' + Math.max(0, Math.min(100, {{ $bind }})) + '%'" @endif></span>
        @endif
    </div>
</div>
