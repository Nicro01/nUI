@props(['label' => null])
@php($id = 'nui-menu-group-'.Str::random(8))
<div {{ $attributes->merge(['role' => 'group', 'aria-labelledby' => $label ? $id : null])->class('nui-menu-group') }}>
    @if ($label)<p id="{{ $id }}" class="px-3 pt-3 pb-1.5 text-xs font-semibold text-base-content/70">{{ $label }}</p>@endif
    {{ $slot }}
</div>
