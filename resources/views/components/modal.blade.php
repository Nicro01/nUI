@props(['id', 'title' => null, 'description' => null, 'size' => 'md', 'mobile' => 'popup'])

{{-- Native <dialog>: focus trap, Esc and inert background come from the browser. Open with document.getElementById('{id}').showModal(). Clicking the backdrop closes it.
     mobile="sheet" turns it into a bottom sheet below 640px (popup elsewhere). --}}
<dialog id="{{ $id }}" data-lenis-prevent @if ($title) aria-labelledby="{{ $id }}-title" @endif @if ($description) aria-describedby="{{ $id }}-description" @endif
    onclick="if (event.target === this) this.close()"
    {{ $attributes->class([
        'm-auto w-[calc(100%-2rem)] p-0 bg-base-100 text-base-content rounded-nui shadow-nui border-[length:var(--nui-border-width)] border-base-300',
        'backdrop:bg-black/45 backdrop:backdrop-blur-[2px]',
        'open:animate-[nui-dialog-in_180ms_cubic-bezier(0.16,1,0.3,1)]',
        match ($size) {
            'sm' => 'max-w-sm',
            'lg' => 'max-w-2xl',
            default => 'max-w-lg',
        },
        $mobile === 'sheet' ? 'max-sm:mb-0 max-sm:mx-0 max-sm:mt-auto max-sm:w-full max-sm:max-w-none max-sm:max-h-[90dvh] max-sm:rounded-b-none max-sm:border-x-0 max-sm:border-b-0 max-sm:pb-[env(safe-area-inset-bottom)] max-sm:open:animate-[nui-sheet-up_340ms_cubic-bezier(0.16,1,0.3,1)]' : '',
    ]) }}>
    @if ($mobile === 'sheet')
        <span aria-hidden="true" class="sm:hidden block mx-auto mt-2.5 -mb-2 h-1 w-10 rounded-full bg-base-content/20"></span>
    @endif
    <div class="p-6">
        <div class="flex items-start gap-4">
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
        </div>
        <div class="mt-4 text-sm leading-relaxed">{{ $slot }}</div>
    </div>
    @isset($actions)
        <x-nui::form method="dialog" class="flex justify-end gap-2 px-6 py-4 bg-base-200/60 border-t border-base-content/[0.06]">
            <x-nui::button type="submit" variant="ghost">Cancelar</x-nui::button>
            {{ $actions }}
        </x-nui::form>
    @endisset
</dialog>
