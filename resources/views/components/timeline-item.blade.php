@props([
    'title' => null,
    'time' => null,
    'datetime' => null,
    'status' => 'done',
    'icon' => null,
    'image' => null,
    'detailsLabel' => 'Ver detalhes',
])

@php
    $statusText = ['done' => 'Concluído', 'current' => 'Em andamento', 'upcoming' => 'Pendente', 'error' => 'Com erro', 'warning' => 'Com alerta'];
@endphp

{{--
    One event of <x-nui::timeline>. status: done | current | upcoming | error | warning (current sets aria-current="step").
    icon (trusted SVG from your code) or image (avatar URL) replace the status mark. Slots: default (description),
    meta (tags, avatars, buttons) and details (collapsible, opens with the collapse morph).
--}}
<li data-status="{{ $status }}" @if ($status === 'current') aria-current="step" @endif
    {{ $attributes->class('nui-timeline-item') }}>
    <span class="nui-timeline-rail">
        <span class="nui-timeline-marker" aria-hidden="true">
            @if ($image)
                <img src="{{ $image }}" alt="" class="size-full object-cover">
            @elseif ($icon)
                <span class="nui-timeline-custom inline-flex">{!! $icon !!}</span>
            @else
                <svg data-when="done" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="m3.5 8.5 3 3 6-7" /></svg>
                <svg data-when="error" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round"><path d="m4.5 4.5 7 7M11.5 4.5l-7 7" /></svg>
                <svg data-when="warning" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round"><path d="M8 3.5v5.5M8 12.25v.25" /></svg>
                <span data-when="current" class="nui-timeline-pip"></span>
            @endif
        </span>
    </span>

    <div class="nui-timeline-body min-w-0">
        <div class="nui-timeline-head flex flex-wrap items-baseline gap-x-3 gap-y-0.5">
            <p class="nui-timeline-title font-semibold leading-snug">
                @foreach ($statusText as $key => $text)
                    <span class="sr-only" data-when="{{ $key }}">{{ $text }}: </span>
                @endforeach
                {{ $title }}
            </p>
            @if ($time)
                <time @if ($datetime) datetime="{{ $datetime }}" @endif class="nui-timeline-time text-xs tabular-nums text-base-content/55">{{ $time }}</time>
            @endif
        </div>

        @if (trim((string) $slot) !== '')
            <div class="nui-timeline-text mt-1 text-sm leading-relaxed text-base-content/70">{{ $slot }}</div>
        @endif

        @isset($meta)
            <div class="nui-timeline-meta mt-3 flex flex-wrap items-center gap-2">{{ $meta }}</div>
        @endisset

        @isset($details)
            <details class="nui-collapse nui-timeline-details group/details mt-2">
                <summary class="inline-flex items-center gap-1.5 h-7 -ml-1.5 px-1.5 rounded-[min(var(--nui-radius-field),0.375rem)] text-sm font-medium text-primary cursor-pointer select-none list-none [&::-webkit-details-marker]:hidden hover:bg-base-content/[0.05] outline-none focus-visible:outline-2 focus-visible:outline-primary">
                    {{ $detailsLabel }}
                    <svg aria-hidden="true" class="size-3.5 transition-transform duration-300 group-open/details:rotate-180" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="m4 6 4 4 4-4" /></svg>
                </summary>
                <div class="nui-collapse-body pt-2">
                    <div class="rounded-nui bg-base-200 border border-base-300 p-3 text-sm leading-relaxed text-base-content/80">{{ $details }}</div>
                </div>
            </details>
        @endisset
    </div>
</li>
