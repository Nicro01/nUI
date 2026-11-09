@props([
    'steps' => [],
    'default' => null,
    'vertical' => false,
    'variant' => 'default',
    'linear' => true,
    'validate' => false,
    'controls' => false,
    'label' => 'Etapas',
    'backLabel' => 'Voltar',
    'nextLabel' => 'Continuar',
    'finishLabel' => 'Concluir',
])

@php
    $wizard = $variant === 'wizard';
    $vertical = $vertical && ! $wizard;
    $id = $attributes->get('id') ?? 'nui-steps-'.Str::random(8);

    // Accepts ['dados' => 'Dados'], ['Dados', 'Entrega'] or [['name', 'label', 'description', 'icon', 'optional', 'error', 'disabled']].
    $items = collect($steps)->map(fn ($step, $key) => is_array($step)
        ? [
            'name' => (string) ($step['name'] ?? $key),
            'label' => (string) $step['label'],
            'description' => $step['description'] ?? null,
            'icon' => $step['icon'] ?? null,
            'optional' => (bool) ($step['optional'] ?? false),
            'error' => (bool) ($step['error'] ?? false),
            'disabled' => (bool) ($step['disabled'] ?? false),
        ]
        : ['name' => (string) (array_is_list($steps) ? $step : $key), 'label' => (string) $step, 'description' => null, 'icon' => null, 'optional' => false, 'error' => false, 'disabled' => false]
    )->values();

    $names = $items->pluck('name');
    $current = (string) ($default ?? $items->firstWhere('disabled', false)['name'] ?? '');
    $currentIndex = max(0, (int) $names->search($current));
    $total = $items->count();
    $key = fn (string $name): string => Str::slug($name) ?: md5($name);
@endphp

{{--
    Multi-step flow: an ordered list of step markers plus <x-nui::step-panel> children.
    The current step name works with x-model and wire:model (x-modelable). In linear mode a step
    is reachable only once every step before it was reached. "validate" checks the native form
    controls of the current panel before moving forward. Listen to nui-steps-before (cancelable,
    detail { from, to }) to run your own checks, nui-steps-change after a move and nui-steps-finish
    on the last step. Inside the slot, next(), back(), go(name) and finish() are available.
    Icons are trusted HTML from your code.
--}}
<div
    x-data="{
        current: @js($current),
        furthest: {{ $currentIndex }},
        errors: @js($items->where('error', true)->pluck('name')->values()),
        direction: 1,
        moved: false,
        ready: false,
        finished: false,
        names: @js($names),
        disabled: @js($items->where('disabled', true)->pluck('name')->values()),
        linear: @js((bool) $linear),
        validates: @js((bool) $validate),
        init() {
            if (! this.names.includes(this.current)) this.current = this.names[0] ?? '';
            this.furthest = Math.max(this.furthest, this.index());
            this.$nextTick(() => this.ready = true);
            this.$watch('current', (value, old) => {
                const to = this.names.indexOf(value), from = this.names.indexOf(old);
                if (to === -1) { this.current = old; return; }
                this.direction = to < from ? -1 : 1;
                this.furthest = Math.max(this.furthest, to);
                this.moved = true;
                this.$dispatch('nui-steps-change', { step: value, index: to });
            });
        },
        index(name = this.current) { return this.names.indexOf(name) },
        state(name) {
            if (this.errors.includes(name)) return 'error';
            if (name === this.current) return this.finished ? 'complete' : 'current';
            return this.index(name) < Math.max(this.index(), this.furthest) ? 'complete' : 'upcoming';
        },
        reachable(name) {
            if (this.disabled.includes(name)) return false;
            return ! this.linear || this.index(name) <= this.furthest || this.index(name) === this.index() + 1;
        },
        panel(name = this.current) { return this.$refs.panels.querySelector('[data-step-panel=' + CSS.escape(name) + ']') },
        check() {
            const panel = this.panel();
            if (! this.validates || ! panel) return true;
            const invalid = [...panel.querySelectorAll('input, select, textarea')].find((field) => ! field.disabled && ! field.checkValidity());
            if (! invalid) return true;
            invalid.reportValidity();
            invalid.focus({ preventScroll: false });
            return false;
        },
        go(name, { skipChecks = false } = {}) {
            const to = this.index(name), from = this.index();
            if (to === -1 || to === from || this.disabled.includes(name)) return false;
            if (to > from && ! skipChecks) {
                if (! this.check()) { this.mark(this.current, true); return false; }
                const before = new CustomEvent('nui-steps-before', { detail: { from: this.current, to: name }, bubbles: true, cancelable: true });
                if (! this.$el.dispatchEvent(before)) return false;
                this.mark(this.current, false);
            }
            if (this.linear && to > this.furthest + 1) return false;
            this.finished = false;
            this.current = name;
            this.$nextTick(() => this.$refs.panels.querySelector('[data-step-panel]:not([hidden])')?.focus({ preventScroll: true }));
            return true;
        },
        next() {
            const following = this.names.slice(this.index() + 1).find((name) => ! this.disabled.includes(name));
            following ? this.go(following) : this.finish();
        },
        back() {
            const previous = this.names.slice(0, this.index()).reverse().find((name) => ! this.disabled.includes(name));
            if (previous) this.go(previous);
        },
        finish() {
            if (! this.check()) { this.mark(this.current, true); return; }
            const before = new CustomEvent('nui-steps-before', { detail: { from: this.current, to: null }, bubbles: true, cancelable: true });
            if (! this.$el.dispatchEvent(before)) return;
            this.mark(this.current, false);
            this.finished = true;
            this.$dispatch('nui-steps-finish', { step: this.current });
        },
        mark(name, failed) { this.errors = failed ? [...new Set([...this.errors, name])] : this.errors.filter((item) => item !== name) },
        isLast() { return ! this.names.slice(this.index() + 1).some((name) => ! this.disabled.includes(name)) },
        isFirst() { return ! this.names.slice(0, this.index()).some((name) => ! this.disabled.includes(name)) },
        progress() { return this.names.length > 1 ? Math.max(this.index(), this.finished ? this.names.length - 1 : 0) / (this.names.length - 1) : 1 },
    }"
    x-modelable="current"
    {{ $attributes->whereStartsWith(['wire:model', 'x-model']) }}
    x-bind:data-moved="moved" x-bind:data-ready="ready" @if ($default === null) data-auto @endif x-bind:data-direction="direction < 0 ? 'back' : 'forward'" x-bind:data-finished="finished"
    x-bind:style="'--nui-steps-progress: ' + progress()"
    style="--nui-steps-progress: {{ $total > 1 ? $currentIndex / ($total - 1) : 1 }}"
    data-orientation="{{ $vertical ? 'vertical' : 'horizontal' }}" data-variant="{{ $wizard ? 'wizard' : 'default' }}"
    {{ $attributes->except(['id', 'class'])->whereDoesntStartWith(['wire:model', 'x-model']) }}
    {{ $attributes->only('class')->class(['nui-steps min-w-0', $vertical ? 'grid items-start gap-x-8 gap-y-6 sm:grid-cols-[minmax(12rem,auto)_minmax(0,1fr)]' : 'flex flex-col gap-6']) }}
    id="{{ $id }}">

    <nav aria-label="{{ $label }}" class="nui-steps-nav min-w-0">
        @unless ($vertical)
            {{-- Phones: the row keeps only markers; this line names the current step. --}}
            <p class="nui-steps-summary mb-3 flex items-baseline gap-2 text-sm sm:hidden" aria-hidden="true">
                <span class="tabular-nums text-base-content/60" x-text="'Etapa ' + (index() + 1) + ' de ' + names.length">Etapa {{ $currentIndex + 1 }} de {{ $total }}</span>
                <span class="font-medium text-base-content" x-text="$refs.list.querySelector('[data-step=' + CSS.escape(current) + '] [data-step-label]')?.textContent">{{ $items[$currentIndex]['label'] ?? '' }}</span>
            </p>
        @endunless

        <ol x-ref="list" @class(['nui-steps-list relative flex', $vertical ? 'flex-col gap-1' : ($wizard ? 'items-stretch' : 'items-start')])>
            @foreach ($items as $index => $item)
                @php
                    $initialState = $item['error'] ? 'error' : ($index < $currentIndex ? 'complete' : ($index === $currentIndex ? 'current' : 'upcoming'));
                @endphp
                <li data-step="{{ $item['name'] }}" data-state="{{ $initialState }}" x-bind:data-state="state(@js($item['name']))"
                    @class(['nui-step relative min-w-0', $vertical ? 'pb-6 last:pb-0' : ($wizard ? 'flex-1' : 'flex-1 last:flex-none')])>
                    @unless ($loop->last)
                        <span aria-hidden="true" class="nui-step-connector"><span class="nui-step-connector-fill"></span></span>
                    @endunless

                    <button type="button" id="{{ $id }}-step-{{ $key($item['name']) }}"
                        aria-controls="{{ $id }}-panel-{{ $key($item['name']) }}"
                        @if ($index === $currentIndex) aria-current="step" @endif
                        x-bind:aria-current="current === @js($item['name']) ? 'step' : null"
                        x-bind:disabled="! reachable(@js($item['name'])) && current !== @js($item['name'])"
                        x-on:click="go(@js($item['name']))"
                        @disabled($item['disabled'] || ($linear && $index > $currentIndex + 1))
                        class="nui-step-trigger group relative z-10 flex min-w-0 gap-3 rounded-nui text-left outline-none focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-primary disabled:cursor-not-allowed {{ $vertical ? 'w-full items-start' : ($wizard ? 'h-full w-full items-center' : 'flex-col items-start sm:pr-4') }}">
                        <span class="nui-step-marker" aria-hidden="true">
                            <svg class="nui-step-check" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="m3.5 8.5 3 3 6-7" /></svg>
                            <svg class="nui-step-alert" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><path d="M8 4v5" /><path d="M8 12h.01" /></svg>
                            <span class="nui-step-number">
                                @if ($item['icon'])
                                    <span class="inline-flex [&_svg]:size-4">{!! $item['icon'] !!}</span>
                                @else
                                    {{ $index + 1 }}
                                @endif
                            </span>
                        </span>
                        <span class="nui-step-text flex min-w-0 flex-col gap-0.5 {{ $vertical ? 'pt-1.5' : '' }}">
                            <span data-step-label class="nui-step-label text-sm [font-weight:var(--nui-control-weight)] [overflow-wrap:anywhere]">{{ $item['label'] }}</span>
                            @if ($item['description'] || $item['optional'])
                                <span class="nui-step-description text-xs leading-snug text-base-content/60">
                                    {{ $item['description'] }}@if ($item['optional'])<span class="nui-step-optional">{{ $item['description'] ? ' · ' : '' }}Opcional</span>@endif
                                </span>
                            @endif
                            <span class="sr-only" x-text="{ complete: ', concluída', current: ', etapa atual', error: ', precisa de atenção', upcoming: '' }[state(@js($item['name']))]"></span>
                        </span>
                    </button>
                </li>
            @endforeach
        </ol>
    </nav>

    <div class="nui-steps-body flex min-w-0 flex-col gap-6">
        <div x-ref="panels" class="nui-steps-panels min-w-0">
            {{ $slot }}
        </div>

        @if ($controls)
            <div class="nui-steps-controls flex flex-wrap items-center gap-3 border-t border-base-300 pt-5">
                <x-nui::button variant="ghost" x-on:click="back()" x-bind:disabled="isFirst()" :disabled="$currentIndex === 0">
                    <svg aria-hidden="true" class="size-4" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M10 3.5 5.5 8l4.5 4.5" /></svg>
                    {{ $backLabel }}
                </x-nui::button>
                <span class="ml-auto text-xs tabular-nums text-base-content/60" aria-live="polite"
                    x-text="finished ? 'Tudo pronto' : 'Etapa ' + (index() + 1) + ' de ' + names.length">Etapa {{ $currentIndex + 1 }} de {{ $total }}</span>
                <x-nui::button x-on:click="next()" x-bind:disabled="finished">
                    <span x-text="isLast() ? @js($finishLabel) : @js($nextLabel)">{{ $currentIndex === $total - 1 ? $finishLabel : $nextLabel }}</span>
                    <svg aria-hidden="true" class="size-4" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" x-show="! isLast()"><path d="M6 3.5 10.5 8 6 12.5" /></svg>
                </x-nui::button>
            </div>
        @endif
    </div>
</div>
