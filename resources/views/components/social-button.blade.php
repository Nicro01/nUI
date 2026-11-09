@props([
    'provider' => null,
    'label' => null,
    'variant' => 'default',
    'size' => 'md',
    'iconOnly' => false,
    'href' => null,
    'loading' => false,
])

@php
    /*
     * Each provider: display name, brand fill (variant="brand") and its mark.
     * Monochrome marks use currentColor so they follow the text colour on the neutral surface.
     */
    $providers = [
        'google' => [
            'name' => 'Google',
            'brand' => '!bg-white !text-[#1f1f1f] !border-[#747775]',
            'icon' => '<path fill="#4285F4" d="M23.49 12.27c0-.79-.07-1.54-.19-2.27H12v4.51h6.47c-.29 1.48-1.14 2.73-2.4 3.58v3h3.86c2.26-2.09 3.56-5.17 3.56-8.82z"/><path fill="#34A853" d="M12 24c3.24 0 5.95-1.08 7.93-2.91l-3.86-3c-1.08.72-2.45 1.16-4.07 1.16-3.13 0-5.78-2.11-6.73-4.96H1.29v3.09C3.26 21.3 7.31 24 12 24z"/><path fill="#FBBC05" d="M5.27 14.29c-.25-.72-.38-1.49-.38-2.29s.14-1.57.38-2.29V6.62H1.29C.47 8.24 0 10.06 0 12s.47 3.76 1.29 5.38l3.98-3.09z"/><path fill="#EA4335" d="M12 4.75c1.77 0 3.35.61 4.6 1.8l3.42-3.42C17.95 1.19 15.24 0 12 0 7.31 0 3.26 2.7 1.29 6.62l3.98 3.09c.95-2.85 3.6-4.96 6.73-4.96z"/>',
        ],
        'github' => [
            'name' => 'GitHub',
            'brand' => '!bg-[#181717] !text-white !border-white/10',
            'icon' => '<path fill="currentColor" d="M12 .297c-6.63 0-12 5.373-12 12 0 5.303 3.438 9.8 8.205 11.385.6.113.82-.258.82-.577 0-.285-.01-1.04-.015-2.04-3.338.724-4.042-1.61-4.042-1.61C4.422 18.07 3.633 17.7 3.633 17.7c-1.087-.744.084-.729.084-.729 1.205.084 1.838 1.236 1.838 1.236 1.07 1.835 2.809 1.305 3.495.998.108-.776.417-1.305.76-1.605-2.665-.3-5.466-1.332-5.466-5.93 0-1.31.465-2.38 1.235-3.22-.135-.303-.54-1.523.105-3.176 0 0 1.005-.322 3.3 1.23.96-.267 1.98-.399 3-.405 1.02.006 2.04.138 3 .405 2.28-1.552 3.285-1.23 3.285-1.23.645 1.653.24 2.873.12 3.176.765.84 1.23 1.91 1.23 3.22 0 4.61-2.805 5.625-5.475 5.92.42.36.81 1.096.81 2.22 0 1.606-.015 2.896-.015 3.286 0 .315.21.69.825.57C20.565 22.092 24 17.592 24 12.297c0-6.627-5.373-12-12-12"/>',
        ],
        'apple' => [
            'name' => 'Apple',
            'brand' => '!bg-black !text-white !border-white/10',
            'icon' => '<path fill="currentColor" d="M12.152 6.896c-.948 0-2.415-1.078-3.96-1.04-2.04.027-3.91 1.183-4.961 3.014-2.117 3.675-.546 9.103 1.519 12.09 1.013 1.454 2.208 3.09 3.792 3.039 1.52-.065 2.09-.987 3.935-.987 1.831 0 2.35.987 3.96.948 1.637-.026 2.676-1.48 3.676-2.948 1.156-1.688 1.636-3.325 1.662-3.415-.039-.013-3.182-1.221-3.22-4.857-.026-3.04 2.48-4.494 2.597-4.559-1.429-2.09-3.623-2.324-4.39-2.376-2-.156-3.675 1.09-4.61 1.09zM15.53 3.83c.843-1.012 1.4-2.427 1.245-3.83-1.207.052-2.662.805-3.532 1.818-.78.896-1.454 2.338-1.273 3.714 1.338.104 2.715-.688 3.559-1.701"/>',
        ],
        'microsoft' => [
            'name' => 'Microsoft',
            'brand' => '!bg-[#2f2f2f] !text-white !border-white/10',
            'icon' => '<path fill="#F25022" d="M1 1h10.5v10.5H1z"/><path fill="#7FBA00" d="M12.5 1H23v10.5H12.5z"/><path fill="#00A4EF" d="M1 12.5h10.5V23H1z"/><path fill="#FFB900" d="M12.5 12.5H23V23H12.5z"/>',
        ],
        'facebook' => [
            'name' => 'Facebook',
            'brand' => '!bg-[#0866FF] !text-white !border-black/10',
            'icon' => '<path fill="currentColor" class="text-[#0866FF] [[data-variant=brand]_&]:text-white" d="M9.101 23.691v-7.98H6.627v-3.667h2.474v-1.58c0-4.085 1.848-5.978 5.858-5.978.401 0 .955.042 1.468.103a8.68 8.68 0 0 1 1.141.195v3.325a8.623 8.623 0 0 0-.653-.036 26.805 26.805 0 0 0-.733-.009c-.707 0-1.259.096-1.675.309a1.686 1.686 0 0 0-.679.622c-.258.42-.374.995-.374 1.752v1.297h3.919l-.386 2.103-.287 1.564h-3.246v8.245C19.396 23.238 24 18.179 24 12.044c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.628 3.874 10.35 9.101 11.647Z"/>',
        ],
        'x' => [
            'name' => 'X',
            'brand' => '!bg-black !text-white !border-white/10',
            'icon' => '<path fill="currentColor" d="M18.901 1.153h3.68l-8.04 9.19L24 22.846h-7.406l-5.8-7.584-6.638 7.584H.474l8.6-9.83L0 1.154h7.594l5.243 6.932ZM17.61 20.644h2.039L6.486 3.24H4.298Z"/>',
        ],
        'linkedin' => [
            'name' => 'LinkedIn',
            'brand' => '!bg-[#0A66C2] !text-white !border-black/10',
            'icon' => '<path fill="currentColor" class="text-[#0A66C2] [[data-variant=brand]_&]:text-white" d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433c-1.144 0-2.063-.926-2.063-2.065 0-1.138.92-2.063 2.063-2.063 1.14 0 2.064.925 2.064 2.063 0 1.139-.925 2.065-2.064 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/>',
        ],
    ];

    $known = $providers[$provider] ?? null;
    if (! $known && $label === null) {
        throw new InvalidArgumentException('Social button: provider desconhecido "'.$provider.'". Use '.implode(', ', array_keys($providers)).' ou informe label e o slot icon.');
    }
    $name = $known['name'] ?? (string) $provider;
    $text = $label ?? 'Continuar com '.$name;
    $brand = $variant === 'brand' && $known;
    $socialClasses = [
        'shadow-nui-control',
        $brand ? $known['brand'] : '!bg-base-100 !text-base-content !border-base-300 hover:!bg-base-200',
        $iconOnly ? '!px-0 aspect-square' : '',
        match ($size) {
            'sm' => '[&_svg]:!size-4',
            'lg' => '[&_svg]:!size-5.5',
            default => '[&_svg]:!size-[1.125rem]',
        },
    ];
@endphp

{{--
    Sign-in button for an OAuth provider: google, github, apple, microsoft, facebook, x, linkedin.
    Default is the nUI neutral key-cap with the provider's own mark; variant="brand" uses the provider's colours.
    icon-only keeps the label as the accessible name. Slot replaces the text; slot "icon" replaces the mark.
--}}
<x-nui::button variant="ghost" :$size :$href :$loading
    data-nui-social="{{ $provider }}" data-variant="{{ $brand ? 'brand' : 'default' }}"
    :aria-label="$iconOnly ? $text : null" :title="$iconOnly ? $text : null"
    {{ $attributes->class($socialClasses) }}>
    @if (! $loading)
        @if (isset($icon))
            {{ $icon }}
        @elseif ($known)
            <svg aria-hidden="true" viewBox="0 0 24 24">{!! $known['icon'] !!}</svg>
        @endif
    @endif
    @unless ($iconOnly)
        <span>{{ $slot->isNotEmpty() ? $slot : $text }}</span>
    @endunless
</x-nui::button>
