@props(['variant' => 'info', 'title' => null, 'dismissible' => false, 'icon' => true, 'outline' => false])

@php
    $tones = [
        'info' => ['border-primary/30 bg-primary/[0.07]', 'text-primary', 'border-primary/45'],
        'success' => ['border-success/30 bg-success/[0.08]', 'text-success', 'border-success/50'],
        'warning' => ['border-warning/50 bg-warning/[0.14]', 'text-[oklch(52%_0.12_70)] [[data-theme=dark]_&]:text-warning', 'border-warning/70'],
        'danger' => ['border-danger/30 bg-danger/[0.07]', 'text-danger', 'border-danger/45'],
    ];
    [$surface, $accent, $edge] = $tones[$variant] ?? $tones['info'];
    $icons = [
        'info' => '<circle cx="10" cy="10" r="7.5" /><path d="M10 9v4.5M10 6.5v.01" />',
        'success' => '<circle cx="10" cy="10" r="7.5" /><path d="m6.75 10.25 2.25 2.25 4.25-4.75" />',
        'warning' => '<path d="M8.7 3.3a1.5 1.5 0 0 1 2.6 0l6 10.4A1.5 1.5 0 0 1 16 16H4a1.5 1.5 0 0 1-1.3-2.3l6-10.4Z" /><path d="M10 8v3.5M10 13.5v.01" />',
        'danger' => '<circle cx="10" cy="10" r="7.5" /><path d="m7.5 7.5 5 5M12.5 7.5l-5 5" />',
    ];
@endphp

{{--
    Inline message. variant: info | success | warning | danger · title · outline · icon (false hides it) · dismissible (morphs out).
    Slot = description; slot "actions" = buttons/links under it. Danger uses role="alert", the others role="status".
--}}
<div role="{{ $variant === 'danger' ? 'alert' : 'status' }}"
    @if ($dismissible) x-data="{ shown: true }" x-show="shown"
        x-transition:leave="transition-all duration-300 ease-[cubic-bezier(0.16,1,0.3,1)]" x-transition:leave-start="opacity-100 [clip-path:inset(0)]" x-transition:leave-end="opacity-0 -translate-y-1 [clip-path:inset(0_0_100%_0)]" @endif
    {{ $attributes->class([
        'nui-enter flex items-start gap-3 p-4 rounded-nui border border-b-[3px] text-sm text-base-content',
        $outline ? 'bg-transparent '.$edge : $surface,
    ]) }}>
    @if ($icon)
        <svg aria-hidden="true" class="size-5 shrink-0 {{ $accent }}" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">{!! $icons[$variant] ?? $icons['info'] !!}</svg>
    @endif
    <div class="flex-1 min-w-0">
        @if ($title)
            <p class="font-semibold leading-5">{{ $title }}</p>
        @endif
        @if ($slot->isNotEmpty())
            <div @class(['leading-relaxed text-base-content/75', 'mt-0.5' => $title])>{{ $slot }}</div>
        @endif
        @isset($actions)
            <div class="mt-3 flex flex-wrap gap-2">{{ $actions }}</div>
        @endisset
    </div>
    @if ($dismissible)
        <x-nui::button variant="ghost" size="sm" type="button" x-on:click="shown = false" aria-label="Fechar aviso"
            class="!p-0 -mr-1 -mt-1 grid place-items-center !size-7 shrink-0 rounded-[calc(var(--nui-radius)-4px)] text-base-content/50 hover:text-base-content hover:bg-base-content/[0.06] focus-visible:outline-2 focus-visible:outline-primary">
            <svg aria-hidden="true" class="size-3.5" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"><path d="m4 4 8 8M12 4l-8 8" /></svg>
        </x-nui::button>
    @endif
</div>
