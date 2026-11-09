@props(['id', 'title' => null, 'description' => null, 'size' => 'md', 'side' => 'right'])

{{-- Side panel on a native <dialog>: focus trap, Esc and inert background from the browser. Open with document.getElementById('{id}').showModal(). Clicking the backdrop closes it. --}}
<dialog id="{{ $id }}" data-lenis-prevent @if ($title) aria-labelledby="{{ $id }}-title" @endif @if ($description) aria-describedby="{{ $id }}-description" @endif
    onclick="if (event.target === this) this.close()"
    {{ $attributes->class([
        'fixed inset-y-0 m-0 p-0 h-dvh max-h-dvh w-full bg-base-100 text-base-content shadow-nui overflow-hidden',
        'backdrop:bg-black/45 backdrop:backdrop-blur-[2px]',
        'open:flex open:flex-col',
        $side === 'left'
            ? 'left-0 right-auto mr-auto border-r-[length:var(--nui-border-width)] border-base-300 open:animate-[nui-drawer-left_320ms_cubic-bezier(0.16,1,0.3,1)]'
            : 'right-0 left-auto ml-auto border-l-[length:var(--nui-border-width)] border-base-300 open:animate-[nui-drawer-right_320ms_cubic-bezier(0.16,1,0.3,1)]',
        match ($size) {
            'sm' => 'max-w-sm',
            'lg' => 'max-w-3xl',
            'full' => 'max-w-none',
            default => 'max-w-lg',
        },
        $size === 'full' ? '' : ($side === 'left' ? 'rounded-r-nui' : 'rounded-l-nui'),
    ]) }}>
    <header class="flex items-start gap-4 px-6 py-5 border-b border-base-content/[0.08]">
        <div class="flex-1 min-w-0">
            @if ($title)
                <h2 id="{{ $id }}-title" class="text-lg font-semibold leading-snug">{{ $title }}</h2>
            @endif
            @if ($description)
                <p id="{{ $id }}-description" class="mt-1 text-sm text-base-content/65">{{ $description }}</p>
            @endif
        </div>
        <x-nui::form method="dialog">
            <x-nui::button variant="ghost" size="sm" type="submit" aria-label="Fechar" class="!p-0 -mr-2 -mt-1 grid place-items-center !size-8 rounded-[calc(var(--nui-radius)-4px)] text-base-content/55 hover:text-base-content hover:bg-base-content/[0.06] focus-visible:outline-2 focus-visible:outline-primary">
                <svg aria-hidden="true" class="size-4" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"><path d="m4 4 8 8M12 4l-8 8" /></svg>
            </x-nui::button>
        </x-nui::form>
    </header>

    <div data-lenis-prevent class="flex-1 overflow-y-auto px-6 py-5 text-sm leading-relaxed">{{ $slot }}</div>

    @isset($actions)
        <x-nui::form method="dialog" class="flex justify-end gap-2 px-6 py-4 bg-base-200/60 border-t border-base-content/[0.08]">
            <x-nui::button type="submit" variant="ghost">Cancelar</x-nui::button>
            {{ $actions }}
        </x-nui::form>
    @endisset
</dialog>
