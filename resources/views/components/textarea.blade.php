@props(['label' => null,'labelStyle' => 'top', 'hint' => null, 'error' => null])

@php
    $name = $attributes->get('name');
    $id = $attributes->get('id') ?? $name ?? $attributes->whereStartsWith('wire:model')->first() ?? 'nui-'.Str::random(8);
    $error ??= $name ? ($errors ?? null)?->first($name) : null;
@endphp

{{-- Grows with its content (field-sizing), still resizable by hand. --}}
<x-nui::field :label="$labelStyle === 'top' ? $label : null" :$hint :$error :for="$id" :class="$attributes->get('class')">
    <x-nui::box :invalid="(bool) $error" :label="$labelStyle === 'top' ? null : $label" :for="$id" :label-style="$labelStyle" class="items-stretch py-1">
        <textarea id="{{ $id }}"
            @if ($error) aria-invalid="true" aria-describedby="{{ $id }}-error" @elseif ($hint) aria-describedby="{{ $id }}-hint" @endif
            {{ $attributes->except(['id', 'class'])->merge($labelStyle === 'floating' ? ['placeholder' => "\u{00A0}"] : [])->merge(['rows' => 3])->class('w-full min-h-24 py-1.5 bg-transparent outline-none resize-y field-sizing-content placeholder:text-base-content/45') }}>{{ $slot }}</textarea>
    </x-nui::box>
</x-nui::field>
