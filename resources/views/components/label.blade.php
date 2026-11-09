@props(['for' => null, 'required' => false, 'optional' => false, 'hint' => null])

{{--
    Standalone label for custom layouts. required shows a marker (announced as "obrigatório"),
    optional shows "(opcional)", hint adds an info icon with a tooltip.
--}}
<label @if ($for) for="{{ $for }}" @endif {{ $attributes->class('inline-flex items-center gap-1.5 text-sm font-medium text-base-content') }}>
    {{ $slot }}
    @if ($required)
        <span aria-hidden="true" class="text-danger">*</span><span class="sr-only">(obrigatório)</span>
    @elseif ($optional)
        <span class="font-normal text-base-content/50">(opcional)</span>
    @endif
    @if ($hint)
        <x-nui::tooltip :text="$hint">
            <span tabindex="0" class="grid place-items-center size-4 rounded-full text-base-content/45 hover:text-base-content focus-visible:outline-2 focus-visible:outline-primary" aria-label="{{ $hint }}">
                <svg aria-hidden="true" class="size-3.5" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"><circle cx="8" cy="8" r="6" /><path d="M8 7.25v3.5M8 5.25v.01" /></svg>
            </span>
        </x-nui::tooltip>
    @endif
</label>
