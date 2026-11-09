@aware(['default' => null])

@props(['name', 'title' => null, 'description' => null])

@php
    $slug = Str::slug((string) $name) ?: md5((string) $name);
@endphp

{{-- Content of one section inside <x-nui::settings-modal>. "name" matches the section's name; ids and aria links come from the section tab list. --}}
<div role="tabpanel" tabindex="0"
    data-settings-panel="{{ $name }}"
    @if ($default !== null && (string) $default !== (string) $name) hidden @endif
    x-bind:hidden="selected !== @js((string) $name)"
    x-bind:id="tabOf(@js((string) $name))?.getAttribute('aria-controls') || ('nui-settings-panel-' + @js($slug))"
    x-bind:aria-labelledby="tabOf(@js((string) $name))?.id"
    {{ $attributes->class('nui-settings-panel flex min-w-0 flex-col gap-5 outline-none focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-primary') }}>
    @if ($title || $description || isset($header))
        <header class="flex flex-wrap items-start justify-between gap-3 border-b border-base-content/[0.08] pb-4">
            <div class="min-w-0 flex-1">
                @if ($title)
                    <h3 class="text-base font-semibold leading-snug text-base-content">{{ $title }}</h3>
                @endif
                @if ($description)
                    <p class="mt-1 text-xs leading-relaxed text-base-content/65">{{ $description }}</p>
                @endif
            </div>
            @isset($header)
                <div class="flex shrink-0 items-center gap-2">{{ $header }}</div>
            @endisset
        </header>
    @endif

    <div class="flex min-w-0 flex-col gap-5">
        {{ $slot }}
    </div>
</div>
