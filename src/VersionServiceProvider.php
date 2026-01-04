<?php

declare(strict_types=1);

namespace Eznix86\Version;

use Eznix86\Version\Commands\VersionBumpCommand;
use Eznix86\Version\Commands\VersionSetCommand;
use Eznix86\Version\Commands\VersionShowCommand;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Foundation\Console\AboutCommand;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;

class VersionServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    #[\Override]
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/version.php',
            'version'
        );

        $this->app->singleton(VersionLoader::class);

        $this->app->singleton(Version::class, fn (Application $app): Version => $app->make(VersionLoader::class)->load());

        $this->app->alias(Version::class, 'version');

        $this->app->singleton(Git::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->registerBladeDirectives();

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/version.php' => $this->app->configPath('version.php'),
            ], 'version-config');

            $this->publishes([
                __DIR__.'/../stubs/version.json' => $this->app->basePath('version.json'),
            ], 'version-json');

            $this->registerCommands();
            $this->registerAboutCommand();
        }
    }

    /**
     * Register the package's Artisan commands.
     */
    protected function registerCommands(): void
    {
        $this->commands([
            VersionShowCommand::class,
            VersionBumpCommand::class,
            VersionSetCommand::class,
        ]);
    }

    /**
     * Register Blade directives.
     */
    protected function registerBladeDirectives(): void
    {
        Blade::directive('version', fn (): string => "<?php echo config('version.prefix') . app('version')->get(); ?>");
    }

    /**
     * Register the package's about command information.
     */
    protected function registerAboutCommand(): void
    {
        if (class_exists(AboutCommand::class)) {
            AboutCommand::add('Application', fn (): array => [
                'Version' => (string) $this->app->make(Version::class)->get(),
            ]);
        }
    }
}
