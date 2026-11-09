@props([
    'action' => null,
    'method' => 'POST',
    'legend' => null,
    'description' => null,
    'submitLabel' => null,
    'summary' => false,
    'errorBag' => 'default',
])

@php
    $method = strtoupper($method);
    if (! in_array($method, ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'DIALOG'], true)) {
        throw new InvalidArgumentException('Form: método HTTP inválido.');
    }
    $htmlMethod = in_array($method, ['GET', 'DIALOG'], true) ? strtolower($method) : 'post';
    $messages = ($errors ?? new Illuminate\Support\ViewErrorBag)->getBag($errorBag);
    $wireSubmit = $attributes->whereStartsWith('wire:submit')->first();
@endphp

<form method="{{ $htmlMethod }}" @if ($action) action="{{ $action }}" @endif
    {{ $attributes->class($method === 'DIALOG' ? [] : ['flex min-w-0 flex-col gap-5']) }}>
    @if ($method === 'DIALOG')
        {{ $slot }}
    @else
        @if ($method !== 'GET')
            @csrf
        @endif
        @if (in_array($method, ['PUT', 'PATCH', 'DELETE'], true))
            @method($method)
        @endif

        @if ($summary && $messages->any())
            <x-nui::alert variant="danger" title="Confira os campos do formulário">
                <ul class="list-disc space-y-1 pl-4">
                    @foreach ($messages->all() as $message)
                        <li>{{ $message }}</li>
                    @endforeach
                </ul>
            </x-nui::alert>
        @endif

        @if ($legend)
            <x-nui::fieldset :$legend :$description>{{ $slot }}</x-nui::fieldset>
        @else
            @if ($description)
                <p class="text-sm leading-relaxed text-base-content/65">{{ $description }}</p>
            @endif
            <div class="flex min-w-0 flex-col gap-4">{{ $slot }}</div>
        @endif

        @if (isset($actions) || $submitLabel)
            <div class="flex flex-wrap items-center justify-end gap-2">
                @isset($actions)
                    {{ $actions }}
                @else
                    @if ($wireSubmit)
                        <x-nui::button type="submit" wire:loading.attr="disabled" :wire:target="$wireSubmit">{{ $submitLabel }}</x-nui::button>
                    @else
                        <x-nui::button type="submit">{{ $submitLabel }}</x-nui::button>
                    @endif
                @endisset
            </div>
        @endif
    @endif
</form>
