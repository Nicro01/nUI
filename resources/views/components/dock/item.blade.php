@props(['label', 'href' => null, 'active' => false, 'badge' => null, 'badgeLabel' => null, 'disabled' => false])
@aware(['orientation' => 'horizontal', 'navigate' => false, 'as' => 'toolbar'])

@php
    $tag = $href && ! $disabled ? 'a' : 'button';
    $hasBadge = $badge !== null && $badge !== '';
    $badgeText = is_numeric($badge) && $badge > 99 ? '99+' : $badge;
    $nav = $as === 'nav';
@endphp

{{--
    One sort in the dock. The icon goes in the slot (decorative SVG); label names the action and shows as the tooltip.
    badge-label is the spoken meaning of the badge ("4 não lidas"); without it the count is read in parentheses.
    In a toolbar the active item is the server-rendered Tab stop, so it survives Livewire re-renders.
--}}
@if ($nav)<li class="contents">@endif
<{{ $tag }}
    {{ $attributes->merge([
        'href' => $tag === 'a' ? $href : null,
        'type' => $tag === 'button' ? 'button' : null,
        'tabindex' => $nav ? null : ($active && ! $disabled ? 0 : -1),
        'aria-label' => $label.($hasBadge ? ($badgeLabel ? ', '.$badgeLabel : ' ('.$badge.')') : ''),
        'aria-current' => $active ? ($href ? 'page' : 'true') : null,
        'aria-disabled' => $disabled ? 'true' : null,
        'disabled' => $tag === 'button' && $disabled ? true : null,
        'wire:navigate' => $navigate && $tag === 'a' ? true : null,
        'data-nui-dock-item' => true,
    ])->class(['nui-dock-item', 'nui-dock-active' => $active]) }}>
    <span aria-hidden="true" class="nui-dock-icon">{{ $slot }}</span>
    @if ($hasBadge)
        <span aria-hidden="true" class="nui-dock-badge">{{ $badgeText }}</span>
    @endif
    <span aria-hidden="true" class="nui-dock-tip">{{ $label }}</span>
</{{ $tag }}>
@if ($nav)</li>@endif
