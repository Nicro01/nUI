@props([
    'label' => 'Avaliação',
    'hint' => null,
    'error' => null,
    'value' => 0,
    'readonly' => false,
    'size' => 'md',
])

@php
    $name = $attributes->get('name');
    $id = $attributes->get('id') ?? 'nui-rating-'.Str::random(6);
    $error ??= $name ? ($errors ?? null)?->first($name) : null;
    $initial = $readonly ? $value : ($name ? old($name, $value) : $value);
    $initial = min(5, max(0, (float) $initial));
    $selected = (int) round($initial);
    $description = $error ? $id.'-error' : ($hint ? $id.'-hint' : null);
    $targetSize = match ($size) {
        'sm' => 'size-8',
        'lg' => 'size-12',
        default => 'size-10',
    };
    $iconSize = match ($size) {
        'sm' => 'size-5',
        'lg' => 'size-9',
        default => 'size-7',
    };
    $star = 'm12 2.75 2.86 5.8 6.4.93-4.63 4.51 1.09 6.38L12 17.36l-5.72 3.01 1.09-6.38-4.63-4.51 6.4-.93L12 2.75Z';
@endphp

{{-- Native radios preserve form submission, reset, arrow keys and wire:model without JavaScript. --}}
<fieldset {{ $attributes->only(['id', 'class', 'style', 'disabled', 'form'])->merge(['id' => $id])->class('min-w-0 m-0 border-0 p-0 disabled:opacity-50') }}
    {{ $attributes->whereStartsWith(['aria-', 'data-']) }}
    @if ($description) aria-describedby="{{ $description }}" @endif
    @if ($error) aria-invalid="true" @endif>
    <legend class="pb-1.5 text-sm font-medium text-base-content">{{ $label }}</legend>

    <div @class(['inline-flex max-w-full items-center gap-0.5', 'nui-rating' => ! $readonly])
        @if ($readonly) role="img" aria-label="{{ $initial }} de 5 estrelas" @endif>
        @for ($rating = 1; $rating <= 5; $rating++)
            @if ($readonly)
                <span aria-hidden="true" class="grid shrink-0 place-items-center {{ $targetSize }}">
                    <svg class="{{ $iconSize }} text-base-content/60" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round">
                        <path d="{{ $star }}" />
                        <path d="{{ $star }}" class="text-primary" fill="currentColor" style="clip-path: inset(0 {{ 100 - min(1, max(0, $initial - $rating + 1)) * 100 }}% 0 0)" />
                    </svg>
                </span>
            @else
                <label class="nui-rating-star relative grid shrink-0 place-items-center rounded-nui-field {{ $targetSize }}">
                    <input type="radio" id="{{ $id }}-{{ $rating }}" name="{{ $name ?? $id }}" value="{{ $rating }}"
                        aria-label="{{ $rating }} {{ $rating === 1 ? 'estrela' : 'estrelas' }}"
                        @if ($description) aria-describedby="{{ $description }}" @endif
                        @if ($error) aria-invalid="true" @endif
                        @checked($selected === $rating)
                        {{ $attributes->except(['id', 'name', 'class', 'style', 'type', 'value', 'checked', 'disabled', 'aria-label', 'aria-labelledby', 'aria-describedby', 'aria-invalid', 'role'])->whereDoesntStartWith('data-') }}
                        class="absolute inset-0 size-full m-0 cursor-pointer opacity-0 disabled:cursor-not-allowed">
                    <svg aria-hidden="true" class="pointer-events-none {{ $iconSize }} text-base-content/60" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round">
                        <path d="{{ $star }}" />
                        <path d="{{ $star }}" class="nui-rating-fill text-primary" fill="currentColor" />
                    </svg>
                </label>
            @endif
        @endfor
    </div>

    @if ($error)
        <p id="{{ $id }}-error" class="mt-1.5 text-sm text-danger">{{ $error }}</p>
    @elseif ($hint)
        <p id="{{ $id }}-hint" class="mt-1.5 text-sm text-base-content/60">{{ $hint }}</p>
    @endif
</fieldset>
