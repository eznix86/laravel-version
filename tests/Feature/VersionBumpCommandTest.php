<?php

declare(strict_types=1);

use Eznix86\Version\Version;
use Eznix86\Version\VersionLoader;
use Illuminate\Process\FakeProcessResult;
use Illuminate\Support\Facades\Process;

beforeEach(function (): void {
    $this->tempPath = sys_get_temp_dir().'/version_test_'.uniqid().'.json';
    file_put_contents($this->tempPath, json_encode(['version' => '1.0.0']));

    $this->app->singleton(VersionLoader::class, fn (): VersionLoader => new VersionLoader($this->tempPath));

    $this->app->singleton(Version::class, fn ($app) => $app->make(VersionLoader::class)->load());

    config(['version.git.enabled' => false]);
});

afterEach(function (): void {
    if (file_exists($this->tempPath)) {
        unlink($this->tempPath);
    }
});

describe('version:bump command', function (): void {
    describe('version bumping', function (): void {
        it('bumps major version', function (): void {
            $this->artisan('version:bump', ['type' => 'major'])
                ->expectsOutputToContain('Version bumped: 1.0.0 → 2.0.0')
                ->assertSuccessful();

            $data = json_decode(file_get_contents($this->tempPath), true);
            expect($data['version'])->toBe('2.0.0');
        });

        it('bumps minor version', function (): void {
            $this->artisan('version:bump', ['type' => 'minor'])
                ->expectsOutputToContain('Version bumped: 1.0.0 → 1.1.0')
                ->assertSuccessful();

            $data = json_decode(file_get_contents($this->tempPath), true);
            expect($data['version'])->toBe('1.1.0');
        });

        it('bumps patch version', function (): void {
            $this->artisan('version:bump', ['type' => 'patch'])
                ->expectsOutputToContain('Version bumped: 1.0.0 → 1.0.1')
                ->assertSuccessful();

            $data = json_decode(file_get_contents($this->tempPath), true);
            expect($data['version'])->toBe('1.0.1');
        });

        it('rejects invalid version type', function (): void {
            $this->artisan('version:bump', ['type' => 'invalid'])
                ->expectsOutputToContain('Invalid version type: invalid')
                ->assertFailed();
        });

        it('prompts for type when not provided', function (): void {
            $this->artisan('version:bump')
                ->expectsChoice('What type of version bump?', 'patch', [
                    'major' => 'Major (breaking changes)',
                    'minor' => 'Minor (new features)',
                    'patch' => 'Patch (bug fixes)',
                    'alpha' => 'Alpha (pre-release)',
                    'beta' => 'Beta (pre-release)',
                    'rc' => 'Release Candidate (pre-release)',
                ])
                ->expectsOutputToContain('Version bumped: 1.0.0 → 1.0.1')
                ->assertSuccessful();

            $data = json_decode(file_get_contents($this->tempPath), true);
            expect($data['version'])->toBe('1.0.1');
        });

        it('prompts and allows selecting major', function (): void {
            $this->artisan('version:bump')
                ->expectsChoice('What type of version bump?', 'major', [
                    'major' => 'Major (breaking changes)',
                    'minor' => 'Minor (new features)',
                    'patch' => 'Patch (bug fixes)',
                    'alpha' => 'Alpha (pre-release)',
                    'beta' => 'Beta (pre-release)',
                    'rc' => 'Release Candidate (pre-release)',
                ])
                ->expectsOutputToContain('Version bumped: 1.0.0 → 2.0.0')
                ->assertSuccessful();
        });
    });

    describe('pre-release bumping', function (): void {
        it('sets alpha release on stable version', function (): void {
            $this->artisan('version:bump', ['type' => 'alpha'])
                ->expectsOutputToContain('Version bumped: 1.0.0 → 1.0.0-alpha.1')
                ->assertSuccessful();
        });

        it('increments existing alpha release', function (): void {
            file_put_contents($this->tempPath, json_encode(['version' => '1.0.0-alpha.1']));
            $this->refreshApplication();
            $this->app->singleton(VersionLoader::class, fn (): VersionLoader => new VersionLoader($this->tempPath));
            $this->app->singleton(Version::class, fn ($app) => $app->make(VersionLoader::class)->load());

            $this->artisan('version:bump', ['type' => 'alpha'])
                ->expectsOutputToContain('Version bumped: 1.0.0-alpha.1 → 1.0.0-alpha.2')
                ->assertSuccessful();
        });

        it('sets beta release on stable version', function (): void {
            $this->artisan('version:bump', ['type' => 'beta'])
                ->expectsOutputToContain('Version bumped: 1.0.0 → 1.0.0-beta.1')
                ->assertSuccessful();
        });

        it('increments existing beta release', function (): void {
            file_put_contents($this->tempPath, json_encode(['version' => '1.0.0-beta.3']));
            $this->refreshApplication();
            $this->app->singleton(VersionLoader::class, fn (): VersionLoader => new VersionLoader($this->tempPath));
            $this->app->singleton(Version::class, fn ($app) => $app->make(VersionLoader::class)->load());

            $this->artisan('version:bump', ['type' => 'beta'])
                ->expectsOutputToContain('Version bumped: 1.0.0-beta.3 → 1.0.0-beta.4')
                ->assertSuccessful();
        });

        it('sets rc release on stable version', function (): void {
            $this->artisan('version:bump', ['type' => 'rc'])
                ->expectsOutputToContain('Version bumped: 1.0.0 → 1.0.0-rc.1')
                ->assertSuccessful();
        });

        it('increments existing rc release', function (): void {
            file_put_contents($this->tempPath, json_encode(['version' => '2.0.0-rc.2']));
            $this->refreshApplication();
            $this->app->singleton(VersionLoader::class, fn (): VersionLoader => new VersionLoader($this->tempPath));
            $this->app->singleton(Version::class, fn ($app) => $app->make(VersionLoader::class)->load());

            $this->artisan('version:bump', ['type' => 'rc'])
                ->expectsOutputToContain('Version bumped: 2.0.0-rc.2 → 2.0.0-rc.3')
                ->assertSuccessful();
        });

        it('switches from alpha to beta', function (): void {
            file_put_contents($this->tempPath, json_encode(['version' => '1.0.0-alpha.5']));
            $this->refreshApplication();
            $this->app->singleton(VersionLoader::class, fn (): VersionLoader => new VersionLoader($this->tempPath));
            $this->app->singleton(Version::class, fn ($app) => $app->make(VersionLoader::class)->load());

            $this->artisan('version:bump', ['type' => 'beta'])
                ->expectsOutputToContain('Version bumped: 1.0.0-alpha.5 → 1.0.0-beta.1')
                ->assertSuccessful();
        });
    });

    describe('build metadata', function (): void {
        it('adds build metadata with --build flag', function (): void {
            $this->artisan('version:bump', ['type' => 'patch', '--build' => '123'])
                ->expectsOutputToContain('Version bumped: 1.0.0 → 1.0.1+123')
                ->assertSuccessful();

            $data = json_decode(file_get_contents($this->tempPath), true);
            expect($data['version'])->toBe('1.0.1+123');
        });

        it('adds build metadata with git sha style', function (): void {
            $this->artisan('version:bump', ['type' => 'minor', '--build' => 'abc1234'])
                ->expectsOutputToContain('Version bumped: 1.0.0 → 1.1.0+abc1234')
                ->assertSuccessful();
        });

        it('adds build metadata with date style', function (): void {
            $this->artisan('version:bump', ['type' => 'major', '--build' => '20251211'])
                ->expectsOutputToContain('Version bumped: 1.0.0 → 2.0.0+20251211')
                ->assertSuccessful();
        });

        it('combines pre-release with build metadata', function (): void {
            $this->artisan('version:bump', ['type' => 'alpha', '--build' => 'build.999'])
                ->expectsOutputToContain('Version bumped: 1.0.0 → 1.0.0-alpha.1+build.999')
                ->assertSuccessful();
        });
    });

    describe('git integration', function (): void {
        beforeEach(function (): void {
            config(['version.git.enabled' => true]);
            config(['version.git.commit_message' => 'Bump version to {version}']);
            config(['version.git.tag_format' => 'v{version}']);
        });

        it('skips git when --no-git flag is provided', function (): void {
            Process::fake();

            $this->artisan('version:bump', ['type' => 'patch', '--no-git' => true])
                ->assertSuccessful();

            Process::assertNothingRan();
        });

        it('skips git when git is not available', function (): void {
            Process::fake([
                'git rev-parse --is-inside-work-tree 2>/dev/null' => new FakeProcessResult(exitCode: 128),
            ]);

            $this->artisan('version:bump', ['type' => 'patch'])
                ->expectsOutputToContain('Git is not available')
                ->assertSuccessful();
        });

        it('commits and tags when git is enabled and available', function (): void {
            Process::fake([
                'git rev-parse --is-inside-work-tree 2>/dev/null' => new FakeProcessResult(output: 'true'),
                '*' => new FakeProcessResult,
            ]);

            $this->artisan('version:bump', ['type' => 'patch'])
                ->expectsOutputToContain('Committed: 1.0.1')
                ->expectsOutputToContain('Tagged: v1.0.1')
                ->assertSuccessful();
        });

        it('warns when commit fails', function (): void {
            Process::fake([
                'git rev-parse --is-inside-work-tree 2>/dev/null' => new FakeProcessResult(output: 'true'),
                'git add *' => new FakeProcessResult,
                'git commit *' => new FakeProcessResult(exitCode: 1),
            ]);

            $this->artisan('version:bump', ['type' => 'patch'])
                ->expectsOutputToContain('Failed to create git commit')
                ->assertSuccessful();
        });

        it('warns when tag fails', function (): void {
            Process::fake(function ($process): FakeProcessResult {
                $cmd = is_array($process->command) ? implode(' ', $process->command) : $process->command;

                if (str_contains($cmd, 'rev-parse')) {
                    return new FakeProcessResult(output: 'true');
                }
                if (str_contains($cmd, 'git tag')) {
                    return new FakeProcessResult(exitCode: 1);
                }

                return new FakeProcessResult;
            });

            $this->artisan('version:bump', ['type' => 'patch'])
                ->expectsOutputToContain('Failed to create git tag')
                ->assertSuccessful();
        });
    });
});
