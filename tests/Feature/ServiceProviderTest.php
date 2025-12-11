<?php

declare(strict_types=1);

use Eznix86\Version\Git;
use Eznix86\Version\Version;
use Eznix86\Version\VersionLoader;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Blade;

describe('VersionServiceProvider', function (): void {
    describe('container bindings', function (): void {
        it('registers Version as singleton', function (): void {
            $version1 = $this->app->make(Version::class);
            $version2 = $this->app->make(Version::class);

            expect($version1)->toBe($version2);
        });

        it('registers VersionLoader as singleton', function (): void {
            $loader1 = $this->app->make(VersionLoader::class);
            $loader2 = $this->app->make(VersionLoader::class);

            expect($loader1)->toBe($loader2);
        });

        it('registers Git as singleton', function (): void {
            $git1 = $this->app->make(Git::class);
            $git2 = $this->app->make(Git::class);

            expect($git1)->toBe($git2);
        });

        it('registers version alias', function (): void {
            $version = $this->app->make('version');

            expect($version)->toBeInstanceOf(Version::class);
        });
    });

    describe('configuration', function (): void {
        it('merges default config', function (): void {
            expect(config('version.git.enabled'))->not->toBeNull();
            expect(config('version.git.commit_message'))->not->toBeNull();
            expect(config('version.git.tag_format'))->not->toBeNull();
        });

        it('has expected default values in config file', function (): void {
            $configPath = __DIR__.'/../../config/version.php';
            $config = require $configPath;

            expect($config['prefix'])->toBe('v');
            expect($config['git']['enabled'])->toBe(true);
            expect($config['git']['commit_message'])->toBe('Bump version to {version}');
            expect($config['git']['tag_format'])->toBe('v{version}');
        });
    });

    describe('commands', function (): void {
        it('registers version:show command', function (): void {
            $commands = Artisan::all();

            expect($commands)->toHaveKey('version:show');
        });

        it('registers version:bump command', function (): void {
            $commands = Artisan::all();

            expect($commands)->toHaveKey('version:bump');
        });
    });

    describe('blade directives', function (): void {
        it('registers @version directive', function (): void {
            $directives = Blade::getCustomDirectives();

            expect($directives)->toHaveKey('version');
        });

        it('compiles @version directive correctly', function (): void {
            $compiled = Blade::compileString('@version');

            expect($compiled)->toContain("config('version.prefix')");
            expect($compiled)->toContain("app('version')->get()");
        });

        it('renders @version with prefix from config', function (): void {
            config(['version.prefix' => 'v']);
            $this->app->singleton(Version::class, fn (): Version => new Version('1.2.3'));

            $rendered = Blade::render('@version');

            expect($rendered)->toBe('v1.2.3');
        });

        it('renders @version with custom prefix', function (): void {
            config(['version.prefix' => 'version-']);
            $this->app->singleton(Version::class, fn (): Version => new Version('2.0.0'));

            $rendered = Blade::render('@version');

            expect($rendered)->toBe('version-2.0.0');
        });

        it('renders @version with empty prefix', function (): void {
            config(['version.prefix' => '']);
            $this->app->singleton(Version::class, fn (): Version => new Version('3.0.0'));

            $rendered = Blade::render('@version');

            expect($rendered)->toBe('3.0.0');
        });
    });

    describe('about command', function (): void {
        it('displays version with build metadata in about command', function (): void {
            $this->app->singleton(Version::class, fn (): Version => new Version('1.2.3+build.456'));

            $this->artisan('about')
                ->expectsOutputToContain('1.2.3+build.456')
                ->assertSuccessful();
        });
    });
});
