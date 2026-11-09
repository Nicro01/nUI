{{-- Group label inside <x-nui::timeline>, e.g. "Hoje". The line keeps running behind it. --}}
<li {{ $attributes->class('nui-timeline-heading') }}>
    <span class="nui-timeline-rail" aria-hidden="true"></span>
    <p class="nui-timeline-heading-label inline-flex items-center h-6 px-2.5 rounded-full bg-base-200 border border-base-300 text-xs font-semibold text-base-content/70">{{ $slot }}</p>
</li>
