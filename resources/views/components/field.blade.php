@props(['label' => null, 'hint' => null, 'error' => null, 'for' => null])

{{-- Shared wrapper for every form control: label on top, error or hint below. --}}
<div {{ $attributes->class('flex flex-col gap-1.5') }}>
    @if ($label)
        <label for="{{ $for }}" class="text-sm font-medium text-base-content">{{ $label }}</label>
    @endif
    {{ $slot }}
    @if ($error)
        <p id="{{ $for }}-error" class="flex items-center gap-1.5 text-sm text-danger">
            <svg aria-hidden="true" class="size-4 shrink-0" viewBox="0 0 16 16" fill="currentColor"><path fill-rule="evenodd" d="M8 15A7 7 0 1 0 8 1a7 7 0 0 0 0 14Zm-.75-10.25a.75.75 0 0 1 1.5 0v3.5a.75.75 0 0 1-1.5 0v-3.5ZM8 11.75a1 1 0 1 0 0-2 1 1 0 0 0 0 2Z" clip-rule="evenodd" /></svg>
            {{ $error }}
        </p>
    @elseif ($hint)
        <p id="{{ $for }}-hint" class="text-sm text-base-content/60">{{ $hint }}</p>
    @endif
</div>
