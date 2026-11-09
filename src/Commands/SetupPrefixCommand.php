<?php

namespace Nicro\Nui\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class SetupPrefixCommand extends Command
{
    protected $signature = 'nui:setup-prefix {prefix? : O prefixo desejado para os componentes (ex: nui, ui)}';

    protected $description = 'Configura o prefixo global dos componentes nUI';

    public function handle(): int
    {
        $prefix = $this->argument('prefix') ?? $this->ask('Qual prefixo você deseja utilizar para os componentes nUI?', 'nui');

        $configFile = config_path('nui.php');

        if (!File::exists($configFile)) {
            $this->call('vendor:publish', ['--tag' => 'nui-config']);
        }

        if (File::exists($configFile)) {
            $content = File::get($configFile);
            $content = preg_replace("/'prefix' => '.*'/", "'prefix' => '{$prefix}'", $content);
            File::put($configFile, $content);

            $this->components->info("Prefixo '{$prefix}' configurado com sucesso em config/nui.php!");
            $this->line("Agora você pode invocar seus componentes como <x-{$prefix}::button>.");
        } else {
            $this->warn('Não foi possível publicar config/nui.php automaticamente.');
        }

        return self::SUCCESS;
    }
}
