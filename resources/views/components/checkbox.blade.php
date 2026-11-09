@props(['label' => null, 'description' => null])

{{-- Native checkbox (forms, keyboard, wire:model) drawn as a custom box. --}}
<label class="inline-flex items-start gap-3 cursor-pointer select-none has-[:disabled]:opacity-50 has-[:disabled]:cursor-not-allowed">
    <span class="relative grid place-items-center shrink-0 size-5 mt-0.5">
        <input type="checkbox" {{ $attributes->class('peer appearance-none size-5 m-0 cursor-pointer rounded-[min(var(--nui-radius-field),0.4rem)] bg-field shadow-nui-inset transition border-[length:min(var(--nui-field-border-width),2px)] border-field-border checked:bg-primary checked:border-primary checked:shadow-nui-control focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary disabled:cursor-not-allowed') }}>
        <svg aria-hidden="true" class="pointer-events-none absolute size-3.5 text-primary-content scale-50 opacity-0 transition peer-checked:scale-100 peer-checked:opacity-100" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="m3.5 8.5 3 3 6-7" /></svg>
    </span>
    @if ($label || $description)
        <span class="flex flex-col">
            <span class="text-sm font-medium text-base-content">{{ $label }}</span>
            @if ($description)
                <span class="text-sm text-base-content/60">{{ $description }}</span>
            @endif
        </span>
    @endif
</label>
