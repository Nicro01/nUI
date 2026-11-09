@props(['images' => [], 'label' => 'Galeria de imagens', 'thumbnails' => true, 'loop' => true])
@php
    $id = $attributes->get('id') ?? 'nui-lightbox-'.Str::random(8);
    $images = array_values(array_filter($images, fn ($image) => is_array($image) && filled($image['src'] ?? null)));
    $images = array_map(fn ($image) => array_merge(['alt' => '', 'caption' => '', 'thumbnail' => $image['src']], $image), $images);
@endphp

<div {{ $attributes->merge(['id' => $id])->class('nui-lightbox w-full min-w-0') }}
    x-data="{
        images: @js($images), index: 0, failed: false, loading: true, loop: @js((bool) $loop), opener: null,
        get current() { return this.images[this.index] || {} },
        show(index, opener) {
            if (!this.images.length) return;
            this.opener = opener; this.index = Math.max(0, Math.min(this.images.length - 1, Number(index) || 0));
            this.loading = true; this.failed = false;
            this.$refs.dialog.showModal();
            this.$dispatch('lightbox-opened', { index: this.index, image: this.current });
        },
        go(offset) {
            let next = this.index + offset;
            if (this.loop) next = (next + this.images.length) % this.images.length;
            if (next < 0 || next >= this.images.length || next === this.index) return;
            this.loading = true; this.failed = false; this.index = next;
            this.$dispatch('lightbox-changed', { index: this.index, image: this.current });
        },
        closed() { this.opener?.focus({ preventScroll: true }); this.$dispatch('lightbox-closed'); }
    }"
    x-on:lightbox-open="show($event.detail?.index || 0, document.activeElement)">
    @if (count($images))
        @if ($thumbnails)
            <div class="grid grid-cols-2 gap-3 sm:grid-cols-3">
                @foreach ($images as $index => $image)
                    <x-nui::button variant="ghost" class="!h-auto !min-w-0 !overflow-hidden !p-0" aria-haspopup="dialog"
                        :aria-controls="$id.'-dialog'" :aria-label="'Ampliar imagem '.($index + 1).($image['alt'] ? ': '.$image['alt'] : '')"
                        x-on:click="show({{ $index }}, $el)">
                        <img src="{{ $image['thumbnail'] }}" alt="{{ $image['alt'] }}" loading="lazy" class="aspect-[4/3] w-full object-cover" />
                    </x-nui::button>
                @endforeach
            </div>
        @else
            <x-nui::button variant="secondary" aria-haspopup="dialog" :aria-controls="$id.'-dialog'" x-on:click="show(0, $el)">{{ $slot->isNotEmpty() ? $slot : $label }}</x-nui::button>
        @endif
        <x-nui::modal :id="$id.'-dialog'" :title="$label" size="lg" x-ref="dialog" class="nui-lightbox-dialog"
            x-on:close="closed()"
            x-on:keydown.arrow-right.prevent="go(getComputedStyle($el).direction === 'rtl' ? -1 : 1)"
            x-on:keydown.arrow-left.prevent="go(getComputedStyle($el).direction === 'rtl' ? 1 : -1)">
            <figure class="m-0 min-w-0">
                <div class="relative grid min-h-48 place-items-center overflow-hidden rounded-nui bg-base-200">
                    <span x-show="loading && !failed" class="absolute" role="status"><x-nui::loading label="Carregando imagem" /></span>
                    <template x-for="(image, imageIndex) in images" x-bind:key="imageIndex">
                        <template x-if="imageIndex === index">
                            <img x-bind:src="image.src" x-bind:alt="image.alt" x-show="!failed" class="max-h-[60dvh] w-full object-contain"
                                x-on:load="loading = false" x-on:error="loading = false; failed = true" />
                        </template>
                    </template>
                    <p x-show="failed" x-cloak role="status" class="p-6 text-center text-sm">Não foi possível carregar esta imagem. Tente outra imagem ou feche a galeria.</p>
                </div>
                <figcaption x-show="current.caption" x-text="current.caption" class="mt-4 text-sm text-base-content/75 [overflow-wrap:anywhere]"></figcaption>
            </figure>
            <div class="mt-5 flex items-center justify-between gap-3">
                <x-nui::button variant="ghost" size="sm" x-on:click="go(-1)" x-bind:disabled="images.length < 2 || (!loop && index === 0)" aria-label="Imagem anterior">Anterior</x-nui::button>
                <span role="status" aria-live="polite" aria-atomic="true" class="text-xs tabular-nums text-base-content/70" x-text="(index + 1) + ' de ' + images.length"></span>
                <x-nui::button variant="ghost" size="sm" x-on:click="go(1)" x-bind:disabled="images.length < 2 || (!loop && index === images.length - 1)" aria-label="Próxima imagem">Próxima</x-nui::button>
            </div>
        </x-nui::modal>
    @else
        <p role="status" class="rounded-nui bg-base-200 p-6 text-center text-sm text-base-content/70">Nenhuma imagem disponível.</p>
    @endif
</div>
