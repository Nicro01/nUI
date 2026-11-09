<?php

namespace Nicro\Nui;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;
use Nicro\Nui\Commands\InstallCommand;
use Nicro\Nui\Commands\SetupPrefixCommand;

class NuiServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/nui.php', 'nui');
    }

    /**
     * Bootstrap any package services.
     */
    public function boot(): void
    {
        $this->registerCommands();
        $this->registerPublishing();
        $this->registerBladeDirectives();
        $this->registerComponents();
    }

    /**
     * Register package artisan commands.
     */
    protected function registerCommands(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                SetupPrefixCommand::class,
                InstallCommand::class,
            ]);
        }
    }

    /**
     * Register vendor publishable assets.
     */
    protected function registerPublishing(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/nui.php' => config_path('nui.php'),
            ], 'nui-config');

            $this->publishes([
                __DIR__.'/../resources/views' => resource_path('views/vendor/nui'),
            ], 'nui-views');

            $this->publishes([
                __DIR__.'/../resources/css' => resource_path('css/vendor/nui'),
            ], 'nui-styles');
        }
    }

    /**
     * Register Blade directives and custom tags (<nui:script />).
     */
    protected function registerBladeDirectives(): void
    {
        Blade::directive('nuiScript', function () {
            return "<?php echo \Nicro\Nui\Nui::script(); ?>";
        });

        Blade::directive('nuiStyles', function () {
            return "<?php echo \Nicro\Nui\Nui::styles(); ?>";
        });

        Blade::precompiler(function (string $string): string {
            $string = preg_replace('/<\s*nui:script\s*\/?>/i', '@nuiScript', $string);
            return preg_replace('/<\s*nui:styles?\s*\/?>/i', '@nuiStyles', $string);
        });
    }

    /**
     * Register anonymous Blade components.
     */
    protected function registerComponents(): void
    {
        $prefix = config('nui.prefix', 'nui');
        $path = __DIR__.'/../resources/views/components';

        // Always register standard 'nui' namespace: <x-nui::...>
        Blade::anonymousComponentPath($path, 'nui');

        // If a custom prefix is configured (e.g. 'ui'), register it as well: <x-ui::...>
        if (!empty($prefix) && $prefix !== 'nui') {
            Blade::anonymousComponentPath($path, $prefix);
        }
    }
}
