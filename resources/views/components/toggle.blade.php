@props(['label' => null, 'description' => null])

{{-- Native checkbox with role="switch": works with forms, wire:model and the keyboard (Space) without JS. --}}
<label class="inline-flex items-start gap-3 cursor-pointer select-none has-[:disabled]:opacity-50 has-[:disabled]:cursor-not-allowed">
    <input type="checkbox" role="switch" {{ $attributes->class('peer sr-only') }}>
    <span aria-hidden="true" class="relative shrink-0 mt-px h-6 w-11 rounded-full bg-base-300 shadow-nui-inset transition-colors duration-200 peer-checked:bg-primary
        peer-focus-visible:outline-2 peer-focus-visible:outline-offset-2 peer-focus-visible:outline-primary
        after:absolute after:top-0.5 after:left-0.5 after:size-5 after:rounded-full after:bg-base-100 after:shadow-nui-control after:transition-transform after:duration-200 after:ease-out
        peer-checked:after:translate-x-5"></span>
    @if ($label || $description)
        <span class="flex flex-col">
            <span class="text-sm font-medium text-base-content">{{ $label }}</span>
            @if ($description)
                <span class="text-sm text-base-content/60">{{ $description }}</span>
            @endif
        </span>
    @endif
</label>
