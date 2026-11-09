@props(['variant' => 'neutral', 'size' => 'md', 'dot' => false, 'removable' => false, 'outline' => false])

@php
    $tone = match ($variant) {
        'primary' => ['bg-primary/12 text-primary', 'border-primary/40 text-primary', 'bg-primary'],
        'success' => ['bg-[oklch(55%_0.14_150/0.14)] text-[oklch(42%_0.12_150)] [[data-theme=dark]_&]:text-[oklch(78%_0.14_150)]', 'border-[oklch(55%_0.14_150/0.5)] text-[oklch(42%_0.12_150)] [[data-theme=dark]_&]:text-[oklch(78%_0.14_150)]', 'bg-[oklch(55%_0.14_150)]'],
        'warning' => ['bg-[oklch(78%_0.15_80/0.22)] text-[oklch(45%_0.1_70)] [[data-theme=dark]_&]:text-[oklch(84%_0.13_80)]', 'border-[oklch(70%_0.15_80/0.6)] text-[oklch(45%_0.1_70)] [[data-theme=dark]_&]:text-[oklch(84%_0.13_80)]', 'bg-[oklch(72%_0.16_75)]'],
        'danger' => ['bg-danger/12 text-danger', 'border-danger/45 text-danger', 'bg-danger'],
        default => ['bg-base-content/[0.07] text-base-content/80', 'border-base-content/20 text-base-content/80', 'bg-base-content/50'],
    };
@endphp

{{-- Short label. removable adds a close button that hides the tag and fires "tag-removed" (bubbles) for Alpine/Livewire. --}}
<span
    @if ($removable) x-data="{ shown: true }" x-show="shown" @endif
    {{ $attributes->class([
        'inline-flex items-center gap-1.5 whitespace-nowrap font-medium rounded-full align-middle [&_svg]:shrink-0',
        $outline ? 'border border-b-2 bg-transparent '.$tone[1] : $tone[0].' border-b-2 border-b-current/20',
        match ($size) {
            'sm' => 'h-5 px-2 text-[0.6875rem] [&_svg]:size-3',
            'lg' => 'h-7 px-3 text-sm [&_svg]:size-4',
            default => 'h-6 px-2.5 text-xs [&_svg]:size-3.5',
        },
        $removable ? 'pr-1' : '',
    ]) }}>
    @if ($dot)
        <span aria-hidden="true" class="size-1.5 rounded-full {{ $tone[2] }}"></span>
    @endif
    {{ $slot }}
    @if ($removable)
        <x-nui::button variant="ghost" size="sm" type="button" aria-label="Remover {{ trim(strip_tags($slot)) }}"
            :data-tag="trim(strip_tags($slot))" x-on:click="shown = false; $el.dispatchEvent(new CustomEvent('tag-removed', { bubbles: true, detail: $el.dataset.tag }))"
            class="!p-0 !text-current grid place-items-center !size-4 rounded-full opacity-70 hover:opacity-100 hover:bg-current/15 focus-visible:outline-2 focus-visible:outline-current">
            <svg aria-hidden="true" class="!size-2.5" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round"><path d="m4 4 8 8M12 4l-8 8" /></svg>
        </x-nui::button>
    @endif
</span>



