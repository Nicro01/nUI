@props([
    'label' => null,
    'hint' => null,
    'error' => null,
    'variant' => 'dropzone',
    'maxSize' => null,
    'maxFiles' => null,
    'preview' => true,
])

@php
    $name = $attributes->get('name');
    $id = $attributes->get('id') ?? ($name ? str_replace(['[', ']'], '', $name) : null) ?? $attributes->whereStartsWith('wire:model')->first() ?? 'nui-'.Str::random(8);
    $error ??= $name ? ($errors ?? null)?->first(str_replace('[]', '', $name)) : null;
    $imageOnly = in_array($variant, ['avatar', 'gallery'], true);
    $accept = $attributes->get('accept') ?? ($imageOnly ? 'image/*' : null);
    $multiple = $variant === 'avatar' ? false : ($variant === 'gallery' || (bool) $attributes->get('multiple'));
    $described = $error ? $id.'-error' : ($hint ? $id.'-hint' : null);
    $iconButton = 'grid place-items-center rounded-full bg-base-100/95 text-base-content shadow-nui-control hover:text-danger focus-visible:outline-2 focus-visible:outline-primary';
@endphp

{{--
    File picker on a real <input type="file"> (forms and Livewire uploads keep working).
    variant: dropzone | compact | avatar | gallery · max-size (MB) · max-files · preview (image thumbnails)
    With multiple, new picks and drops are added to the list instead of replacing it.
--}}
<x-nui::field :$label :$error :for="$id" :class="$attributes->get('class')">
<div class="contents"
    x-data="{
        files: [],
        store: [],
        rejected: [],
        dragging: false,
        syncing: false,
        uploading: false,
        progress: 0,
        multiple: @js($multiple),
        maxSize: @js($maxSize ? (float) $maxSize : null),
        maxFiles: @js($maxFiles ? (int) $maxFiles : null),
        preview: @js((bool) $preview),
        commit(list, replace = false) {
            this.rejected = [];
            let next = (this.multiple && ! replace) ? [...this.store] : [];
            for (const file of list) {
                if (this.maxSize && file.size > this.maxSize * 1048576) { this.rejected.push(`${file.name}: maior que ${this.maxSize} MB`); continue; }
                next.push(file);
            }
            if (! this.multiple) next = next.slice(-1);
            if (this.maxFiles && next.length > this.maxFiles) { this.rejected.push(`Máximo de ${this.maxFiles} arquivos: ${next.length - this.maxFiles} ignorado(s)`); next = next.slice(0, this.maxFiles); }
            this.write(next);
        },
        write(next) {
            this.files.forEach((file) => file.url && URL.revokeObjectURL(file.url));
            this.store = next;
            const transfer = new DataTransfer();
            next.forEach((file) => transfer.items.add(file));
            this.syncing = true;
            this.$refs.input.files = transfer.files;
            this.$refs.input.dispatchEvent(new Event('change', { bubbles: true }));
            this.syncing = false;
            this.files = next.map((file) => ({ name: file.name, size: file.size, ext: (file.name.split('.').pop() || '').toUpperCase().slice(0, 4), image: file.type.startsWith('image/'), url: this.preview && file.type.startsWith('image/') ? URL.createObjectURL(file) : null }));
        },
        picked() { if (! this.syncing) this.commit([...this.$refs.input.files]) },
        remove(index) { this.write(this.store.filter((file, position) => position !== index)); this.rejected = [] },
        size(bytes) { return bytes < 1024 ? bytes + ' B' : bytes < 1048576 ? Math.round(bytes / 1024) + ' KB' : (bytes / 1048576).toFixed(1).replace('.', ',') + ' MB' },
        tone(ext) {
            if (['PDF'].includes(ext)) return 'text-danger';
            if (['DOC', 'DOCX', 'TXT', 'MD', 'RTF'].includes(ext)) return 'text-primary';
            if (['XLS', 'XLSX', 'CSV'].includes(ext)) return 'text-[oklch(50%_0.13_150)]';
            if (['ZIP', 'RAR', '7Z', 'GZ'].includes(ext)) return 'text-[oklch(60%_0.14_70)]';
            return 'text-base-content/60';
        },
    }"
    x-on:dragover.prevent="dragging = true" x-on:dragleave.prevent="dragging = false" x-on:drop.prevent="dragging = false; commit($event.dataTransfer.files)">

    <input id="{{ $id }}" type="file" x-ref="input" class="sr-only" x-on:change="picked()"
        x-on:livewire-upload-start="uploading = true; progress = 0" x-on:livewire-upload-progress="progress = $event.detail.progress"
        x-on:livewire-upload-finish="uploading = false" x-on:livewire-upload-error="uploading = false; rejected.push('Falha no envio. Tente de novo.')"
        @if ($accept) accept="{{ $accept }}" @endif @if ($multiple) multiple @endif
        @if ($error) aria-invalid="true" @endif @if ($described) aria-describedby="{{ $described }}" @endif
        {{ $attributes->except(['id', 'class', 'type', 'accept', 'multiple']) }}>

    @if ($variant === 'compact')
        {{-- One line, like an input --}}
        <x-nui::box :invalid="(bool) $error" class="pl-1.5" x-bind:class="dragging && '!outline-2 !outline-primary'">
            <label for="{{ $id }}" class="inline-flex items-center gap-1.5 h-7 px-2.5 shrink-0 rounded-[calc(var(--nui-radius-field)-3px)] bg-base-100 text-sm font-medium shadow-nui-control border border-base-content/10 cursor-pointer hover:bg-base-200">
                <svg aria-hidden="true" class="size-3.5" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M8 10.5V2.5M5 5.5l3-3 3 3M2.5 10.5v1.5a1.5 1.5 0 0 0 1.5 1.5h8a1.5 1.5 0 0 0 1.5-1.5v-1.5" /></svg>
                Escolher {{ $multiple ? 'arquivos' : 'arquivo' }}
            </label>
            <span class="flex-1 min-w-0 truncate text-sm" x-bind:class="! files.length && 'text-base-content/45'"
                x-text="files.length === 0 ? 'Nenhum arquivo selecionado' : files.length === 1 ? files[0].name : `${files.length} arquivos selecionados`">Nenhum arquivo selecionado</span>
            <x-nui::button variant="ghost" size="sm" type="button" x-show="files.length" x-cloak x-on:click="write([])" aria-label="Limpar seleção" class="!p-0 grid place-items-center !size-7 shrink-0 rounded-full text-base-content/55 hover:text-danger hover:bg-danger/10 focus-visible:outline-2 focus-visible:outline-primary">
                <svg aria-hidden="true" class="size-3.5" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"><path d="m4 4 8 8M12 4l-8 8" /></svg>
            </x-nui::button>
        </x-nui::box>
        @if ($hint)
            <p id="{{ $id }}-hint" class="text-sm text-base-content/60">{{ $hint }}</p>
        @endif
    @elseif ($variant === 'avatar')
        {{-- Round profile photo --}}
        <div class="flex items-center gap-5">
            <div class="relative shrink-0">
                <label for="{{ $id }}" class="group relative grid place-items-center size-24 rounded-full overflow-hidden cursor-pointer bg-field shadow-nui-inset border-[length:max(var(--nui-field-border-width),1.5px)] focus-within:outline-2 focus-within:outline-offset-2 focus-within:outline-primary {{ $error ? 'border-danger' : 'border-field-border' }}"
                    x-bind:class="dragging && 'outline-2 outline-offset-2 outline-primary'">
                    <template x-if="files[0]?.url"><img x-bind:src="files[0].url" alt="" class="nui-enter size-full object-cover"></template>
                    <svg x-show="! files[0]?.url" aria-hidden="true" class="size-10 text-base-content/30" viewBox="0 0 24 24" fill="currentColor"><path d="M12 12a5 5 0 1 0 0-10 5 5 0 0 0 0 10Zm0 2c-4.4 0-8 2.2-8 5v1a1 1 0 0 0 1 1h14a1 1 0 0 0 1-1v-1c0-2.8-3.6-5-8-5Z" /></svg>
                    <span aria-hidden="true" class="absolute inset-0 grid place-items-center bg-black/45 text-white opacity-0 transition group-hover:opacity-100">
                        <svg class="size-6" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M2.5 7A1.5 1.5 0 0 1 4 5.5h2l1.5-2h5l1.5 2h2A1.5 1.5 0 0 1 17.5 7v8a1.5 1.5 0 0 1-1.5 1.5H4A1.5 1.5 0 0 1 2.5 15V7Z" /><circle cx="10" cy="10.5" r="3" /></svg>
                    </span>
                    <span class="sr-only">Escolher foto</span>
                </label>
                <x-nui::button variant="ghost" size="sm" type="button" x-show="files.length" x-cloak x-on:click="remove(0)" aria-label="Remover foto" class="!p-0 {{ $iconButton }} !absolute -bottom-0.5 -right-0.5 !size-7">
                    <svg aria-hidden="true" class="size-3.5" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M3 4.5h10M6.5 4.5V3h3v1.5M4.5 4.5l.5 8.5h6l.5-8.5" /></svg>
                </x-nui::button>
            </div>
            <div class="flex flex-col gap-2 items-start">
                <label for="{{ $id }}" class="inline-flex items-center gap-1.5 h-8 px-3 rounded-nui bg-secondary text-secondary-content text-sm font-medium shadow-nui-control cursor-pointer hover:brightness-105">
                    <svg aria-hidden="true" class="size-3.5" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M8 10.5V2.5M5 5.5l3-3 3 3M2.5 10.5v1.5a1.5 1.5 0 0 0 1.5 1.5h8a1.5 1.5 0 0 0 1.5-1.5v-1.5" /></svg>
                    <span x-text="files.length ? 'Trocar foto' : 'Enviar foto'">Enviar foto</span>
                </label>
                @if ($hint)
                    <p id="{{ $id }}-hint" class="text-sm text-base-content/60">{{ $hint }}</p>
                @endif
            </div>
        </div>
    @elseif ($variant === 'gallery')
        {{-- Grid of image thumbnails with an add tile --}}
        <div class="grid grid-cols-[repeat(auto-fill,minmax(6.5rem,1fr))] gap-3">
            <template x-for="(file, index) in files" x-bind:key="file.name + index">
                <figure class="nui-enter group relative aspect-square overflow-hidden rounded-nui-field bg-base-200 shadow-nui border-[length:var(--nui-border-width)] border-base-300">
                    <img x-show="file.url" x-bind:src="file.url" x-bind:alt="file.name" class="size-full object-cover">
                    <figcaption class="absolute inset-x-0 bottom-0 px-2 py-1 bg-gradient-to-t from-black/60 to-transparent text-[0.6875rem] text-white truncate" x-text="file.name"></figcaption>
                    <x-nui::button variant="ghost" size="sm" type="button" x-on:click="remove(index)" x-bind:aria-label="'Remover ' + file.name" class="!p-0 {{ $iconButton }} !absolute top-1.5 right-1.5 !size-7 opacity-0 group-hover:opacity-100 focus-visible:opacity-100 transition">
                        <svg aria-hidden="true" class="size-3.5" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"><path d="m4 4 8 8M12 4l-8 8" /></svg>
                    </x-nui::button>
                </figure>
            </template>
            <label for="{{ $id }}" x-show="! maxFiles || files.length < maxFiles"
                x-bind:class="dragging && 'outline-2 -outline-offset-1 outline-primary'"
                class="flex flex-col items-center justify-center gap-1.5 aspect-square rounded-nui-field cursor-pointer bg-field shadow-nui-inset border-dashed border-[length:max(var(--nui-field-border-width),1.5px)] text-base-content/60 hover:text-primary hover:bg-base-content/[0.03] focus-within:outline-2 focus-within:outline-primary {{ $error ? 'border-danger' : 'border-field-border' }}">
                <svg aria-hidden="true" class="size-6" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"><path d="M10 4.5v11M4.5 10h11" /></svg>
                <span class="text-xs font-medium">Adicionar</span>
            </label>
        </div>
        @if ($hint)
            <p id="{{ $id }}-hint" class="text-sm text-base-content/60">{{ $hint }}</p>
        @endif
    @else
        {{-- Dropzone --}}
        <label for="{{ $id }}"
            x-bind:class="dragging && 'outline-2 -outline-offset-1 outline-primary bg-primary/[0.04]'"
            @class([
                'group flex flex-col items-center gap-2 px-6 py-8 text-center cursor-pointer transition',
                'bg-field rounded-nui-field shadow-nui-inset border-dashed border-[length:max(var(--nui-field-border-width),1.5px)]',
                'hover:bg-base-content/[0.03] focus-within:outline-2 focus-within:-outline-offset-1 focus-within:outline-primary',
                $error ? 'border-danger' : 'border-field-border',
            ])>
            <span class="grid place-items-center size-11 rounded-full bg-base-100 text-primary shadow-nui-control transition group-hover:-translate-y-0.5" x-bind:class="dragging && '-translate-y-1 scale-110'">
                <svg aria-hidden="true" class="size-5" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M10 13V3.5M6 7l4-4 4 4M3.5 13v2A1.5 1.5 0 0 0 5 16.5h10a1.5 1.5 0 0 0 1.5-1.5v-2" /></svg>
            </span>
            <span class="text-sm text-base-content" x-text="dragging ? 'Solte para enviar' : ''"></span>
            <span x-show="! dragging" class="text-sm text-base-content"><span class="font-semibold text-primary">Clique para enviar</span> ou arraste aqui</span>
            @if ($hint)
                <span id="{{ $id }}-hint" class="text-xs text-base-content/60">{{ $hint }}</span>
            @endif
        </label>
    @endif

    {{-- Upload progress (Livewire) --}}
    <div x-show="uploading" x-cloak class="h-1.5 rounded-full bg-base-content/10 overflow-hidden" role="progressbar" aria-label="Enviando" aria-valuemin="0" aria-valuemax="100" x-bind:aria-valuenow="progress">
        <div class="h-full rounded-full bg-primary transition-[width] duration-200" x-bind:style="`width: ${progress}%`"></div>
    </div>

    {{-- Rejected files --}}
    <ul x-show="rejected.length" x-cloak role="alert" class="flex flex-col gap-1 text-sm text-danger">
        <template x-for="message in rejected" x-bind:key="message">
            <li class="nui-enter flex items-center gap-1.5">
                <svg aria-hidden="true" class="size-4 shrink-0" viewBox="0 0 16 16" fill="currentColor"><path fill-rule="evenodd" d="M8 15A7 7 0 1 0 8 1a7 7 0 0 0 0 14Zm-.75-10.25a.75.75 0 0 1 1.5 0v3.5a.75.75 0 0 1-1.5 0v-3.5ZM8 11.75a1 1 0 1 0 0-2 1 1 0 0 0 0 2Z" clip-rule="evenodd" /></svg>
                <span x-text="message"></span>
            </li>
        </template>
    </ul>

    @if (in_array($variant, ['dropzone', 'compact'], true))
        {{-- File list --}}
        <ul x-show="files.length && (@js($variant === 'dropzone') || files.length > 1)" x-cloak class="flex flex-col gap-2" aria-live="polite">
            <template x-for="(file, index) in files" x-bind:key="file.name + index">
                <li class="nui-enter flex items-center gap-3 pl-2 pr-1.5 py-1.5 bg-base-100 rounded-nui-field shadow-nui border-[length:var(--nui-border-width)] border-base-300 text-sm">
                    <template x-if="file.url"><img x-bind:src="file.url" alt="" class="size-9 shrink-0 rounded-[calc(var(--nui-radius-field)-3px)] object-cover"></template>
                    <span x-show="! file.url" class="relative grid place-items-center size-9 shrink-0" x-bind:class="tone(file.ext)">
                        <svg aria-hidden="true" class="size-9" viewBox="0 0 32 32" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linejoin="round"><path d="M19 3.5H9a2 2 0 0 0-2 2v21a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V9.5l-6-6Z" fill="currentColor" fill-opacity="0.08" /><path d="M19 3.5v6h6" /></svg>
                        <span class="absolute bottom-1.5 text-[0.5625rem] font-bold tracking-tight" x-text="file.ext"></span>
                    </span>
                    <span class="flex min-w-0 flex-1 flex-col">
                        <span class="truncate font-medium" x-text="file.name"></span>
                        <span class="tabular-nums text-xs text-base-content/55" x-text="size(file.size)"></span>
                    </span>
                    <x-nui::button variant="ghost" size="sm" type="button" x-on:click="remove(index)" x-bind:aria-label="'Remover ' + file.name"
                        class="!p-0 grid place-items-center !size-7 shrink-0 rounded-[calc(var(--nui-radius-field)-4px)] text-base-content/55 hover:text-danger hover:bg-danger/10 focus-visible:outline-2 focus-visible:outline-primary">
                        <svg aria-hidden="true" class="size-3.5" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"><path d="m4 4 8 8M12 4l-8 8" /></svg>
                    </x-nui::button>
                </li>
            </template>
        </ul>
    @endif
</div>
</x-nui::field>


