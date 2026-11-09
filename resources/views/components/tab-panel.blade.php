@aware(['default' => null])

@props(['name'])

{{-- Content of one tab inside <x-nui::tabs>. "name" matches the tab's name; ids and aria links come from the tab list. --}}
<div role="tabpanel" tabindex="0"
    @if ($default !== null && (string) $default !== (string) $name) hidden @endif
    x-bind:hidden="selected !== @js((string) $name)"
    x-bind:id="tabOf(@js((string) $name))?.getAttribute('aria-controls')"
    x-bind:aria-labelledby="tabOf(@js((string) $name))?.id"
    {{ $attributes->class('nui-tab-panel rounded-nui outline-none focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-primary') }}>
    {{ $slot }}
</div>
