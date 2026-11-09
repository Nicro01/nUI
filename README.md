# nUI

Componentes Blade para a TALL Stack com temas plug-and-play via CSS variables.

## Arquitetura

```
packages/nui/
├── src/NuiServiceProvider.php      # registra <x-nui::*> (componentes anônimos)
├── resources/css/nicro.css             # tokens (@theme) + temas
└── resources/views/components/*.blade.php
```

Três camadas, cada uma só conhece a de baixo:

1. **Tokens** (`--nui-*`): cor, raio, borda, sombra. Um tema é só um bloco que sobrescreve esses valores.
2. **Utilitários Tailwind** (`bg-primary`, `rounded-nui`, `shadow-nui`…): gerados por `@theme inline` a partir dos tokens.
3. **Componentes Blade**: usam apenas os utilitários semânticos. Nenhuma cor fixa, então trocar o tema muda tudo.

Decisões:
- Base nativa (`<button>`, `<input>`, `<input type=checkbox role=switch>`, `<dialog>`): acessibilidade, formulários e `wire:model` funcionam sem JS.
- `$attributes->class()` em todos: qualquer classe passada se soma (e `class` do Tailwind pode sobrescrever pontualmente).
- Livewire 4 instalado (já traz o Alpine e injeta os scripts sozinho). Os atributos `wire:*` e `x-*` passam direto pro elemento nativo.

## Instalação

A instalação do nUI segue o mesmo padrão elegante do TallStackUI:

### 1. Requerer o pacote via Composer

```bash
composer require nicro/nui
```

*(O Laravel descobre o `NuiServiceProvider` automaticamente via Package Auto-Discovery).*

### 2. Adicionar o script no layout base

No `<head>` do seu layout principal (`resources/views/layouts/app.blade.php`), inclua `<nui:script />` antes de `@livewireStyles` e `@vite`:

```html
<head>
    <!-- ... -->
    <nui:script />
    @livewireStyles
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
```

### 3. Configurar o Tailwind CSS v4

No seu arquivo `resources/css/app.css`, importe o CSS do nUI e aponte o `@source` para que o Tailwind compile as classes dos componentes:

```css
@import "tailwindcss";
@import '../../vendor/nicro/nui/resources/css/nicro.css';

@source '../../vendor/nicro/nui/resources/views/**/*.blade.php';
@source '../views';
```

### 4. Compilar os assets

```bash
npm run build && php artisan optimize:clear
```

### Comandos úteis

- `php artisan nui:install` — Publica o arquivo de configuração e exibe o checklist de configuração.
- `php artisan nui:setup-prefix` — Permite alterar o prefixo global dos componentes (ex: `ui`, `nui`).
- `php artisan vendor:publish --tag=nui-config` — Publica o arquivo `config/nui.php`.

## Uso

```blade
<x-nui::button variant="secondary" size="sm">Salvar</x-nui::button>
<x-nui::button href="/docs">Link</x-nui::button>

<x-nui::input label="E-mail" name="email" wire:model.live="email" />  {{-- erro de validação aparece sozinho --}}

<x-nui::toggle label="Ativo" wire:model="active" />

<x-nui::card title="Título">
    Conteúdo
    <x-slot:actions><x-nui::button>OK</x-nui::button></x-slot:actions>
</x-nui::card>

<x-nui::button onclick="document.getElementById('m').showModal()">Abrir</x-nui::button>
<x-nui::modal id="m" title="Olá">...</x-nui::modal>
```

Props: `button` (`variant`: primary|secondary|danger|ghost, `size`: sm|md|lg, `href`), `input` (`label`, `hint`, `error`), `toggle` (`label`), `card` (`title`, slot `actions`), `modal` (`id`, `title`, slot `actions`).

## Menu

`<x-nui::menu label="Ações">` abre um painel flutuante. Com `variant="inline"`,
vira navegação lateral com links nativos. Ambos aceitam `menu.group`,
`menu.item`, `menu.submenu`, `menu.separator` e `menu.checkbox`.

```blade
<x-nui::menu label="Documento" id="menu-documento">
    <x-nui::menu.item label="Revisar" wire:click="revisar" shortcut="ctrl+r" />
    <x-nui::menu.submenu label="Guias">
        <x-nui::menu.item label="Instalação" href="/docs/instalacao" />
    </x-nui::menu.submenu>
    <x-nui::menu.separator />
    <x-nui::menu.checkbox label="Favoritos" wire:model="favoritos" />
</x-nui::menu>
```

`menu.item` aceita `href`, `active`, `disabled`, `danger`, `description`,
`shortcut`, `close` e os slots `icon`/`trailing`. `navigate` no Menu ativa
`wire:navigate` nos links. Os atalhos são visuais; a aplicação registra os
handlers. O painel usa Popover API e Alpine, incluído no Livewire 4.
Submenus expandem dentro do painel para funcionar também em telas estreitas.
Use setas, Home/End e busca por digitação; Esc retorna ao gatilho e Tab sai.
Em `inline`, os links também participam normalmente da sequência de Tab.

## Temas

Aplique com `data-theme` (`light` | `dark`) no `<html>` ou em qualquer elemento (vale para a subárvore):

```html
<html data-theme="dark">
<section data-theme="light">...</section>
```

Criar um tema = um bloco CSS no seu `app.css`, sem tocar nos componentes:

```css
[data-theme='brutal'] {
    --nui-primary: oklch(85% 0.2 100);
    --nui-primary-content: black;
    --nui-radius: 0;
    --nui-radius-field: 0;
    --nui-border-width: 3px;
    --nui-shadow: 4px 4px 0 black;
    --nui-shadow-inset: none;
}
```

Tokens disponíveis: `base-100/200/300/content`, `primary(-content)`, `secondary(-content)`, `danger(-content)`, `radius`, `radius-field`, `border-width`, `shadow`, `shadow-inset`, `shadow-control` (botões).

## Novo componente

1. Crie `resources/views/components/<nome>.blade.php` — já fica disponível como `<x-nui::nome>`.
2. Use só utilitários semânticos (`bg-base-100`, `rounded-nui`, `shadow-nui`…), nunca cores fixas.
3. Termine com `{{ $attributes->class(...) }}` no elemento raiz.
4. Adicione uma entrada em `components` e em `nav` de `config/docs.php` e um teste em `tests/Feature/NuiComponentsTest.php`.

## Documentação

`/` e `/docs/{página}`: documentação com uma página por guia e por componente (navegação em `config/docs.php`, páginas em `resources/views/docs`). Cada exemplo é uma string Blade renderizada ao vivo **e** exibida como código, então a documentação não fica desatualizada. O seletor Tema no topo troca o site inteiro (persiste em `localStorage`).

Próximos passos, quando fizerem falta:
- Página por componente com tabela de props (ler os `@props` de cada arquivo).
- Editor ao vivo de tokens (inputs que fazem `style.setProperty('--nui-*')`) e botão "copiar tema".
- Controles interativos de props (exige Livewire ou Alpine).
