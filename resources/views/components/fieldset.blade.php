@props(['legend' => null, 'description' => null, 'error' => null])

@php
    $id = $attributes->get('id') ?? 'nui-fieldset-'.Str::random(6);
    $described = trim(implode(' ', array_filter([
        $attributes->get('aria-describedby'),
        $description ? $id.'-description' : null,
        $error ? $id.'-error' : null,
    ])));
@endphp

<fieldset {{ $attributes->except('aria-describedby')->merge([
    'id' => $id,
    'aria-describedby' => $described ?: null,
    'aria-invalid' => $error ? 'true' : null,
])->class([
    'min-w-0 rounded-nui border-[length:var(--nui-border-width)] bg-base-100 p-5 pt-4 text-base-content shadow-nui disabled:opacity-50',
    $error ? 'border-danger' : 'border-base-300',
]) }}>
    @if ($legend)
        <legend class="max-w-full px-2 text-sm font-semibold">{{ $legend }}</legend>
    @endif
    @if ($description)
        <p id="{{ $id }}-description" class="mb-5 text-sm leading-relaxed text-base-content/65">{{ $description }}</p>
    @endif
    <div class="flex min-w-0 flex-col gap-4">{{ $slot }}</div>
    @if ($error)
        <p id="{{ $id }}-error" class="mt-4 text-sm text-danger" role="alert">{{ $error }}</p>
    @endif
    @isset($actions)
        <div class="mt-5 flex flex-wrap items-center justify-end gap-2 border-t border-base-content/10 pt-4">{{ $actions }}</div>
    @endisset
</fieldset>
