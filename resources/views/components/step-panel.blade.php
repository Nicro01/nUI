@aware(['default' => null])

@props(['name'])

{{-- Content of one step inside <x-nui::steps>. "name" matches the step's name; ids and aria links come from the step list. --}}
<section data-step-panel="{{ $name }}" tabindex="-1"
    @if ($default !== null && (string) $default !== (string) $name) hidden @endif
    x-bind:hidden="current !== @js((string) $name)"
    x-bind:id="$root.querySelector('[data-step=' + CSS.escape(@js((string) $name)) + '] button')?.getAttribute('aria-controls')"
    x-bind:aria-labelledby="$root.querySelector('[data-step=' + CSS.escape(@js((string) $name)) + '] button')?.id"
    {{ $attributes->class('nui-step-panel rounded-nui outline-none') }}>
    {{ $slot }}
</section>
