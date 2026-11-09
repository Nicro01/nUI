@props([
    'label' => null,
    'description' => null,
    'href' => null,
    'active' => false,
    'disabled' => false,
])

@aware(['navigate' => false])

@php
    $interactive = $href !== null || $attributes->whereStartsWith(['wire:click', 'x-on:click', '@click'])->isNotEmpty();
    $rowClass = 'flex min-h-14 min-w-0 flex-1 items-center gap-3 px-4 py-4';
@endphp

<li {{ $attributes->whereDoesntStartWith(['wire:click', 'x-on:click', '@click'])->class('flex min-w-0 items-center') }}>
    @if ($interactive)
        <x-nui::button :href="$disabled ? null : $href" :variant="$active ? 'secondary' : 'ghost'" :$disabled
            {{ $attributes->whereStartsWith(['wire:click', 'x-on:click', '@click'])->merge([
                'data-nui-list-row' => true,
                'wire:navigate' => $navigate && $href && ! $disabled ? true : null,
                'aria-current' => $active ? ($href ? 'page' : 'true') : null,
            ])->class($rowClass.' !h-auto !justify-start !whitespace-normal !rounded-none !border-0 !shadow-none text-left') }}>
            @isset($leading)<span class="flex shrink-0 items-center">{{ $leading }}</span>@endisset
            <span class="flex min-w-0 flex-1 flex-col gap-1">
                @if ($label !== null)<span class="text-sm font-medium [overflow-wrap:anywhere]">{{ $label }}</span>@endif
                @if ($description)<span class="text-xs font-normal leading-5 text-current/70 [overflow-wrap:anywhere]">{{ $description }}</span>@endif
                @if ($slot->isNotEmpty())<span class="text-sm">{{ $slot }}</span>@endif
            </span>
        </x-nui::button>
    @else
        <div data-nui-list-row @if ($active) aria-current="true" @endif @if ($disabled) aria-disabled="true" @endif
            @class([$rowClass, 'opacity-50' => $disabled, 'bg-primary/5' => $active])>
            @isset($leading)<span class="flex shrink-0 items-center">{{ $leading }}</span>@endisset
            <div class="min-w-0 flex-1">
                @if ($label !== null)<p class="text-sm font-medium [overflow-wrap:anywhere]">{{ $label }}</p>@endif
                @if ($description)<p @class(['text-xs leading-5 text-base-content/70 [overflow-wrap:anywhere]', 'mt-1' => $label !== null])>{{ $description }}</p>@endif
                @if ($slot->isNotEmpty())<div @class(['text-sm', 'mt-2' => $label !== null || $description])>{{ $slot }}</div>@endif
            </div>
        </div>
    @endif
    @isset($trailing)
        <div class="flex min-w-0 max-w-[45%] shrink-0 items-center gap-2 pr-4 text-sm text-base-content/70">{{ $trailing }}</div>
    @endisset
</li>

