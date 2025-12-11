<?php

declare(strict_types=1);

use Eznix86\Version\Version;

describe('version:show command', function (): void {
    beforeEach(function (): void {
        $this->tempPath = sys_get_temp_dir().'/version_test_'.uniqid().'.json';
        file_put_contents($this->tempPath, json_encode(['version' => '2.3.4']));

        $this->app->singleton(Version::class, fn (): Version => new Version('2.3.4'));
    });

    afterEach(function (): void {
        if (file_exists($this->tempPath)) {
            unlink($this->tempPath);
        }
    });

    it('displays the current version', function (): void {
        $this->artisan('version:show')
            ->expectsOutputToContain('Current version: 2.3.4')
            ->assertSuccessful();
    });

    it('displays version breakdown table', function (): void {
        $this->artisan('version:show')
            ->expectsTable(
                ['Component', 'Value'],
                [
                    ['Major', '2'],
                    ['Minor', '3'],
                    ['Patch', '4'],
                    ['Pre-release', '-'],
                    ['Build', '-'],
                ]
            )
            ->assertSuccessful();
    });

    it('displays pre-release in table when present', function (): void {
        $this->app->singleton(Version::class, fn (): Version => new Version('1.0.0-beta.2'));

        $this->artisan('version:show')
            ->expectsTable(
                ['Component', 'Value'],
                [
                    ['Major', '1'],
                    ['Minor', '0'],
                    ['Patch', '0'],
                    ['Pre-release', 'beta.2'],
                    ['Build', '-'],
                ]
            )
            ->assertSuccessful();
    });

    it('displays build metadata in table when present', function (): void {
        $this->app->singleton(Version::class, fn (): Version => new Version('1.0.0+build.123'));

        $this->artisan('version:show')
            ->expectsOutputToContain('Current version: 1.0.0+build.123')
            ->expectsTable(
                ['Component', 'Value'],
                [
                    ['Major', '1'],
                    ['Minor', '0'],
                    ['Patch', '0'],
                    ['Pre-release', '-'],
                    ['Build', 'build.123'],
                ]
            )
            ->assertSuccessful();
    });

    it('displays both pre-release and build in table', function (): void {
        $this->app->singleton(Version::class, fn (): Version => new Version('2.0.0-rc.1+20251211'));

        $this->artisan('version:show')
            ->expectsOutputToContain('Current version: 2.0.0-rc.1+20251211')
            ->expectsTable(
                ['Component', 'Value'],
                [
                    ['Major', '2'],
                    ['Minor', '0'],
                    ['Patch', '0'],
                    ['Pre-release', 'rc.1'],
                    ['Build', '20251211'],
                ]
            )
            ->assertSuccessful();
    });
});
