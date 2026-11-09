@props([
    'title' => null,
    'description' => null,
    'open' => false,
    'icon' => 'chevron',
    'separator' => true,
])

{{-- Native <details>: keyboard, find-in-page and "open" state for free. Morph animates via ::details-content in modern CSS and WAAPI fallback. --}}
<details @if ($open) open @endif
    x-data="{
        init() {
            if (window.CSS && CSS.supports && CSS.supports('interpolate-size: allow-keywords')) return;
            const details = this.$el;
            const summary = details.querySelector(':scope > summary');
            const content = details.querySelector('.nui-collapse-content') || details.querySelector('.nui-collapse-body');
            if (!summary || !content) return;
            let anim = null;
            let isOpening = false;
            let isClosing = false;
            summary.addEventListener('click', (e) => {
                if (e.defaultPrevented || e.button !== 0 || e.metaKey || e.ctrlKey || e.shiftKey || e.altKey) return;
                if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;
                e.preventDefault();
                if (isClosing || !details.open) {
                    if (anim) anim.cancel();
                    isOpening = true;
                    isClosing = false;
                    details.open = true;
                    const endH = content.scrollHeight;
                    anim = content.animate([
                        { height: '0px', opacity: 0, transform: 'translateY(-8px)' },
                        { height: endH + 'px', opacity: 1, transform: 'translateY(0)' }
                    ], {
                        duration: 320,
                        easing: 'cubic-bezier(0.16, 1, 0.3, 1)'
                    });
                    anim.onfinish = () => { isOpening = false; anim = null; };
                } else if (isOpening || details.open) {
                    if (anim) anim.cancel();
                    isClosing = true;
                    isOpening = false;
                    const startH = content.offsetHeight;
                    anim = content.animate([
                        { height: startH + 'px', opacity: 1, transform: 'translateY(0)' },
                        { height: '0px', opacity: 0, transform: 'translateY(-8px)' }
                    ], {
                        duration: 260,
                        easing: 'cubic-bezier(0.16, 1, 0.3, 1)'
                    });
                    anim.onfinish = () => {
                        details.open = false;
                        isClosing = false;
                        anim = null;
                    };
                }
            });
        }
    }"
    {{ $attributes->class([
        'nui-collapse group/collapse relative bg-base-100 text-base-content rounded-nui',
        'border-[length:var(--nui-border-width)] border-base-300 shadow-nui',
        'transition-all duration-300 [transition-timing-function:cubic-bezier(0.16,1,0.3,1)]',
        'hover:border-base-content/25',
        'open:border-base-content/25 open:shadow-sm',
    ]) }}>
    <summary class="flex items-center gap-4 px-5 py-4 cursor-pointer select-none list-none [&::-webkit-details-marker]:hidden rounded-nui group-open/collapse:rounded-b-none outline-none focus-visible:ring-2 focus-visible:ring-primary/50 focus-visible:ring-offset-2 focus-visible:ring-offset-base-100 hover:bg-base-content/[0.025] active:bg-base-content/[0.04] transition-colors duration-200">
        @isset($leading)
            <span class="shrink-0 text-base-content/70">{{ $leading }}</span>
        @endisset

        <span class="flex-1 min-w-0">
            @isset($header)
                {{ $header }}
            @else
                <span class="block font-semibold text-[0.9375rem] text-base-content tracking-tight">{{ $title ?? $summary ?? '' }}</span>
                @if ($description)
                    <span class="block mt-0.5 text-xs sm:text-sm text-base-content/65 leading-relaxed">{{ $description }}</span>
                @endif
            @endisset
        </span>

        @isset($trailing)
            <div class="shrink-0 flex items-center gap-2">{{ $trailing }}</div>
        @endisset

        @if ($icon === 'plus')
            <span aria-hidden="true" class="relative grid place-items-center size-7 shrink-0 rounded-full bg-base-content/[0.05] text-base-content/65 transition-all duration-300 group-hover/collapse:bg-base-content/[0.09] group-hover/collapse:text-base-content group-open/collapse:bg-primary/15 group-open/collapse:text-primary">
                <span class="absolute h-0.5 w-2.5 rounded-full bg-current transition-transform duration-300"></span>
                <span class="absolute h-2.5 w-0.5 rounded-full bg-current transition-transform duration-300 group-open/collapse:rotate-90 group-open/collapse:scale-y-0"></span>
            </span>
        @elseif ($icon === 'chevron')
            <span aria-hidden="true" class="grid place-items-center size-7 shrink-0 rounded-full bg-base-content/[0.05] text-base-content/65 transition-all duration-300 group-hover/collapse:bg-base-content/[0.09] group-hover/collapse:text-base-content group-open/collapse:bg-primary/15 group-open/collapse:text-primary">
                <svg class="size-3.5 transition-transform duration-300 [transition-timing-function:cubic-bezier(0.16,1,0.3,1)] group-open/collapse:rotate-180" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m4 6 4 4 4-4" /></svg>
            </span>
        @elseif ($icon && $icon !== 'none')
            <span aria-hidden="true" class="grid place-items-center size-7 shrink-0 rounded-full bg-base-content/[0.05] text-base-content/65 transition-all duration-300 group-hover/collapse:bg-base-content/[0.09] group-hover/collapse:text-base-content group-open/collapse:bg-primary/15 group-open/collapse:text-primary">
                {!! $icon !!}
            </span>
        @endif
    </summary>
    <div class="nui-collapse-content">
        @if ($separator)
            <div class="nui-collapse-divider mx-5 border-t border-base-content/[0.06]"></div>
        @endif
        <div class="nui-collapse-body px-5 {{ $separator ? 'pt-3.5' : 'pt-0' }} pb-5 text-sm leading-relaxed text-base-content/80">{{ $slot }}</div>
    </div>
</details>
