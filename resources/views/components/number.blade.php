@props(['label' => null,'labelStyle' => 'top', 'hint' => null, 'error' => null])

@php
    $name = $attributes->get('name');
    $id = $attributes->get('id') ?? $name ?? $attributes->whereStartsWith('wire:model')->first() ?? 'nui-'.Str::random(8);
    $error ??= $name ? ($errors ?? null)?->first($name) : null;
    $labelStyle = $labelStyle === 'floating' ? 'legend' : $labelStyle; // the minus button sits where a floating label would rest
    $stepButton = 'nui-number-step text-base-content/70 hover:text-base-content hover:bg-base-content/5 disabled:opacity-40 disabled:pointer-events-none';
@endphp

{{-- A real <input type="number"> (min/max/step, arrow keys, forms) with custom stepper buttons. --}}
<x-nui::field :label="$labelStyle === 'top' ? $label : null" :$hint :$error :for="$id" :class="$attributes->get('class')">
    <x-nui::box :invalid="(bool) $error" :label="$labelStyle === 'top' ? null : $label" :for="$id" :label-style="$labelStyle" class="nui-number-control px-1"
        x-data="{ step(direction) { const input = $refs.input; direction > 0 ? input.stepUp() : input.stepDown(); input.dispatchEvent(new Event('input', { bubbles: true })); input.dispatchEvent(new Event('change', { bubbles: true })); } }">
        <x-nui::button variant="ghost" tabindex="-1" aria-label="Diminuir" x-on:click="step(-1)" class="{{ $stepButton }}" :disabled="$attributes->get('disabled')">
            <svg aria-hidden="true" class="size-4" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"><path d="M3.5 8h9" /></svg>
        </x-nui::button>
        <input id="{{ $id }}" type="number" x-ref="input"
            @if ($error) aria-invalid="true" aria-describedby="{{ $id }}-error" @elseif ($hint) aria-describedby="{{ $id }}-hint" @endif
            {{ $attributes->except(['id', 'class', 'type'])->merge($labelStyle === 'floating' ? ['placeholder' => ' '] : [])->class('nui-number-value min-w-0 py-2 bg-transparent outline-none text-center tabular-nums placeholder:text-base-content/45 [appearance:textfield] [&::-webkit-inner-spin-button]:appearance-none [&::-webkit-outer-spin-button]:appearance-none') }}>
        <x-nui::button variant="ghost" tabindex="-1" aria-label="Aumentar" x-on:click="step(1)" class="{{ $stepButton }}" :disabled="$attributes->get('disabled')">
            <svg aria-hidden="true" class="size-4" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"><path d="M3.5 8h9M8 3.5v9" /></svg>
        </x-nui::button>
    </x-nui::box>
</x-nui::field>

