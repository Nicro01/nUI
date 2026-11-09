@props([
    'label' => null,
    'hint' => null,
    'error' => null,
    'min' => 0,
    'max' => 100,
    'step' => 1,
    'value' => null,
    'dual' => false,
    'prefix' => '',
    'suffix' => '',
    'ticks' => false,
    'marks' => [],
    'showValue' => true,
])

@php
    $name = $attributes->get('name');
    $id = $attributes->get('id') ?? $name ?? $attributes->whereStartsWith('wire:model')->first() ?? 'nui-range-'.Str::random(6);
    $error ??= $name ? ($errors ?? null)?->first($name) : null;
    $initial = $name ? old($name, $value) : $value;
    $initial = $dual
        ? array_values((array) ($initial ?? [$min, $max])) + [$min, $max]
        : ($initial ?? $min);
    $tickCount = $ticks ? (int) floor(($max - $min) / max($step, 0.0001)) : 0;
    $tickCount = $tickCount > 0 && $tickCount <= 40 ? $tickCount : 0;
    $decimals = str_contains((string) $step, '.') ? strlen(substr(strrchr((string) $step, '.'), 1)) : 0;
@endphp

{{--
    Slider on real <input type="range"> elements: arrows, Home/End, forms and wire:model work natively.
    dual = two thumbs (value is [min, max], posted as name[min] and name[max]).
    prefix/suffix format the value (R$ 1.200, 75%); ticks draws step marks; marks = [value => 'label'] under the track.
--}}
<x-nui::field :$label :$hint :$error :for="$id" :class="$attributes->get('class')">
    <div class="contents"
        x-data="{
            min: @js((float) $min),
            max: @js((float) $max),
            dual: @js((bool) $dual),
            value: @js($initial),
            pct(number) { return ((number - this.min) / (this.max - this.min)) * 100 },
            format(number) { return @js($prefix) + Number(number).toLocaleString('pt-BR', { minimumFractionDigits: @js($decimals), maximumFractionDigits: @js($decimals) }) + @js($suffix) },
            setLow(number) { this.value = [Math.min(number, this.value[1]), this.value[1]] },
            setHigh(number) { this.value = [this.value[0], Math.max(number, this.value[0])] },
            get start() { return this.dual ? this.pct(this.value[0]) : 0 },
            get end() { return this.pct(this.dual ? this.value[1] : this.value) },
            get display() { return this.dual ? this.format(this.value[0]) + ' – ' + this.format(this.value[1]) : this.format(this.value) },
        }"
        x-modelable="value"
        {{ $attributes->whereStartsWith(['x-model']) }}
        {{ $dual ? $attributes->whereStartsWith(['wire:model']) : '' }}>

        @if ($showValue)
            <output for="{{ $id }}" class="{{ $label ? '-mt-7' : '' }} self-end text-sm font-semibold tabular-nums text-base-content" x-text="display" aria-live="polite">
                {{ $dual ? $prefix.$initial[0].$suffix.' – '.$prefix.$initial[1].$suffix : $prefix.$initial.$suffix }}
            </output>
        @endif

        <div @class(['nui-range relative h-7 flex items-center', 'nui-range-dual' => $dual, 'nui-range-invalid' => $error])>
            {{-- Track and fill --}}
            <div aria-hidden="true" class="absolute inset-x-0 h-2 rounded-full bg-base-content/10 shadow-nui-inset"></div>
            <div aria-hidden="true" class="absolute h-2 rounded-full {{ $error ? 'bg-danger' : 'bg-primary' }} shadow-[inset_0_-1px_0_oklch(0%_0_0/0.18)]"
                x-bind:style="`left: ${start}%; right: ${100 - end}%`"
                style="left: 0%; right: {{ 100 - ($dual ? 100 : (($initial - $min) / max($max - $min, 1)) * 100) }}%"></div>

            @if ($tickCount)
                <div aria-hidden="true" class="pointer-events-none absolute inset-x-[0.6875rem] flex justify-between">
                    @for ($tick = 0; $tick <= $tickCount; $tick++)
                        <span class="h-3.5 w-px rounded-full bg-base-content/20"></span>
                    @endfor
                </div>
            @endif

            @foreach ($dual ? ['low', 'high'] : ['single'] as $thumb)
                @php($thumbId = $dual ? $id.'-'.$thumb : $id)
                <input id="{{ $thumbId }}" type="range" min="{{ $min }}" max="{{ $max }}" step="{{ $step }}"
                    @if ($dual)
                        @if ($name) name="{{ $name }}[{{ $thumb === 'low' ? 'min' : 'max' }}]" @endif
                        value="{{ $thumb === 'low' ? $initial[0] : $initial[1] }}"
                        x-bind:value="value[{{ $thumb === 'low' ? 0 : 1 }}]"
                        x-on:input="{{ $thumb === 'low' ? 'setLow' : 'setHigh' }}(Number($event.target.value)); $event.target.value = value[{{ $thumb === 'low' ? 0 : 1 }}]"
                        aria-label="{{ ($label ? $label.': ' : '').($thumb === 'low' ? 'mínimo' : 'máximo') }}"
                    @else
                        value="{{ $initial }}" x-bind:value="value"
                        x-on:input="value = Number($event.target.value)"
                        {{ $attributes->except(['id', 'class', 'type', 'min', 'max', 'step', 'value', 'x-model']) }}
                    @endif
                    x-bind:aria-valuetext="format({{ $dual ? 'value['.($thumb === 'low' ? 0 : 1).']' : 'value' }})"
                    @if ($error) aria-invalid="true" aria-describedby="{{ $id }}-error" @elseif ($hint) aria-describedby="{{ $id }}-hint" @endif
                    @disabled($attributes->get('disabled'))
                    class="peer/{{ $thumb }} absolute inset-0 w-full h-full m-0 bg-transparent appearance-none cursor-pointer disabled:cursor-not-allowed">
                {{-- Value bubble: grows out of the thumb while it is dragged or focused --}}
                <span aria-hidden="true"
                    class="nui-range-bubble pointer-events-none absolute bottom-full mb-2 -translate-x-1/2 px-2 py-1 rounded-[min(var(--nui-radius),0.5rem)] bg-[oklch(23%_0.02_260)] text-[oklch(95%_0.01_85)] text-xs font-semibold tabular-nums whitespace-nowrap shadow-[0_8px_20px_-8px_oklch(0%_0_0/0.5)]
                        opacity-0 scale-75 translate-y-1 origin-bottom transition duration-200 ease-[cubic-bezier(0.16,1,0.3,1)]
                        peer-active/{{ $thumb }}:opacity-100 peer-active/{{ $thumb }}:scale-100 peer-active/{{ $thumb }}:translate-y-0
                        peer-focus-visible/{{ $thumb }}:opacity-100 peer-focus-visible/{{ $thumb }}:scale-100 peer-focus-visible/{{ $thumb }}:translate-y-0"
                    x-bind:style="`left: calc(${ {{ $thumb === 'high' ? 'end' : ($thumb === 'low' ? 'start' : 'end') }} }% + ${ 11 - {{ $thumb === 'high' ? 'end' : ($thumb === 'low' ? 'start' : 'end') }} * 0.22 }px)`"
                    x-text="format({{ $dual ? 'value['.($thumb === 'low' ? 0 : 1).']' : 'value' }})"></span>
            @endforeach
        </div>

        @if ($marks)
            <div aria-hidden="true" class="relative h-5 mx-[0.6875rem] text-xs text-base-content/55">
                @foreach ($marks as $markValue => $markLabel)
                    <span class="absolute -translate-x-1/2 whitespace-nowrap" style="left: {{ (($markValue - $min) / max($max - $min, 1)) * 100 }}%">{{ $markLabel }}</span>
                @endforeach
            </div>
        @endif
    </div>
</x-nui::field>
