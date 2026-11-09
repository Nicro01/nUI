@props(['label', 'value' => null, 'color' => null])

{{-- Legend tile, a key-cap sort: the swatch carries identity, the text stays in ink (never the series colour). --}}
<div {{ $attributes->class('flex min-w-0 flex-col gap-1 rounded-[min(var(--nui-radius-field),0.5rem)] border border-base-content/12 border-b-2 border-b-base-content/22 bg-base-200 px-3 py-2.5') }}>
    <span class="flex min-w-0 items-center gap-2 text-xs font-medium text-base-content/70">
        @if ($color)<span aria-hidden="true" class="size-2.5 shrink-0 rounded-[2px] shadow-[inset_0_-1px_0_oklch(0%_0_0/0.25)]" style="background: {{ $color }}"></span>@endif
        <span class="truncate">{{ $label }}</span>
    </span>
    @if ($value !== null)<span class="text-[0.9375rem] font-semibold tabular-nums">{{ $value }}</span>@endif
</div>
