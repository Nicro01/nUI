@props(['label' => null,'labelStyle' => 'top', 'hint' => null, 'error' => null, 'mask' => null, 'regex' => null])

@php
    $name = $attributes->get('name');
    $id = $attributes->get('id') ?? $name ?? $attributes->whereStartsWith('wire:model')->first() ?? 'nui-'.Str::random(8);
    $error ??= $name ? ($errors ?? null)?->first($name) : null;

    // Mask tokens: 9 = digit, a = letter, A = letter (uppercased), * = letter or digit, # = letter or digit (uppercased); anything else is a literal.
    // Several masks separated by | are tried in order of length (e.g. landline vs mobile phone).
    $presets = [
        'cpf' => '999.999.999-99',
        'cnpj' => '99.999.999/9999-99',
        'cpf-cnpj' => '999.999.999-99|99.999.999/9999-99',
        'cep' => '99999-999',
        'phone' => '(99) 9999-9999|(99) 99999-9999',
        'date' => '99/99/9999',
        'time' => '99:99',
        'card' => '9999 9999 9999 9999',
        'plate' => 'AAA-9#99',
        'money' => 'money',
    ];
    $masks = $mask ? explode('|', $presets[$mask] ?? $mask) : [];
    $numeric = $masks && collect($masks)->every(fn (string $pattern) => $pattern === 'money' || ! preg_match('/[aA*#]/', $pattern));
@endphp

{{-- Text-like inputs: text, email, url, tel, search… Optional mask/regex, and leading/trailing slots for icons or affixes. --}}
<x-nui::field :label="$labelStyle === 'top' ? $label : null" :$hint :$error :for="$id" :class="$attributes->get('class')">
    <div class="contents"
        @if ($masks || $regex)
            x-data="{
                masks: @js($masks),
                regex: @js($regex),
                format(raw) {
                    if (this.regex) { const allowed = new RegExp(this.regex); raw = [...raw].filter((char) => allowed.test(char)).join(''); }
                    if (! this.masks.length) return raw;
                    if (this.masks[0] === 'money') {
                        const digits = raw.replace(/\D/g, '').replace(/^0+/, '').slice(0, 15);
                        return digits ? (Number(digits) / 100).toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) : '';
                    }
                    const chars = [...raw.replace(/[^0-9a-zA-ZÀ-ÿ]/g, '')];
                    const slots = (pattern) => (pattern.match(/[9aA*#]/g) || []).length;
                    const pattern = this.masks.find((candidate) => slots(candidate) >= chars.length) ?? this.masks.at(-1);
                    const fits = { '9': /\d/, a: /\p{L}/u, A: /\p{L}/u, '*': /[\p{L}\d]/u, '#': /[\p{L}\d]/u };
                    let out = '', index = 0;
                    for (const token of pattern) {
                        if (index >= chars.length) break;
                        if (fits[token]) {
                            while (index < chars.length && ! fits[token].test(chars[index])) index++;
                            if (index >= chars.length) break;
                            out += token === 'A' || token === '#' ? chars[index].toUpperCase() : chars[index];
                            index++;
                        } else {
                            out += token;
                        }
                    }
                    return out;
                },
                apply(input) {
                    const atEnd = input.selectionStart === input.value.length;
                    const caret = input.selectionStart;
                    const before = input.value.length;
                    input.value = this.format(input.value);
                    const position = atEnd ? input.value.length : Math.max(0, caret + input.value.length - before);
                    input.setSelectionRange?.(position, position);
                },
            }"
            x-init="$refs.input.value && ($refs.input.value = format($refs.input.value))"
        @endif>
    <x-nui::box :invalid="(bool) $error" :label="$labelStyle === 'top' ? null : $label" :for="$id" :label-style="$labelStyle">
        @isset($leading)
            <span class="flex shrink-0 items-center text-base-content/55 [&_svg]:size-4">{{ $leading }}</span>
        @endisset
        <input id="{{ $id }}"
            @if ($masks || $regex) x-ref="input" x-on:input.capture="apply($el)" @endif
            @if ($numeric) inputmode="numeric" @endif
            @if ($error) aria-invalid="true" aria-describedby="{{ $id }}-error" @elseif ($hint) aria-describedby="{{ $id }}-hint" @endif
            {{ $attributes->except(['id', 'class'])->merge($labelStyle === 'floating' ? ['placeholder' => "\u{00A0}"] : [])->merge(['type' => 'text'])->class('w-full min-w-0 py-2 bg-transparent outline-none placeholder:text-base-content/45 disabled:cursor-not-allowed') }}>
        @isset($trailing)
            <span class="flex shrink-0 items-center text-base-content/55 [&_svg]:size-4">{{ $trailing }}</span>
        @endisset
    </x-nui::box>
    </div>
</x-nui::field>
