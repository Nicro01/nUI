@props(['label' => 'Navegação principal', 'width' => 'wide', 'padding' => true, 'fixed' => false])
@php($id = $attributes->get('id') ?? 'nui-layout-'.Str::random(8))

{{--
    fixed: app shell. The layout is exactly one viewport tall (100dvh, or the screen of a mockup it sits in);
    the sidebar never grows past it and only the content column scrolls, under a header that stays put.
--}}
<div {{ $attributes->merge(['id' => $id])->class('nui-layout min-w-0 bg-base-200 text-base-content') }}
    @if ($fixed) data-fixed @endif
    x-data="{ navigationOpen: false }">
    <div class="nui-layout-frame flex min-w-0 flex-col md:flex-row">
        @isset($sidebar)
            <div class="shrink-0 border-b border-base-300 bg-base-100 p-3 md:hidden">
                <x-nui::button variant="ghost" class="w-full !justify-start"
                    aria-expanded="false" x-bind:aria-expanded="navigationOpen.toString()"
                    aria-controls="{{ $id }}-navigation" x-on:click="navigationOpen = !navigationOpen">
                    <svg aria-hidden="true" class="size-4" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.7"><path d="M3 5h14M3 10h14M3 15h14" /></svg>
                    {{ $label }}
                </x-nui::button>
            </div>
            <aside id="{{ $id }}-navigation" aria-label="{{ $label }}"
                class="nui-layout-sidebar w-full shrink-0 border-b border-base-300 bg-base-100 md:w-64 md:border-b-0 md:border-e"
                x-bind:data-open="navigationOpen.toString()"
                x-on:keydown.escape="navigationOpen = false; $el.previousElementSibling.querySelector('button')?.focus()">
                @isset($brand)<div class="flex min-h-16 shrink-0 items-center border-b border-base-300 px-4 py-3">{{ $brand }}</div>@endisset
                <div class="nui-layout-nav p-3">{{ $sidebar }}</div>
                @isset($sidebarFooter)<div class="mt-auto shrink-0 border-t border-base-300 p-3">{{ $sidebarFooter }}</div>@endisset
            </aside>
        @endisset
        <div class="nui-layout-column flex min-w-0 flex-1 flex-col">
            @isset($header)
                <header class="flex min-h-16 min-w-0 shrink-0 flex-wrap items-center gap-3 border-b border-base-300 bg-base-100 px-4 py-3 sm:px-6">{{ $header }}</header>
            @endisset
            <div class="nui-layout-scroll flex min-w-0 flex-1 flex-col">
                <main @class(['mx-auto w-full min-w-0 flex-1', 'p-4 sm:p-6 lg:p-8' => $padding,
                    'max-w-7xl' => $width === 'wide', 'max-w-3xl' => $width === 'prose'])>
                    {{ $slot }}
                </main>
                @isset($footer)
                    <footer class="border-t border-base-300 bg-base-100 px-4 py-4 text-sm sm:px-6">{{ $footer }}</footer>
                @endisset
            </div>
        </div>
    </div>
</div>
