@props(['label'])
@aware(['variant' => 'dropdown'])
<div role="none" class="nui-menu-check cursor-pointer select-none"
    x-on:click="if (!$event.target.closest('label') && !$event.target.closest('input')) { $el.querySelector('input')?.click(); }">
    <x-nui::checkbox :$label {{ $attributes->merge([
        'role' => $variant === 'inline' ? null : 'menuitemcheckbox',
        'tabindex' => $variant === 'inline' ? null : -1,
        'data-nui-menu-item' => true,
        'data-label' => $label,
    ]) }} />
</div>
