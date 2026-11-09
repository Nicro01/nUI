@props([
    'label' => 'Código de verificação',
    'hint' => null,
    'error' => null,
    'value' => '',
    'length' => 6,
    'group' => 0,
    'mode' => 'numeric',     // numeric | alphanumeric | alpha
    'mask' => false,         // hide characters (•) like a PIN
    'placeholder' => null,   // single char shown in empty cells, e.g. '○' or '0'
    'size' => 'md',          // sm | md | lg
    'separator' => null,     // custom group separator text (default: dash)
    'autosubmit' => false,   // submit the parent form when complete
    'autofocus' => false,
])

@php
    $length = min(12, max(1, (int) $length));
    $group = max(0, (int) $group);
    $mode = in_array($mode, ['numeric', 'alphanumeric', 'alpha'], true) ? $mode : 'numeric';
    $strip = match ($mode) { 'alpha' => '/[^A-Za-z]/', 'alphanumeric' => '/[^A-Za-z0-9]/', default => '/\D/' };
    $jsStrip = match ($mode) { 'alpha' => '[^A-Za-z]', 'alphanumeric' => '[^A-Za-z0-9]', default => '\\D' };
    $pattern = match ($mode) { 'alpha' => '[A-Za-z]', 'alphanumeric' => '[A-Za-z0-9]', default => '[0-9]' };
    $name = $attributes->get('name');
    $id = $attributes->get('id') ?? $name ?? 'nui-otp-'.Str::random(6);
    $error ??= $name ? ($errors ?? null)?->first($name) : null;
    $raw = (string) ($name ? old($name, $value) : $value);
    $initial = substr(preg_replace($strip, '', $mode === 'numeric' ? $raw : strtoupper($raw)), 0, $length);
    $disabled = (bool) $attributes->get('disabled');
    $readonly = (bool) $attributes->get('readonly');
    $cellSize = match ($size) {
        'sm' => '!min-h-9 !w-8 text-base',
        'lg' => '!min-h-14 !w-12 text-2xl',
        default => '!min-h-11 !w-10 text-lg',
    };
    $unit = $mode === 'numeric' ? 'dígitos' : 'caracteres';
@endphp

<x-nui::field :$label :$hint :$error :for="$id" :class="$attributes->get('class')">
    <div class="nui-otp relative inline-flex max-w-full self-start"
        x-data="{
            value: @js($initial),
            length: @js($length),
            disabled: @js($disabled),
            readonly: @js($readonly),
            autosubmit: @js((bool) $autosubmit),
            strip: new RegExp(@js($jsStrip), 'g'),
            upper: @js($mode !== 'numeric'),
            focused: false,
            active: 0,
            selecting: false,
            completed: null,
            init() {
                this.$watch('value', incoming => {
                    const clean = this.clean(incoming);
                    if (incoming !== clean) { this.value = clean; return; }
                    if (clean.length === this.length && clean !== this.completed) {
                        this.completed = clean;
                        this.$dispatch('otp-complete', { value: clean });
                        if (this.autosubmit) this.$nextTick(() => this.$refs.control.form?.requestSubmit());
                    }
                    if (clean.length !== this.length) this.completed = null;
                });
                if (@js((bool) $autofocus) && !this.disabled) this.$nextTick(() => this.focusAt(this.value.length));
            },
            clean(text) {
                text = String(text ?? '');
                if (this.upper) text = text.toUpperCase();
                return text.replace(this.strip, '').slice(0, this.length);
            },
            cursor() {
                const input = this.$refs.control;
                const start = input.selectionStart ?? this.value.length;
                this.selecting = input.selectionEnd > start;
                this.active = Math.min(this.length - 1, start);
            },
            focusAt(position) {
                const input = this.$refs.control;
                input.focus();
                input.setSelectionRange(position, Math.min(position + 1, this.value.length));
                this.cursor();
            },
            clear() { this.value = ''; this.$refs.control.value = ''; this.focusAt(0); },
            input(event) {
                const input = event.target, position = input.selectionStart;
                this.value = this.clean(input.value);
                input.value = this.value;
                const caret = Math.min(position ?? this.value.length, this.value.length);
                input.setSelectionRange(caret, Math.min(caret + 1, this.value.length));
                this.cursor();
            },
            keydown(event) {
                const input = this.$refs.control, start = input.selectionStart ?? 0;
                if (event.key === 'ArrowLeft' && !event.shiftKey) { event.preventDefault(); this.focusAt(Math.max(0, start - 1)); }
                else if (event.key === 'ArrowRight' && !event.shiftKey) { event.preventDefault(); this.focusAt(Math.min(this.value.length, start + 1)); }
                else if (event.key === 'Home') { event.preventDefault(); this.focusAt(0); }
                else if (event.key === 'End') { event.preventDefault(); this.focusAt(this.value.length); }
                else if (event.key === 'Escape' && !this.readonly && this.value) { event.preventDefault(); this.clear(); }
            },
            paste(event) {
                if (this.disabled || this.readonly) return;
                event.preventDefault();
                const text = this.clean(event.clipboardData?.getData('text') || '');
                if (!text) return;
                const input = this.$refs.control;
                const start = input.selectionStart ?? this.value.length, end = input.selectionEnd ?? start;
                this.value = text.length === this.length ? text : this.clean(this.value.slice(0, start) + text + this.value.slice(end));
                input.value = this.value;
                const caret = text.length === this.length ? this.length : Math.min(start + text.length, this.length);
                this.$nextTick(() => { input.setSelectionRange(caret, caret); this.cursor(); });
            },
            select(event) {
                if (this.disabled) return;
                const cells = [...this.$refs.cells.querySelectorAll('[data-otp-cell]')];
                const index = cells.findIndex(cell => { const rect = cell.getBoundingClientRect(); return event.clientX >= rect.left && event.clientX <= rect.right; });
                this.focusAt(index === -1 ? this.value.length : Math.min(index, this.value.length));
            },
            isActive(i) { return this.focused && this.active === i; },
            showCaret(i) { return this.isActive(i) && !this.value[i] && !this.readonly; },
        }"
        x-modelable="value" {{ $attributes->whereStartsWith(['wire:model', 'x-model', 'x-on:']) }}
        x-on:pointerdown.prevent="select($event)"
        x-on:reset.window="if ($event.target.contains($el)) value = @js($initial)">

        {{-- One composed Input retains SMS autofill, paste, selection and native form validation. --}}
        <x-nui::input :id="$id" x-ref="control" :value="$initial" x-bind:value="value" :error="false"
            type="text" :inputmode="$mode === 'numeric' ? 'numeric' : 'text'" autocomplete="one-time-code"
            :autocapitalize="$mode === 'numeric' ? null : 'characters'" spellcheck="false" autocorrect="off"
            :maxlength="$length" :pattern="$pattern.'{'.$length.'}'"
            :aria-label="($label ?: 'Código de verificação').' · '.$length.' '.$unit"
            :aria-describedby="$error ? $id.'-error' : ($hint ? $id.'-hint' : null)" :aria-invalid="$error ? 'true' : null"
            x-on:input="input($event)" x-on:paste="paste($event)" x-on:keydown="keydown($event)"
            x-on:focus="focused = true; $nextTick(() => cursor())" x-on:blur="focused = false"
            x-on:keyup="cursor()" x-on:select="cursor()"
            class="absolute inset-0 size-full opacity-0 [&_input]:h-full [&_input]:caret-transparent [&_input]:selection:bg-transparent"
            {{ $attributes->whereDoesntStartWith(['wire:model', 'x-model', 'x-on:'])->except(['class', 'id', 'value', 'length', 'maxlength', 'inputmode', 'autocomplete', 'type', 'pattern', 'x-ref', 'aria-describedby', 'aria-invalid', 'autofocus']) }} />

        <div x-ref="cells" aria-hidden="true" @class(['pointer-events-none flex min-w-0 gap-1.5', 'opacity-50' => $disabled])>
            @for ($index = 0; $index < $length; $index++)
                @if ($group && $index && $index % $group === 0)
                    <span class="grid shrink-0 place-items-center px-0.5 text-base-content/35">
                        @if ($separator !== null)
                            <span class="text-sm">{{ $separator }}</span>
                        @else
                            <span class="h-px w-2 bg-current"></span>
                        @endif
                    </span>
                @endif
                <x-nui::box :invalid="(bool) $error" :data-otp-cell="$index"
                    x-bind:class="{
                        'outline-2 outline-primary -outline-offset-1': isActive({{ $index }}),
                        'bg-primary/10': isActive({{ $index }}) && selecting,
                        'ring-1 ring-inset ring-base-content/15': !!value[{{ $index }}] && !isActive({{ $index }}) && !@js((bool) $error),
                    }"
                    class="{{ $cellSize }} !min-w-0 flex-1 justify-center !px-0 font-mono font-medium tabular-nums transition-[outline,background-color]">
                    <span x-show="value[{{ $index }}]" x-text="@js((bool) $mask) ? '•' : value[{{ $index }}]">{{ isset($initial[$index]) ? ($mask ? '•' : $initial[$index]) : '' }}</span>
                    <span x-show="showCaret({{ $index }})" x-cloak class="h-[1.1em] w-px animate-pulse bg-base-content"></span>
                    @if ($placeholder !== null)
                        <span x-show="!value[{{ $index }}] && !showCaret({{ $index }})" class="text-base-content/30">{{ $placeholder }}</span>
                    @endif
                </x-nui::box>
            @endfor
        </div>
    </div>
</x-nui::field>
