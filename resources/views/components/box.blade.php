@props(['invalid' => false, 'label' => null, 'for' => null, 'labelStyle' => 'top', 'size' => 'md'])

@php
    $style = $label ? $labelStyle : 'top';
    // A real <fieldset>/<legend> lets the browser cut the border around the legend text, whatever the page colour.
    $tag = $style === 'legend' ? 'fieldset' : 'div';
    $sizeClasses = match ($size) {
        'sm' => 'min-h-8 px-2.5 gap-1.5 text-xs',
        'lg' => 'min-h-12 px-3.5 gap-2.5 text-base',
        default => 'min-h-10 px-3 gap-2',
    };
@endphp

{{--
    The visual "field" surface shared by input, password, number, textarea and the select/date triggers.
    It also draws the non-top label styles, so every control behaves the same way:
      inline   = label segment at the start, divided from the value
      floating = label rests inside and floats up on focus or once filled (text fields only)
      legend   = label set into the top border (native fieldset legend)
    Only the text control gets extra padding, so buttons inside (eye, steppers, chevrons) stay centred.
--}}
<{{ $tag }} {{ $attributes->class([
    'relative flex items-center m-0 min-w-0 bg-field text-base-content rounded-nui-field transition',
    $sizeClasses,
    'has-[:disabled]:opacity-60 has-[:disabled]:cursor-not-allowed',
    $invalid ? 'border-danger' : 'border-field-border',
    // Outlines and inset shadows ignore the legend notch, so the legend style shows focus on the (notched) border itself.
    $style === 'legend'
        ? 'pt-0 pb-1 border-[length:max(var(--nui-field-border-width),1.5px)] '.($invalid ? '' : 'focus-within:border-primary')
        : 'shadow-nui-inset border-[length:var(--nui-field-border-width)] focus-within:outline-2 focus-within:-outline-offset-1 '.($invalid ? 'focus-within:outline-danger' : 'focus-within:outline-primary'),
    'nui-floating min-h-14 [&>input]:pt-5 [&>input]:pb-1.5 [&>textarea]:pt-6' => $style === 'floating',
]) }}>
    @if ($style === 'legend')
        <legend class="ml-[-0.25rem] px-1 text-xs font-medium leading-4 text-base-content/70 transition-colors">
            <label for="{{ $for }}" class="cursor-pointer">{{ $label }}</label>
        </legend>
    @endif

    @if ($style === 'inline')
        <label for="{{ $for }}" @class([
            'shrink-0 self-stretch flex items-center border-r border-base-content/12 text-base-content/60 cursor-pointer select-none',
            $size === 'sm' ? 'pr-2 mr-0.5 text-xs font-medium' : 'pr-3 mr-1 text-sm',
        ])>{{ $label }}</label>
    @endif

    {{ $slot }}

    @if ($style === 'floating')
        <label for="{{ $for }}" class="nui-float-label">{{ $label }}</label>
    @endif
</{{ $tag }}>
