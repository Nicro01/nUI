@props(['label', 'disabled' => false, 'expanded' => false])
@aware(['variant' => 'dropdown'])
@php($id = 'nui-menu-sub-'.Str::random(8))
<div role="none" {{ $attributes->class('min-w-0') }} x-data="{ expanded: @js((bool) $expanded) }"
    x-on:keydown="
        const rtl = getComputedStyle($el).direction === 'rtl';
        const forward = rtl ? 'ArrowLeft' : 'ArrowRight', back = rtl ? 'ArrowRight' : 'ArrowLeft';
        if ($event.target === $refs.subTrigger && $event.key === forward && !$refs.subTrigger.disabled) {
            $event.preventDefault(); $event.stopPropagation(); expanded = true;
            $nextTick(() => { focusEdge($refs.subPanel); $dispatch('nui-menu-resize'); });
        } else if (expanded && ($event.key === back || $event.key === 'Escape')) {
            $event.preventDefault(); $event.stopPropagation(); expanded = false; $refs.subTrigger.focus(); $dispatch('nui-menu-resize');
        }
    ">
    <x-nui::button variant="ghost" :$disabled class="nui-menu-item" x-ref="subTrigger" :id="$id.'-trigger'"
        :role="$variant === 'inline' ? null : 'menuitem'" :tabindex="$variant === 'inline' ? null : -1"
        data-nui-menu-item :data-label="$label" :aria-haspopup="$variant === 'inline' ? null : 'menu'"
        :aria-controls="$id" x-bind:aria-expanded="expanded.toString()"
        x-on:click="expanded = !expanded; $dispatch('nui-menu-resize')">
        @isset($icon)<span aria-hidden="true" class="flex size-5 shrink-0 items-center justify-center">{{ $icon }}</span>@endisset
        <span class="min-w-0 flex-1">{{ $label }}</span>
        <svg aria-hidden="true" class="size-4 transition-transform motion-reduce:transition-none rtl:rotate-180" x-bind:class="expanded && 'rotate-90 rtl:rotate-90'" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.7"><path d="m8 5 5 5-5 5" /></svg>
    </x-nui::button>
    <div id="{{ $id }}" data-nui-menu-level x-ref="subPanel" x-show="expanded" x-cloak
        role="{{ $variant === 'inline' ? 'group' : 'menu' }}" aria-labelledby="{{ $id }}-trigger"
        class="ms-3 border-s border-base-content/15 ps-2">
        {{ $slot }}
    </div>
</div>
