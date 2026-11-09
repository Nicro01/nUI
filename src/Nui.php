<?php

namespace Nicro\Nui;

use Illuminate\Support\HtmlString;

class Nui
{
    /**
     * Gera o script essencial do nUI para inicialização de tema e eliminação de FOUC.
     */
    public static function script(): HtmlString
    {
        $defaultTheme = config('nui.theme', 'light');

        $html = <<<HTML
<script>
    (function () {
        try {
            var theme = localStorage.getItem('nui-theme') || '{$defaultTheme}';
            document.documentElement.dataset.theme = theme;
        } catch (e) {}
    })();
</script>
HTML;

        return new HtmlString(trim($html));
    }

    /**
     * Gera styles adicionais se necessário.
     */
    public static function styles(): HtmlString
    {
        return new HtmlString('');
    }
}
