@props([
    'language' => 'plaintext',
    'code' => null,
    'editable' => false,
    'languages' => null,
    'filename' => null,
    'lineNumbers' => true,
    'maxHeight' => '28rem',
    'wrap' => false,
])

@php
    $source = $code ?? trim((string) $slot, "\n");
    $languageNames = [
        'bash' => 'Bash', 'blade' => 'Blade', 'c' => 'C', 'cpp' => 'C++', 'csharp' => 'C#', 'css' => 'CSS', 'diff' => 'Diff',
        'dockerfile' => 'Dockerfile', 'go' => 'Go', 'html' => 'HTML', 'ini' => 'INI / .env', 'java' => 'Java', 'javascript' => 'JavaScript',
        'json' => 'JSON', 'kotlin' => 'Kotlin', 'markdown' => 'Markdown', 'php' => 'PHP', 'plaintext' => 'Texto', 'python' => 'Python',
        'ruby' => 'Ruby', 'rust' => 'Rust', 'scss' => 'SCSS', 'sql' => 'SQL', 'swift' => 'Swift', 'typescript' => 'TypeScript',
        'xml' => 'XML', 'yaml' => 'YAML',
    ];
    $choices = collect($languages ?? array_keys($languageNames))->mapWithKeys(fn (string $key) => [$key => $languageNames[$key] ?? $key])->all();
    $name = $attributes->get('name');
    $id = $attributes->get('id') ?? $name ?? 'nui-code-'.Str::random(6);
    $lineCount = max(1, substr_count($source, "\n") + 1);
@endphp

{{--
    Highlighted code via highlight.js (window.hljs, registered in resources/js/app.js).
    Read-only by default; editable adds a live editor (Tab indents) and a language picker,
    and works in forms (name) and with wire:model (x-modelable on the code).
--}}
<figure {{ $attributes->except(['name', 'id', 'wire:model', 'x-model'])->class('nui-code overflow-hidden rounded-nui border border-black/25 border-b-[3px] border-b-black/40 shadow-nui has-[textarea:focus-visible]:outline-2 has-[textarea:focus-visible]:outline-offset-2 has-[textarea:focus-visible]:outline-[var(--nui-code-accent)]') }}
    x-data="{
        code: @js($source),
        language: @js($language),
        html: '',
        copied: false,
        wrap: @js((bool) $wrap),
        focused: false,
        caretLine: 1,
        track(area) { this.caretLine = area.value.slice(0, area.selectionStart).split('\n').length },
        get lines() { return this.code.split('\n').length },
        render() {
            const escape = (text) => text.replace(/[&<>]/g, (char) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;' })[char]);
            const hljs = window.hljs;
            const language = hljs?.getLanguage(this.language) ? this.language : 'plaintext';
            {{-- Trailing newline keeps the overlay heights equal when the last line is empty. --}}
            this.html = hljs ? hljs.highlight(this.code + '\n', { language, ignoreIllegals: true }).value : escape(this.code + '\n');
        },
        indent(event) {
            const area = event.target, start = area.selectionStart, end = area.selectionEnd;
            this.code = this.code.slice(0, start) + '    ' + this.code.slice(end);
            this.$nextTick(() => area.setSelectionRange(start + 4, start + 4));
        },
        async copy() {
            try { await navigator.clipboard.writeText(this.code); this.copied = true; setTimeout(() => (this.copied = false), 1600); } catch (error) {}
        },
    }"
    x-modelable="code"
    {{ $attributes->whereStartsWith(['wire:model', 'x-model']) }}
    x-init="render(); window.hljs || window.addEventListener('nui-hljs-ready', () => render(), { once: true }); $watch('code', () => render()); $watch('language', () => render())">

    <figcaption class="flex items-center gap-3 pl-4 pr-2 h-11 bg-[var(--nui-code-bar)] border-b border-white/[0.07] text-xs text-[var(--nui-code-muted)]">
        <svg aria-hidden="true" class="size-4 shrink-0 text-[var(--nui-code-accent)]" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="m5.5 5-3 3 3 3M10.5 5l3 3-3 3M9 3.5 7 12.5" /></svg>
        <span class="font-mono truncate text-[var(--nui-code-fg)]/85">{{ $filename ?? ($choices[$language] ?? $languageNames[$language] ?? $language) }}</span>
        @if ($editable)
            <span class="hidden sm:inline-flex items-center gap-1 h-5 px-1.5 rounded-full bg-white/[0.06] text-[0.6875rem] font-medium">
                <svg aria-hidden="true" class="size-3" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="m10.5 2.5 3 3L6 13H3v-3l7.5-7.5Z" /></svg>
                Editável
            </span>
        @endif

        <span class="ml-auto flex items-center gap-1">
            @if ($editable)
                <x-nui::select id="{{ $id }}-language" x-model="language" :options="$choices" :value="$language" searchable aria-label="Linguagem"
                    class="w-36 [&_[x-ref=anchor]]:!min-h-8 [&_[x-ref=anchor]]:!px-2.5 [&_[x-ref=anchor]]:!text-xs [&_[x-ref=anchor]]:!bg-transparent [&_[x-ref=anchor]]:hover:!bg-white/[0.06] [&_[x-ref=anchor]]:!border [&_[x-ref=anchor]]:!border-white/10 [&_[x-ref=anchor]]:!text-[var(--nui-code-fg)] [&_[x-ref=anchor]]:!shadow-none [&_[x-ref=anchor]]:!rounded-[calc(var(--nui-radius)-4px)] [&_[x-ref=anchor]_svg]:!text-[var(--nui-code-muted)]" />
            @elseif ($filename)
                <span class="h-6 inline-flex items-center px-2 rounded-full bg-white/[0.06] font-mono text-[0.6875rem] uppercase tracking-wide">{{ $choices[$language] ?? $languageNames[$language] ?? $language }}</span>
            @endif
            <x-nui::button variant="ghost" size="sm" type="button" x-on:click="wrap = ! wrap" x-bind:aria-pressed="wrap.toString()" aria-pressed="{{ $wrap ? 'true' : 'false' }}" aria-label="Quebrar linhas longas" title="Quebrar linhas longas"
                x-bind:class="wrap ? 'bg-white/[0.08] text-[var(--nui-code-accent)]' : 'text-[var(--nui-code-fg)]/70'"
                class="!p-0 !text-current grid place-items-center size-8 rounded-[calc(var(--nui-radius)-4px)] hover:text-[var(--nui-code-fg)] hover:bg-white/[0.06] focus-visible:outline-2 focus-visible:outline-[var(--nui-code-accent)]">
                <svg aria-hidden="true" class="size-4" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M2.5 4h11M2.5 8h9a2 2 0 0 1 0 4H8m0 0 1.5-1.5M8 12l1.5 1.5M2.5 12h3" /></svg>
            </x-nui::button>
            <x-nui::button variant="ghost" size="sm" type="button" x-on:click="copy()" x-bind:aria-label="copied ? 'Copiado' : 'Copiar código'" aria-label="Copiar código"
                class="!p-0 !text-current inline-flex items-center gap-1.5 h-8 px-2.5 rounded-[calc(var(--nui-radius)-4px)] font-medium text-[var(--nui-code-fg)]/80 hover:text-[var(--nui-code-fg)] hover:bg-white/[0.06] focus-visible:outline-2 focus-visible:outline-[var(--nui-code-accent)]">
                <svg x-show="! copied" aria-hidden="true" class="size-3.5" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round"><rect x="5.5" y="5.5" width="8" height="8" rx="1.5" /><path d="M10.5 5.5v-2a1 1 0 0 0-1-1h-6a1 1 0 0 0-1 1v6a1 1 0 0 0 1 1h2" /></svg>
                <svg x-show="copied" x-cloak aria-hidden="true" class="size-3.5 text-[oklch(80%_0.11_145)]" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m3.5 8.5 3 3 6-7" /></svg>
                <span class="hidden sm:inline" x-text="copied ? 'Copiado' : 'Copiar'">Copiar</span>
            </x-nui::button>
        </span>
    </figcaption>

    <div data-lenis-prevent class="relative flex overflow-auto bg-[var(--nui-code-bg)] text-[var(--nui-code-fg)] font-mono text-[0.8125rem] leading-6" style="max-height: {{ $maxHeight }}">
        @if ($editable)
            {{-- Current line band, shown while editing. --}}
            <div aria-hidden="true" x-show="focused && ! wrap" x-cloak class="pointer-events-none absolute inset-x-0 h-6 bg-white/[0.04] transition-[top] duration-100" x-bind:style="`top: ${16 + (caretLine - 1) * 24}px`"></div>
        @endif
        @if ($lineNumbers)
            <div aria-hidden="true" x-show="! wrap" class="sticky left-0 z-[1] shrink-0 py-4 pl-4 pr-3 text-right select-none tabular-nums text-[var(--nui-code-muted)]/60 bg-[var(--nui-code-bg)]">
                <template x-for="line in lines" x-bind:key="line"><div x-text="line" x-bind:class="focused && line === caretLine && 'text-[var(--nui-code-accent)]'"></div></template>
                <noscript>@for ($line = 1; $line <= $lineCount; $line++)<div>{{ $line }}</div>@endfor</noscript>
            </div>
        @endif

        {{-- Editor = transparent textarea laid exactly over the highlighted <pre>; both share font, padding and wrapping. --}}
        <div class="relative flex-1 min-w-0 grid">
            <pre class="[grid-area:1/1] m-0 py-4 pr-4 {{ $lineNumbers ? 'pl-1' : 'pl-4' }} overflow-visible pointer-events-none" x-bind:class="wrap ? 'whitespace-pre-wrap break-words pl-4' : 'whitespace-pre'" aria-hidden="{{ $editable ? 'true' : 'false' }}"><code class="hljs" x-html="html">{{ $source }}</code></pre>
            @if ($editable)
                <textarea id="{{ $id }}" @if ($name) name="{{ $name }}" @endif x-model="code" x-on:keydown.tab.prevent="indent($event)"
                    x-on:focus="focused = true" x-on:blur="focused = false"
                    x-on:keyup="track($el)" x-on:click="track($el)" x-on:input="track($el)"
                    spellcheck="false" autocapitalize="off" autocomplete="off" aria-label="Editor de código"
                    class="[grid-area:1/1] m-0 py-4 pr-4 {{ $lineNumbers ? 'pl-1' : 'pl-4' }} overflow-hidden resize-none bg-transparent text-transparent caret-[var(--nui-code-accent)] outline-none selection:bg-white/20" x-bind:class="wrap ? 'whitespace-pre-wrap break-words pl-4' : 'whitespace-pre'">{{ $source }}</textarea>
            @endif
        </div>
    </div>

    @if ($editable)
        <div class="flex items-center justify-between gap-3 px-4 h-8 bg-[var(--nui-code-bar)] border-t border-white/[0.07] font-mono text-[0.6875rem] text-[var(--nui-code-muted)]" aria-live="polite">
            <span x-text="`Linha ${caretLine}, ${lines} ${lines === 1 ? 'linha' : 'linhas'}`">{{ $lineCount }} linhas</span>
            <span><x-nui::kbd size="sm" class="!bg-white/[0.06] !text-[var(--nui-code-fg)]/80 !border-white/10 !border-b-white/20">Tab</x-nui::kbd> indenta</span>
        </div>
    @endif
</figure>
