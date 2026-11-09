<?php

namespace Nicro\Nui\Mcp;

use Throwable;

/**
 * Model Context Protocol server for nUI (JSON-RPC 2.0, one message per line over stdio).
 *
 * It answers from the installed package itself: the component catalog shipped in resources/mcp/catalog.json
 * (props, examples, Alpine and Livewire integrations), the Blade sources (their @props) and the theme tokens
 * in resources/css/nicro.css. So an assistant always reads the API of the version the app actually uses.
 */
class Server
{
    public const VERSION = '0.1.0';

    private const PROTOCOL_VERSIONS = ['2025-06-18', '2025-03-26', '2024-11-05'];

    /** @var array<string, array<string, mixed>>|null */
    private ?array $components = null;

    public function __construct(private ?string $packagePath = null)
    {
        $this->packagePath ??= dirname(__DIR__, 2);
    }

    /**
     * Handle one decoded JSON-RPC message. Returns the response, or null for notifications.
     *
     * @param  array<string, mixed>  $message
     * @return array<string, mixed>|null
     */
    public function handle(array $message): ?array
    {
        $id = $message['id'] ?? null;
        $method = (string) ($message['method'] ?? '');
        $params = is_array($message['params'] ?? null) ? $message['params'] : [];

        if (! array_key_exists('id', $message)) {
            return null;
        }

        try {
            $result = match ($method) {
                'initialize' => $this->initialize($params),
                'ping' => (object) [],
                'tools/list' => ['tools' => $this->tools()],
                'tools/call' => $this->callTool((string) ($params['name'] ?? ''), is_array($params['arguments'] ?? null) ? $params['arguments'] : []),
                'prompts/list' => ['prompts' => $this->prompts()],
                'prompts/get' => $this->getPrompt((string) ($params['name'] ?? ''), is_array($params['arguments'] ?? null) ? $params['arguments'] : []),
                default => null,
            };
        } catch (InvalidParams $exception) {
            return $this->error($id, -32602, $exception->getMessage());
        } catch (Throwable $exception) {
            return $this->error($id, -32603, 'Erro interno do servidor nUI: '.$exception->getMessage());
        }

        if ($result === null) {
            return $this->error($id, -32601, "Método não suportado: {$method}");
        }

        return ['jsonrpc' => '2.0', 'id' => $id, 'result' => $result];
    }

    /**
     * Handle a raw line (as read from stdin) and return the line to write back, if any.
     */
    public function handleLine(string $line): ?string
    {
        // Some shells (PowerShell) prefix piped input with a UTF-8 BOM.
        $line = trim(preg_replace('/^\xEF\xBB\xBF/', '', $line) ?? $line);

        if ($line === '') {
            return null;
        }

        $message = json_decode($line, true);

        if (! is_array($message)) {
            return $this->encode($this->error(null, -32700, 'JSON inválido.'));
        }

        $response = $this->handle($message);

        return $response === null ? null : $this->encode($response);
    }

    /**
     * @param  array<string, mixed>  $params
     * @return array<string, mixed>
     */
    private function initialize(array $params): array
    {
        $requested = (string) ($params['protocolVersion'] ?? '');

        return [
            'protocolVersion' => in_array($requested, self::PROTOCOL_VERSIONS, true) ? $requested : self::PROTOCOL_VERSIONS[0],
            'capabilities' => ['tools' => (object) [], 'prompts' => (object) []],
            'serverInfo' => ['name' => 'nui', 'title' => 'nUI', 'version' => self::VERSION],
            'instructions' => 'nUI é uma biblioteca de componentes Blade (<x-nui::nome>) para Tailwind 4, Alpine e Livewire 4. '
                .'Antes de escrever uma interface, use search_components ou list_components para achar o componente e get_component '
                .'para ler props, exemplos e a integração com Alpine e Livewire. Componha a tela com os componentes existentes em vez de '
                .'criar markup próprio, e siga get_guidelines.',
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function tools(): array
    {
        $readOnly = ['readOnlyHint' => true, 'openWorldHint' => false];

        return [
            [
                'name' => 'list_components',
                'title' => 'Listar componentes',
                'description' => 'Lista os componentes do nUI com grupo e descrição curta. Filtre por grupo (ex.: Formulários, Gráficos).',
                'inputSchema' => ['type' => 'object', 'properties' => ['group' => ['type' => 'string', 'description' => 'Nome do grupo para filtrar.']]],
                'annotations' => $readOnly,
            ],
            [
                'name' => 'search_components',
                'title' => 'Buscar componentes',
                'description' => 'Busca componentes por texto em nome, descrição, props e exemplos (ex.: "tabela ordenável", "upload", "gráfico de linha").',
                'inputSchema' => ['type' => 'object', 'properties' => ['query' => ['type' => 'string']], 'required' => ['query']],
                'annotations' => $readOnly,
            ],
            [
                'name' => 'get_component',
                'title' => 'Ler componente',
                'description' => 'Documentação completa de um componente: uso, props, subcomponentes, exemplos Blade e integração com Alpine e Livewire.',
                'inputSchema' => ['type' => 'object', 'properties' => ['name' => ['type' => 'string', 'description' => 'Ex.: button, data-grid ou x-nui::area-chart.']], 'required' => ['name']],
                'annotations' => $readOnly,
            ],
            [
                'name' => 'get_theme_tokens',
                'title' => 'Tokens do tema',
                'description' => 'Variáveis CSS (--nui-*) do tema claro, escuro ou atlético/iron, para criar um tema próprio ou usar as cores certas.',
                'inputSchema' => ['type' => 'object', 'properties' => ['theme' => ['type' => 'string', 'enum' => ['light', 'dark', 'iron', 'iron-dark', 'fitness']]]],
                'annotations' => $readOnly,
            ],
            [
                'name' => 'get_guidelines',
                'title' => 'Diretrizes do nUI',
                'description' => 'Regras de uso e de design da biblioteca: composição, ícones, acessibilidade, movimento e integração.',
                'inputSchema' => ['type' => 'object', 'properties' => (object) []],
                'annotations' => $readOnly,
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $arguments
     * @return array<string, mixed>
     */
    private function callTool(string $name, array $arguments): array
    {
        return match ($name) {
            'list_components' => $this->text($this->listComponents(isset($arguments['group']) ? (string) $arguments['group'] : null)),
            'search_components' => $this->text($this->searchComponents((string) ($arguments['query'] ?? throw new InvalidParams('Informe "query".')))),
            'get_component' => $this->getComponent((string) ($arguments['name'] ?? throw new InvalidParams('Informe "name".'))),
            'get_theme_tokens' => $this->text($this->themeTokens((string) ($arguments['theme'] ?? 'light'))),
            'get_guidelines' => $this->text($this->guidelines()),
            default => throw new InvalidParams("Ferramenta desconhecida: {$name}"),
        };
    }

    private function listComponents(?string $group): string
    {
        $lines = [];

        foreach ($this->components() as $slug => $component) {
            if ($group !== null && mb_strtolower($component['group']) !== mb_strtolower($group)) {
                continue;
            }

            $lines[$component['group']][] = "- `<x-nui::{$slug}>` {$component['title']}: ".$this->firstSentence($component['lead'] ?? '');
        }

        if ($lines === []) {
            return $group === null ? 'Nenhum componente no catálogo.' : "Nenhum componente no grupo \"{$group}\". Grupos: ".implode(', ', $this->groups()).'.';
        }

        $output = '';
        foreach ($lines as $name => $items) {
            $output .= "## {$name}\n".implode("\n", $items)."\n\n";
        }

        return rtrim($output);
    }

    private function searchComponents(string $query): string
    {
        $stopwords = ['de', 'da', 'do', 'das', 'dos', 'com', 'para', 'por', 'e', 'a', 'o', 'as', 'os', 'um', 'uma', 'em', 'no', 'na', 'the', 'of', 'with'];
        $terms = array_values(array_filter(
            preg_split('/\s+/', $this->fold($query)) ?: [],
            fn (string $term) => $term !== '' && ! in_array($term, $stopwords, true),
        ));

        if ($terms === []) {
            throw new InvalidParams('A busca está vazia.');
        }

        $scores = [];
        foreach ($this->components() as $slug => $component) {
            $title = $this->fold($slug.' '.$component['title'].' '.$component['group']);
            $lead = $this->fold($component['lead'] ?? '');
            $rest = $this->fold(implode(' ', array_map(fn (array $prop) => $prop['name'].' '.$prop['values'], $component['props']))
                .' '.implode(' ', array_map(fn (array $example) => $example['title'].' '.($example['text'] ?? ''), $component['examples'])));

            $score = 0;
            foreach ($terms as $term) {
                $score += (str_contains($title, $term) ? 6 : 0) + (str_contains($lead, $term) ? 3 : 0) + (str_contains($rest, $term) ? 1 : 0);
            }

            if ($score > 0) {
                $scores[$slug] = $score;
            }
        }

        if ($scores === []) {
            return "Nada encontrado para \"{$query}\". Use list_components para ver todos.";
        }

        arsort($scores);
        $components = $this->components();

        return implode("\n", array_map(
            fn (string $slug) => "- `<x-nui::{$slug}>` {$components[$slug]['title']} ({$components[$slug]['group']}): ".$this->firstSentence($components[$slug]['lead'] ?? ''),
            array_slice(array_keys($scores), 0, 8),
        ));
    }

    /**
     * @return array<string, mixed>
     */
    private function getComponent(string $name): array
    {
        $slug = trim(preg_replace('/^(<?x-nui::|x-nui::|nui::)/', '', trim($name)) ?? '', "<>/ \t");
        $component = $this->components()[$slug] ?? null;
        $source = $this->sourcePath($slug);

        if ($component === null && $source === null) {
            $suggestions = $this->searchComponents($slug);

            return $this->text("Componente \"{$name}\" não encontrado.\n\nTalvez:\n{$suggestions}", true);
        }

        $out = '# '.($component['title'] ?? $slug)." (`<x-nui::{$slug}>`)\n\n";
        if ($component) {
            $out .= "Grupo: {$component['group']}\n\n".($component['lead'] ? $component['lead']."\n\n" : '');
        }

        if ($component && $component['props'] !== []) {
            $out .= "## Props\n\n| Prop | Valores | Padrão |\n|---|---|---|\n";
            foreach ($component['props'] as $prop) {
                $out .= '| '.$this->cell($prop['name']).' | '.$this->cell($prop['values']).' | '.$this->cell($prop['default'])." |\n";
            }
            $out .= "\n";
        }

        if ($source) {
            $declared = $this->declaredProps($source);
            if ($declared !== '') {
                $out .= "## @props no código-fonte\n\n```php\n{$declared}\n```\n\n";
            }
        }

        $children = $this->children($slug);
        if ($children !== []) {
            $out .= "## Subcomponentes\n\n".implode("\n", array_map(fn (string $child) => "- `<x-nui::{$slug}.{$child}>`", $children))."\n\n";
        }

        foreach ($component['examples'] ?? [] as $example) {
            $out .= "## Exemplo: {$example['title']}\n\n".($example['text'] ? $example['text']."\n\n" : '')."```blade\n".trim($example['code'])."\n```\n\n";
        }

        $integration = $component['integration'] ?? null;
        if (is_array($integration)) {
            $out .= "## Com Alpine\n\n".($integration['alpine_text'] ?? '')."\n\n```blade\n".trim($integration['alpine'] ?? '')."\n```\n\n";
            $out .= "## Com Livewire\n\n".($integration['livewire_text'] ?? '')."\n\nPropriedades e ações do componente Livewire:\n\n```php\n"
                .trim($integration['php'] ?? '')."\n```\n\nView:\n\n```blade\n".trim($integration['blade'] ?? '')."\n```\n";
        }

        return $this->text(rtrim($out));
    }

    private function themeTokens(string $theme): string
    {
        $valid = ['light', 'dark', 'iron', 'iron-dark', 'fitness', 'fitness-dark'];
        $theme = in_array($theme, $valid, true) ? $theme : 'light';
        $css = (string) @file_get_contents($this->packagePath.'/resources/css/nicro.css');

        if (! preg_match("/\\[data-theme='{$theme}'[^\\]]*\\]\\s*\\{(.*?)\\n\\}/s", $css, $block)) {
            return "Tokens do tema {$theme} não encontrados em nicro.css.";
        }

        preg_match_all('/(--nui-[a-z0-9-]+)\s*:\s*([^;]+);/', $block[1], $matches, PREG_SET_ORDER);

        return "Tokens do tema {$theme} (aplique com data-theme=\"{$theme}\" no <html>; um tema próprio redefine as mesmas variáveis em [data-theme='nome']):\n\n"
            .implode("\n", array_map(fn (array $match) => "{$match[1]}: ".trim($match[2]).';', $matches));
    }

    private function guidelines(): string
    {
        return <<<'TEXT'
# Diretrizes do nUI

- Componha com o que existe: use <x-nui::…> antes de escrever markup próprio. Telas inteiras devem ser só componentes distribuídos em uma grade.
- Componentes envolvem elementos nativos (<button>, <input>, <dialog>, popover); mantenha os atributos nativos e os eventos.
- Cores e raios vêm apenas dos tokens --nui-* e das classes base-100/200/300, base-content, primary, secondary, success, warning, danger. Não use cores fixas.
- Gráficos usam as tintas --nui-chart-1..3 na ordem fixa, nunca recicladas; cores de status ficam reservadas para estados.
- Ícones são sempre SVG; nunca use caracteres ou emoji como ícone. Ícones que carregam sentido precisam de nome acessível.
- Identidade: borda de tecla (borda inferior mais grossa) em cards, tags e teclas; hover em cards é opcional (prop hover).
- Mudanças de estado (abrir, expandir) usam a animação morph da biblioteca e respeitam prefers-reduced-motion.
- Alpine para comportamento no navegador; Livewire para estado, validação e ações no servidor. Não use x-model e wire:model no mesmo campo; use $wire para compartilhar estado.
- Acessibilidade: rótulos em todos os campos, foco visível, teclado completo; botões só com ícone levam aria-label.
- Textos da interface em português (pt-BR), com números no formato brasileiro.
TEXT;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function prompts(): array
    {
        return [[
            'name' => 'build_screen',
            'title' => 'Montar tela com nUI',
            'description' => 'Monta uma tela Blade ou Livewire usando apenas componentes do nUI.',
            'arguments' => [['name' => 'description', 'description' => 'O que a tela deve fazer.', 'required' => true]],
        ]];
    }

    /**
     * @param  array<string, mixed>  $arguments
     * @return array<string, mixed>
     */
    private function getPrompt(string $name, array $arguments): array
    {
        if ($name !== 'build_screen') {
            throw new InvalidParams("Prompt desconhecido: {$name}");
        }

        $description = trim((string) ($arguments['description'] ?? '')) ?: throw new InvalidParams('Informe "description".');

        return [
            'description' => 'Montar tela com nUI',
            'messages' => [[
                'role' => 'user',
                'content' => ['type' => 'text', 'text' => "Monte esta tela com nUI: {$description}\n\n"
                    ."1. Use search_components para achar os componentes de cada parte.\n"
                    ."2. Leia cada um com get_component antes de usar (props, exemplos, integração).\n"
                    ."3. Siga get_guidelines. Componha só com <x-nui::…> em uma grade; não crie widgets próprios.\n"
                    .'4. Use Livewire para dados e ações, Alpine para comportamento local.'],
            ]],
        ];
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function components(): array
    {
        if ($this->components === null) {
            $catalog = json_decode((string) @file_get_contents($this->packagePath.'/resources/mcp/catalog.json'), true);
            $this->components = is_array($catalog['components'] ?? null) ? $catalog['components'] : [];
        }

        return $this->components;
    }

    /**
     * @return list<string>
     */
    private function groups(): array
    {
        return array_values(array_unique(array_column($this->components(), 'group')));
    }

    private function sourcePath(string $slug): ?string
    {
        if ($slug === '' || ! preg_match('/^[a-z0-9-]+$/', $slug)) {
            return null;
        }

        $path = $this->packagePath."/resources/views/components/{$slug}.blade.php";

        return is_file($path) ? $path : null;
    }

    private function declaredProps(string $path): string
    {
        $source = (string) file_get_contents($path);

        return preg_match('/@props\((\[.*?\])\)/s', $source, $match) ? trim($match[1]) : '';
    }

    /**
     * @return list<string>
     */
    private function children(string $slug): array
    {
        $directory = $this->packagePath."/resources/views/components/{$slug}";

        if ($slug === '' || ! is_dir($directory)) {
            return [];
        }

        $children = array_map(fn (string $file) => basename($file, '.blade.php'), glob($directory.'/*.blade.php') ?: []);
        sort($children);

        return $children;
    }

    private function firstSentence(string $text): string
    {
        return preg_match('/^.*?[.!?](\s|$)/u', $text, $match) ? trim($match[0]) : $text;
    }

    private function fold(string $text): string
    {
        $text = mb_strtolower($text);

        return strtr($text, ['á' => 'a', 'à' => 'a', 'â' => 'a', 'ã' => 'a', 'é' => 'e', 'ê' => 'e', 'í' => 'i', 'ó' => 'o', 'ô' => 'o', 'õ' => 'o', 'ú' => 'u', 'ç' => 'c']);
    }

    private function cell(string $text): string
    {
        return str_replace(['|', "\n"], ['\\|', ' '], $text);
    }

    /**
     * @return array<string, mixed>
     */
    private function text(string $text, bool $isError = false): array
    {
        return ['content' => [['type' => 'text', 'text' => $text]], 'isError' => $isError];
    }

    /**
     * @return array<string, mixed>
     */
    private function error(mixed $id, int $code, string $message): array
    {
        return ['jsonrpc' => '2.0', 'id' => $id, 'error' => ['code' => $code, 'message' => $message]];
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function encode(array $payload): string
    {
        return json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE);
    }
}
