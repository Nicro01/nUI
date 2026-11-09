@props(['label' => null, 'href' => null, 'active' => false, 'disabled' => false, 'danger' => false, 'description' => null, 'shortcut' => null, 'close' => true])
@aware(['variant' => 'dropdown', 'navigate' => false])

<x-nui::button :href="$disabled ? null : $href" variant="ghost" :$disabled
    {{ $attributes->merge([
        'role' => $variant === 'inline' ? null : 'menuitem',
        'tabindex' => $variant === 'inline' ? null : -1,
        'data-nui-menu-item' => true,
        'data-nui-menu-action' => $close ? true : null,
        'data-label' => $label,
        'aria-current' => $active ? ($href ? 'page' : 'true') : null,
        'wire:navigate' => $navigate && $href && !$disabled ? true : null,
    ])->class(['nui-menu-item', 'nui-menu-danger' => $danger, 'nui-menu-active' => $active]) }}>
    @isset($icon)<span aria-hidden="true" class="flex size-5 shrink-0 items-center justify-center">{{ $icon }}</span>@endisset
    <span class="flex min-w-0 flex-1 flex-col gap-0.5">
        <span data-nui-menu-label>{{ $label ?? $slot }}</span>
        @if ($description)<span class="text-xs font-normal text-current/70">{{ $description }}</span>@endif
    </span>
    @if ($shortcut)<x-nui::kbd :keys="$shortcut" size="sm" class="shrink-0" />@endif
    @isset($trailing)<span class="shrink-0">{{ $trailing }}</span>@endisset
</x-nui::button>
