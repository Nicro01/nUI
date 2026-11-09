@props([
    'value' => '',
    'size' => 192,
    'level' => 'M',
    'label' => 'QR Code',
    'caption' => null,
    'download' => false,
    'filename' => 'qr-code.svg',
    'downloadLabel' => 'Baixar SVG',
    'empty' => 'Informe um conteúdo para gerar o QR Code.',
])

@php
    $pixels = max(64, min(1024, (int) $size));
    $source = null;
    $error = null;
    if ((string) $value !== '') {
        try {
            $source = 'data:image/svg+xml;base64,'.base64_encode(\Nicro\Nui\Support\QrCode::svg((string) $value, $pixels, (string) $level));
        } catch (\InvalidArgumentException | \BaconQrCode\Exception\WriterException $exception) {
            $error = 'Conteúdo muito longo para este QR Code. Reduza o texto ou o nível de correção.';
        }
    }
    $safeFilename = preg_replace('/[^\pL\pN._-]/u', '-', (string) $filename) ?: 'qr-code.svg';
    if (!str_ends_with(strtolower($safeFilename), '.svg')) {
        $safeFilename .= '.svg';
    }
@endphp

<figure {{ $attributes->class('inline-flex max-w-full flex-col items-center gap-3 align-top text-base-content') }}>
    @if ($source)
        <img src="{{ $source }}" alt="{{ $label }}" width="{{ $pixels }}" height="{{ $pixels }}"
            class="block h-auto max-w-full" />
    @else
        <div class="grid max-w-full place-items-center rounded-nui border border-dashed border-base-content/20 bg-base-200 p-4 text-center text-sm text-base-content/70"
            style="width: {{ $pixels }}px; min-height: {{ $pixels }}px" role="{{ $error ? 'alert' : 'status' }}">
            {{ $error ?? $empty }}
        </div>
    @endif
    @if ($caption || $slot->isNotEmpty())
        <figcaption class="max-w-64 break-words text-center text-sm leading-relaxed text-base-content/70">
            {{ $caption }}{{ $slot }}
        </figcaption>
    @endif
    @if ($download)
        <x-nui::button :href="$source" :disabled="!$source" :download="$safeFilename" size="sm" variant="secondary">
            {{ $downloadLabel }}
        </x-nui::button>
    @endif
</figure>
