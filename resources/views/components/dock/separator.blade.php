@aware(['orientation' => 'horizontal', 'as' => 'toolbar'])

{{-- Divider between groups of sorts, like a lead spacer in the stick. In a nav list it is a decorative list item. --}}
@if ($as === 'nav')
    <li aria-hidden="true" {{ $attributes->class('nui-dock-separator') }}></li>
@else
    <span role="separator" aria-orientation="{{ $orientation === 'vertical' ? 'horizontal' : 'vertical' }}" {{ $attributes->class('nui-dock-separator') }}></span>
@endif
