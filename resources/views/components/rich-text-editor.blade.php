@props([
    'value' => null, 'label' => null, 'hint' => null, 'error' => null,
    'placeholder' => 'Comece a escrever…', 'disabled' => false, 'readonly' => false,
    'toolbar' => true,
])

@php
    $name = $attributes->get('name');
    $model = $attributes->whereStartsWith('wire:model')->first();
    $id = $attributes->get('id') ?? $name ?? 'nui-rich-text-'.Str::random(8);
    $source = (string) ($name ? old($name, $value ?? (string) $slot) : ($value ?? (string) $slot));
    $error ??= ($name || $model) ? ($errors ?? null)?->first($name ?? $model) : null;
    $description = $attributes->get('aria-describedby') ?? ($error ? $id.'-error' : ($hint ? $id.'-hint' : null));
    $groups = [
        [
            ['bold', 'Negrito', 'M6 4h6a4 4 0 0 1 0 8H6zm0 8h7a4 4 0 0 1 0 8H6z'],
            ['italic', 'Itálico', 'M19 4h-9m4 16H5M15 4 9 20'],
            ['underline', 'Sublinhado', 'M6 3v7a6 6 0 0 0 12 0V3M4 21h16'],
            ['strike', 'Tachado', 'M16 4H9a4 4 0 0 0-3 6m12 4a4 4 0 0 1-4 6H6M3 12h18'],
        ],
        [
            ['heading2', 'Título 2', 'M4 5v14M14 5v14M4 12h10M18 11a2 2 0 0 1 4 0c0 2-4 3-4 5h4'],
            ['heading3', 'Título 3', 'M4 5v14M14 5v14M4 12h10M18 9h2a2 2 0 0 1 0 4h-1m1 0a2 2 0 0 1 0 4h-2'],
        ],
        [
            ['bulletList', 'Lista com marcadores', 'M9 6h12M9 12h12M9 18h12M3 6h.01M3 12h.01M3 18h.01'],
            ['orderedList', 'Lista numerada', 'M10 6h11M10 12h11M10 18h11M3 4h1v5M3 14a2 2 0 0 1 4 0c0 2-4 3-4 5h4'],
            ['blockquote', 'Citação', 'M3 11h6V5H3v8c0 3 2 5 5 6m7-8h6V5h-6v8c0 3 2 5 5 6'],
            ['codeBlock', 'Bloco de código', 'm8 8-4 4 4 4m8-8 4 4-4 4M14 4l-4 16'],
            ['link', 'Inserir ou editar link', 'M10 13a5 5 0 0 0 7 0l3-3a5 5 0 0 0-7-7l-2 2m3 6a5 5 0 0 0-7 0l-3 3a5 5 0 0 0 7 7l2-2'],
        ],
        [
            ['undo', 'Desfazer', 'M3 7v6h6M3 13a8 8 0 1 1 3 6'],
            ['redo', 'Refazer', 'M21 7v6h-6m6 0a8 8 0 1 0-3 6'],
        ],
    ];
@endphp

<div {{ $attributes->except(['name', 'id', 'wire:model', 'x-model'])->whereDoesntStartWith(['wire:model', 'x-model'])->class('nui-rich-text w-full min-w-0') }}
    data-invalid="{{ $error ? 'true' : 'false' }}" data-description="{{ $description }}"
    data-disabled="{{ $disabled ? 'true' : 'false' }}" data-readonly="{{ $readonly ? 'true' : 'false' }}"
    x-data="{
        value: @js($source), ready: false, bridge: null,
        disabled: @js((bool) $disabled), readOnly: @js((bool) $readonly),
        active: {}, canUndo: false, canRedo: false, words: 0,
        linkOpen: false, url: '', linkError: '',
        init() {
            this.mount = () => {
                if (this.bridge) return;
                this.bridge = window.nuiMountRichText(this, @js(['id' => $id, 'label' => $label ?? $attributes->get('aria-label', 'Editor de texto'), 'placeholder' => $placeholder, 'invalid' => (bool) $error, 'describedBy' => $description]));
                this.bridge.setEditable();
            };
            if (window.nuiMountRichText) this.$nextTick(() => this.mount());
            else {
                window.addEventListener('nui-rich-text-ready', this.mount, { once: true });
                window.dispatchEvent(new Event('nui-rich-text-request'));
            }
            this.$watch('value', (value) => this.bridge?.setValue(value));
            this.$watch('disabled', () => this.bridge?.setEditable());
            this.$watch('readOnly', () => this.bridge?.setEditable());
        },
        destroy() {
            window.removeEventListener('nui-rich-text-ready', this.mount);
            this.bridge?.destroy();
        },
        toolbarKey(event) {
            if (!['ArrowLeft', 'ArrowRight', 'Home', 'End'].includes(event.key)) return;
            const buttons = [...event.currentTarget.querySelectorAll('button:not(:disabled)')];
            const index = buttons.indexOf(event.target);
            if (index < 0) return;
            event.preventDefault();
            const next = event.key === 'Home' ? 0 : event.key === 'End' ? buttons.length - 1 : (index + (event.key === 'ArrowRight' ? 1 : -1) + buttons.length) % buttons.length;
            buttons[next]?.focus();
        }
    }"
    x-modelable="value" {{ $attributes->whereStartsWith(['wire:model', 'x-model']) }}
    x-on:click="if ($event.target.tagName === 'LABEL' && $event.target.htmlFor === @js($id)) bridge?.focus()">
    <x-nui::field :$label :$hint :$error :for="$id">
        <div x-show="!ready">
            <x-nui::textarea id="{{ $id }}-fallback" :name="$name" :readonly="$readonly" :disabled="$disabled"
                :placeholder="$placeholder" :aria-label="$label ?? 'Editor de texto'" x-bind:disabled="ready || disabled">{{ $source }}</x-nui::textarea>
        </div>
        <div wire:ignore x-show="ready" x-cloak x-bind:class="disabled && 'opacity-60'">
            <x-nui::box :invalid="(bool) $error" class="nui-rich-text-box !block !p-0 !opacity-100 !cursor-auto overflow-hidden">
                @if ($toolbar && !$readonly)
                    <div role="toolbar" aria-label="Formatação do texto" class="flex flex-wrap items-center gap-1 border-b border-field-border bg-base-200/50 p-2" x-on:keydown="toolbarKey($event)" x-show="!readOnly">
                        @foreach ($groups as $group)
                            <div class="flex items-center gap-1">
                                @foreach ($group as [$action, $title, $path])
                                    @php
                                        $control = new \Illuminate\View\ComponentAttributeBag([
                                            'aria-label' => $title, 'title' => $title,
                                            'disabled' => $disabled ? true : null,
                                            'x-bind:disabled' => 'disabled || readOnly || !ready'.($action === 'undo' ? ' || !canUndo' : ($action === 'redo' ? ' || !canRedo' : '')),
                                            'x-bind:aria-pressed' => in_array($action, ['undo', 'redo']) ? 'null' : "Boolean(active['{$action}']).toString()",
                                            'x-on:mousedown.prevent' => '',
                                            'x-on:click' => $action === 'link' ? 'bridge.openLink()' : "bridge.run('{$action}')",
                                        ]);
                                    @endphp
                                    <x-nui::button variant="ghost" size="sm" class="!size-9 !p-0" :aria-label="$title" :title="$title"
                                        :attributes="$control">
                                        <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="{{ $path }}" /></svg>
                                    </x-nui::button>
                                @endforeach
                            </div>
                            @unless ($loop->last)<span aria-hidden="true" class="mx-0.5 h-5 w-px bg-field-border"></span>@endunless
                        @endforeach
                    </div>
                @endif

                <div x-show="linkOpen" x-cloak role="group" aria-label="Editar link" class="border-b border-field-border bg-base-200/40 p-3"
                    x-on:keydown.escape.prevent.stop="linkOpen = false; bridge.focus()">
                    <x-nui::input id="{{ $id }}-link" label="Endereço do link" type="text" inputmode="url" placeholder="https://exemplo.com" data-link-input x-model="url"
                        x-bind:aria-invalid="Boolean(linkError).toString()" aria-describedby="{{ $id }}-link-error" x-on:keydown.enter.prevent="bridge.saveLink()" />
                    <p id="{{ $id }}-link-error" x-show="linkError" x-text="linkError" class="mt-1 text-sm text-danger" role="alert"></p>
                    <div class="mt-3 flex flex-wrap gap-2">
                        <x-nui::button size="sm" x-on:click="bridge.saveLink()">Aplicar link</x-nui::button>
                        <x-nui::button size="sm" variant="ghost" x-on:click="bridge.removeLink()" x-bind:disabled="!active.link">Remover link</x-nui::button>
                        <x-nui::button size="sm" variant="ghost" x-on:click="linkOpen = false; bridge.focus()">Cancelar</x-nui::button>
                    </div>
                </div>

                <div x-ref="editor" class="min-w-0" data-rich-text-editor></div>
                <div class="flex flex-wrap items-center justify-between gap-2 border-t border-field-border px-4 py-2 text-xs text-base-content/70">
                    <span x-text="readOnly ? 'Somente leitura' : (disabled ? 'Edição desativada' : 'Enter para novo parágrafo')">{{ $readonly ? 'Somente leitura' : ($disabled ? 'Edição desativada' : 'Enter para novo parágrafo') }}</span>
                    <span class="tabular-nums" x-text="words + (words === 1 ? ' palavra' : ' palavras')"></span>
                </div>
            </x-nui::box>
        </div>
        @if ($name)
            <x-nui::input type="hidden" :name="$name" :value="$source" class="hidden" x-model="value" :disabled="$disabled" x-bind:disabled="!ready || disabled" />
        @endif
    </x-nui::field>
</div>
