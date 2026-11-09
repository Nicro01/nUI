<?php

namespace Nicro\Nui\Commands;

use Illuminate\Console\Command;

class InstallCommand extends Command
{
    protected $signature = 'nui:install';

    protected $description = 'Instala e publica os arquivos de configuração do nUI';

    public function handle(): int
    {
        $this->components->info('Publicando configuração do nUI...');

        $this->call('vendor:publish', ['--tag' => 'nui-config', '--force' => true]);

        $this->newLine();
        $this->components->info('nUI instalado com sucesso!');
        $this->newLine();
        $this->line('  <fg=yellow>1.</> Adicione a diretiva no <fg=green><head></> do seu layout:');
        $this->line('     <fg=gray><nui:script /></>');
        $this->newLine();
        $this->line('  <fg=yellow>2.</> No seu arquivo <fg=green>resources/css/app.css</>:');
        $this->line("     <fg=gray>@import '../../vendor/nicro/nui/resources/css/nicro.css';</>");
        $this->line("     <fg=gray>@source '../../vendor/nicro/nui/resources/views/**/*.blade.php';</>");
        $this->newLine();
        $this->line('  <fg=yellow>3.</> Compile os assets da sua aplicação:');
        $this->line('     <fg=gray>npm run build && php artisan optimize:clear</>');
        $this->newLine();

        return self::SUCCESS;
    }
}
