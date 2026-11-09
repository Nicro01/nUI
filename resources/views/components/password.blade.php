@props(['label' => null,'labelStyle' => 'top', 'hint' => null, 'error' => null])

@php
    $name = $attributes->get('name');
    $id = $attributes->get('id') ?? $name ?? $attributes->whereStartsWith('wire:model')->first() ?? 'nui-'.Str::random(8);
    $error ??= $name ? ($errors ?? null)?->first($name) : null;
@endphp

<x-nui::field :label="$labelStyle === 'top' ? $label : null" :$hint :$error :for="$id" :class="$attributes->get('class')">
    <x-nui::box :invalid="(bool) $error" :label="$labelStyle === 'top' ? null : $label" :for="$id" :label-style="$labelStyle" class="pr-1" x-data="{ shown: false }">
        <input id="{{ $id }}" type="password" x-bind:type="shown ? 'text' : 'password'"
            @if ($error) aria-invalid="true" aria-describedby="{{ $id }}-error" @elseif ($hint) aria-describedby="{{ $id }}-hint" @endif
            {{ $attributes->except(['id', 'class', 'type'])->merge($labelStyle === 'floating' ? ['placeholder' => "\u{00A0}"] : [])->merge(['autocomplete' => 'current-password'])->class('w-full min-w-0 py-2 bg-transparent outline-none placeholder:text-base-content/45') }}>
        <x-nui::button variant="ghost" size="sm" type="button" x-on:click="shown = !shown" x-bind:aria-pressed="shown.toString()" aria-pressed="false" aria-label="Mostrar senha"
            class="!p-0 grid place-items-center !size-8 shrink-0 rounded-[calc(var(--nui-radius-field)-2px)] text-base-content/60 hover:text-base-content hover:bg-base-content/5 focus-visible:outline-2 focus-visible:outline-primary">
            <svg x-show="!shown" aria-hidden="true" class="size-4.5" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M1.5 10S4.5 4 10 4s8.5 6 8.5 6-3 6-8.5 6-8.5-6-8.5-6Z" /><circle cx="10" cy="10" r="2.5" /></svg>
            <svg x-show="shown" x-cloak aria-hidden="true" class="size-4.5" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M8.2 4.2A8.4 8.4 0 0 1 10 4c5.5 0 8.5 6 8.5 6a14.6 14.6 0 0 1-2.2 3M5 5.6C2.7 7.2 1.5 10 1.5 10s3 6 8.5 6a8 8 0 0 0 4.4-1.3M2 2l16 16M8.2 8.3a2.5 2.5 0 0 0 3.5 3.5" /></svg>
        </x-nui::button>
    </x-nui::box>
</x-nui::field>


