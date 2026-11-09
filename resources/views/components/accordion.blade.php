@props(['multiple' => false, 'variant' => 'bordered'])

{{--
    Group of <x-nui::collapse>. By default only one item stays open: the browser's exclusive
    <details name="…"> does the work (the name is set by Alpine; without JS, several can open).
    variant: bordered (one box, divided rows) | separated (spaced cards).
--}}
<div
    @unless ($multiple)
        x-data x-init="const group = 'nui-accordion-' + Math.random().toString(36).slice(2, 8); $el.querySelectorAll(':scope > details').forEach((item) => item.name = group)"
    @endunless
    {{ $attributes->class([
        'nui-accordion',
        $variant === 'separated'
            ? 'flex flex-col gap-3'
            : 'nui-accordion-bordered bg-base-100 rounded-nui border-[length:var(--nui-border-width)] border-base-300 shadow-nui divide-y divide-base-content/[0.08] overflow-hidden',
    ]) }}>
    {{ $slot }}
</div>
