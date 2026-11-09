@props(['label' => null, 'description' => null])

{{-- Native radio: group options with the same name; arrow keys move between them. --}}
<label class="inline-flex items-start gap-3 cursor-pointer select-none has-[:disabled]:opacity-50 has-[:disabled]:cursor-not-allowed">
    <span class="relative grid place-items-center shrink-0 size-5 mt-0.5">
        <input type="radio" {{ $attributes->class('peer appearance-none size-5 m-0 cursor-pointer rounded-full bg-field shadow-nui-inset transition border-[length:min(var(--nui-field-border-width),2px)] border-field-border checked:bg-primary checked:border-primary checked:shadow-nui-control focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary disabled:cursor-not-allowed') }}>
        <span aria-hidden="true" class="pointer-events-none absolute size-2 rounded-full bg-primary-content scale-0 transition peer-checked:scale-100"></span>
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
