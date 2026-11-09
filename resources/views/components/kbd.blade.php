@props(['keys' => null, 'size' => 'md'])

@php
    // Special keys are drawn as SVG icons (never text glyphs); each has a spoken label for screen readers.
    $icons = [
        'cmd' => ['Command', '<path d="M6 6V4.5A1.5 1.5 0 1 0 4.5 6H6Zm0 0v4m0-4h4m-4 4H4.5A1.5 1.5 0 1 0 6 11.5V10Zm0 0h4m0-4V4.5A1.5 1.5 0 1 1 11.5 6H10Zm0 0v4m0 0h1.5a1.5 1.5 0 1 1-1.5 1.5V10Z" />'],
        'shift' => ['Shift', '<path d="M8 2.5 3 8h2.75v5.5h4.5V8H13L8 2.5Z" />'],
        'option' => ['Option', '<path d="M2.5 4h3l5 8h3M9.5 4h4" />'],
        'enter' => ['Enter', '<path d="M13 3.5V8a2 2 0 0 1-2 2H3.5m0 0L6 7.5M3.5 10 6 12.5" />'],
        'backspace' => ['Backspace', '<path d="M5.5 3.5h7a1.5 1.5 0 0 1 1.5 1.5v6a1.5 1.5 0 0 1-1.5 1.5h-7L1.5 8l4-4.5ZM7.5 6l3 4m0-4-3 4" />'],
        'tab' => ['Tab', '<path d="M2.5 8h9m0 0L8.5 5m3 3-3 3M13.5 4v8" />'],
        'up' => ['Seta para cima', '<path d="M8 13V3m0 0L4 7m4-4 4 4" />'],
        'down' => ['Seta para baixo', '<path d="M8 3v10m0 0 4-4m-4 4L4 9" />'],
        'left' => ['Seta para a esquerda', '<path d="M13 8H3m0 0 4-4M3 8l4 4" />'],
        'right' => ['Seta para a direita', '<path d="M3 8h10m0 0-4-4m4 4-4 4" />'],
    ];
    $aliases = ['command' => 'cmd', 'meta' => 'cmd', 'return' => 'enter', 'arrowup' => 'up', 'arrowdown' => 'down', 'arrowleft' => 'left', 'arrowright' => 'right'];
    $words = ['alt' => 'Alt', 'ctrl' => 'Ctrl', 'esc' => 'Esc', 'space' => 'Espaço', 'del' => 'Del'];
    $list = $keys === null ? null : (is_array($keys) ? $keys : explode('+', $keys));
    $cap = [
        'inline-flex items-center justify-center font-mono font-medium leading-none whitespace-nowrap',
        'bg-base-100 text-base-content rounded-[min(var(--nui-radius-field),0.375rem)]',
        'border border-base-content/15 border-b-[3px] border-b-base-content/25 shadow-[0_1px_0_oklch(0%_0_0/0.06)]',
        match ($size) {
            'sm' => 'min-w-5 h-5 px-1 text-[0.6875rem] [&_svg]:size-3',
            'lg' => 'min-w-8 h-8 px-2 text-sm [&_svg]:size-4',
            default => 'min-w-6 h-6 px-1.5 text-xs [&_svg]:size-3.5',
        },
    ];
    $resolve = function (string $key) use ($icons, $aliases, $words): array {
        $name = strtolower(trim($key));
        $name = $aliases[$name] ?? $name;

        return isset($icons[$name]) ? ['icon' => $icons[$name]] : ['text' => $words[$name] ?? trim($key)];
    };
@endphp

{{-- Key cap. <x-nui::kbd>Esc</x-nui::kbd> or a combination: <x-nui::kbd keys="cmd+shift+k" />. --}}
@if ($list)
    <span {{ $attributes->class('inline-flex items-center gap-1 align-middle') }}>
        @foreach ($list as $key)
            @if (! $loop->first)
                <svg aria-hidden="true" class="size-2.5 text-base-content/40" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M8 3.5v9M3.5 8h9" /></svg>
            @endif
            @php($part = $resolve($key))
            <kbd @class($cap)>
                @isset($part['icon'])
                    <svg role="img" aria-label="{{ $part['icon'][0] }}" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">{!! $part['icon'][1] !!}</svg>
                @else
                    {{ $part['text'] }}
                @endisset
            </kbd>
        @endforeach
    </span>
@else
    <kbd {{ $attributes->class(array_merge($cap, ['align-middle'])) }}>{{ $slot }}</kbd>
@endif
