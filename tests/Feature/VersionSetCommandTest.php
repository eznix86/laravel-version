<?php

declare(strict_types=1);

use Eznix86\Version\Commands\VersionSetCommand;
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

describe('version:set command', function (): void {
    describe('version setting', function (): void {
        it('sets a simple version', function (): void {
            $this->artisan('version:set', ['version' => '2.1.0'])
                ->expectsOutputToContain('Version set: 1.0.0 → 2.1.0')
                ->assertSuccessful();

            $data = json_decode(file_get_contents($this->tempPath), true);
            expect($data['version'])->toBe('2.1.0');
        });

        it('sets a pre-release version', function (): void {
            $this->artisan('version:set', ['version' => '3.0.0-alpha.1'])
                ->expectsOutputToContain('Version set: 1.0.0 → 3.0.0-alpha.1')
                ->assertSuccessful();

            $data = json_decode(file_get_contents($this->tempPath), true);
            expect($data['version'])->toBe('3.0.0-alpha.1');
        });

        it('sets a version with build metadata', function (): void {
            $this->artisan('version:set', ['version' => '1.5.0+build.123'])
                ->expectsOutputToContain('Version set: 1.0.0 → 1.5.0+build.123')
                ->assertSuccessful();

            $data = json_decode(file_get_contents($this->tempPath), true);
            expect($data['version'])->toBe('1.5.0+build.123');
        });

        it('sets a version with pre-release and build metadata', function (): void {
            $this->artisan('version:set', ['version' => '2.0.0-beta.2+20251211'])
                ->expectsOutputToContain('Version set: 1.0.0 → 2.0.0-beta.2+20251211')
                ->assertSuccessful();

            $data = json_decode(file_get_contents($this->tempPath), true);
            expect($data['version'])->toBe('2.0.0-beta.2+20251211');
        });

        it('rejects invalid version format', function (): void {
            $this->artisan('version:set', ['version' => 'invalid-version'])
                ->expectsOutputToContain('Invalid version format: invalid-version')
                ->assertFailed();

            // Version should not have changed
            $data = json_decode(file_get_contents($this->tempPath), true);
            expect($data['version'])->toBe('1.0.0');
        });

        it('rejects empty version', function (): void {
            $this->artisan('version:set', ['version' => ''])
                ->expectsOutputToContain('A version string is required')
                ->assertFailed();
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

            $this->artisan('version:set', ['version' => '2.0.0', '--no-git' => true])
                ->assertSuccessful();

            Process::assertNothingRan();
        });

        it('skips git when git is not available', function (): void {
            Process::fake([
                'git rev-parse --is-inside-work-tree 2>/dev/null' => new FakeProcessResult(exitCode: 128),
            ]);

            $this->artisan('version:set', ['version' => '2.0.0'])
                ->expectsOutputToContain('Git is not available')
                ->assertSuccessful();
        });

        it('commits and tags when git is enabled and available', function (): void {
            Process::fake([
                'git rev-parse --is-inside-work-tree 2>/dev/null' => new FakeProcessResult(output: 'true'),
                '*' => new FakeProcessResult,
            ]);

            $this->artisan('version:set', ['version' => '2.1.0'])
                ->expectsOutputToContain('Committed: 2.1.0')
                ->expectsOutputToContain('Tagged: v2.1.0')
                ->assertSuccessful();
        });

        it('warns when commit fails', function (): void {
            Process::fake([
                'git rev-parse --is-inside-work-tree 2>/dev/null' => new FakeProcessResult(output: 'true'),
                'git add *' => new FakeProcessResult,
                'git commit *' => new FakeProcessResult(exitCode: 1),
            ]);

            $this->artisan('version:set', ['version' => '2.0.0'])
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

            $this->artisan('version:set', ['version' => '2.0.0'])
                ->expectsOutputToContain('Failed to create git tag')
                ->assertSuccessful();
        });
    });

    describe('--match-git-tags option', function (): void {
        beforeEach(function (): void {
            config(['version.git.enabled' => true]);
            config(['version.git.commit_message' => 'Bump version to {version}']);
            config(['version.git.tag_format' => 'v{version}']);
        });

        it('fails when git is not available', function (): void {
            Process::fake([
                'git rev-parse --is-inside-work-tree 2>/dev/null' => new FakeProcessResult(exitCode: 128),
            ]);

            $this->artisan('version:set', ['--match-git-tags' => true])
                ->expectsOutputToContain('Git is not available')
                ->assertFailed();
        });

        it('fails when no git tags exist', function (): void {
            Process::fake([
                'git rev-parse --is-inside-work-tree 2>/dev/null' => new FakeProcessResult(output: 'true'),
                'git tag -l' => new FakeProcessResult(output: ''),
            ]);

            $this->artisan('version:set', ['--match-git-tags' => true])
                ->expectsOutputToContain('No git tags found')
                ->assertFailed();
        });

        it('uses latest git tag as version', function (): void {
            Process::fake([
                'git rev-parse --is-inside-work-tree 2>/dev/null' => new FakeProcessResult(output: 'true'),
                'git tag -l' => new FakeProcessResult(output: "v1.0.0\nv2.0.0\nv1.5.0"),
                '*' => new FakeProcessResult,
            ]);

            $this->artisan('version:set', ['--match-git-tags' => true])
                ->expectsOutputToContain('Using latest git tag: 2.0.0')
                ->expectsOutputToContain('Version set: 1.0.0 → 2.0.0')
                ->assertSuccessful();

            $data = json_decode(file_get_contents($this->tempPath), true);
            expect($data['version'])->toBe('2.0.0');
        });

        it('commits without tagging when using --match-git-tags', function (): void {
            Process::fake([
                'git rev-parse --is-inside-work-tree 2>/dev/null' => new FakeProcessResult(output: 'true'),
                'git tag -l' => new FakeProcessResult(output: "v1.0.0\nv2.0.0"),
                '*' => new FakeProcessResult,
            ]);

            $this->artisan('version:set', ['--match-git-tags' => true])
                ->expectsOutputToContain('Committed: 2.0.0')
                ->assertSuccessful();

            Process::assertRan(fn ($process): bool => str_contains($process->command, 'commit'));
            Process::assertNotRan(fn ($process): bool => (bool) preg_match('/^git tag v?\d+\.\d+\.\d+/', $process->command));
        });

        it('works with pre-release tags', function (): void {
            Process::fake([
                'git rev-parse --is-inside-work-tree 2>/dev/null' => new FakeProcessResult(output: 'true'),
                'git tag -l' => new FakeProcessResult(output: "v1.0.0\nv2.0.0-alpha.1\nv1.5.0"),
                '*' => new FakeProcessResult,
            ]);

            $this->artisan('version:set', ['--match-git-tags' => true])
                ->expectsOutputToContain('Using latest git tag: 2.0.0-alpha.1')
                ->expectsOutputToContain('Version set: 1.0.0 → 2.0.0-alpha.1')
                ->assertSuccessful();

            $data = json_decode(file_get_contents($this->tempPath), true);
            expect($data['version'])->toBe('2.0.0-alpha.1');
        });

        it('skips git when --no-git is also provided', function (): void {
            Process::fake([
                'git rev-parse --is-inside-work-tree 2>/dev/null' => new FakeProcessResult(output: 'true'),
                'git tag -l' => new FakeProcessResult(output: "v1.0.0\nv2.0.0"),
            ]);

            // --match-git-tags with --no-git should work: read tag, set version, skip commit
            $this->artisan('version:set', ['--match-git-tags' => true, '--no-git' => true])
                ->expectsOutputToContain('Using latest git tag: 2.0.0')
                ->expectsOutputToContain('Version set: 1.0.0 → 2.0.0')
                ->assertSuccessful();

            // Git commands ran (to read tags), but no commit or tag creation
            Process::assertRan(fn ($process): bool => str_contains($process->command, 'tag -l'));
            Process::assertNotRan(fn ($process): bool => str_contains($process->command, 'commit'));
            Process::assertNotRan(fn ($process): bool => str_contains($process->command, 'git tag ') && !str_contains($process->command, 'tag -l'));
        });

        it('handles single tag', function (): void {
            Process::fake([
                'git rev-parse --is-inside-work-tree 2>/dev/null' => new FakeProcessResult(output: 'true'),
                'git tag -l' => new FakeProcessResult(output: "v1.0.0"),
                '*' => new FakeProcessResult,
            ]);

            $this->artisan('version:set', ['--match-git-tags' => true])
                ->expectsOutputToContain('Using latest git tag: 1.0.0')
                ->expectsOutputToContain('Version set: 1.0.0 → 1.0.0')
                ->assertSuccessful();
        });

        it('handles tags with build metadata', function (): void {
            Process::fake([
                'git rev-parse --is-inside-work-tree 2>/dev/null' => new FakeProcessResult(output: 'true'),
                'git tag -l' => new FakeProcessResult(output: "v1.0.0\nv2.0.0+build.123"),
                '*' => new FakeProcessResult,
            ]);

            $this->artisan('version:set', ['--match-git-tags' => true])
                ->expectsOutputToContain('Using latest git tag: 2.0.0+build.123')
                ->expectsOutputToContain('Version set: 1.0.0 → 2.0.0+build.123')
                ->assertSuccessful();

            $data = json_decode(file_get_contents($this->tempPath), true);
            expect($data['version'])->toBe('2.0.0+build.123');
        });

        it('fails when command is prohibited', function (): void {
            VersionSetCommand::prohibit(true);

            Process::fake([
                'git rev-parse --is-inside-work-tree 2>/dev/null' => new FakeProcessResult(output: 'true'),
                'git tag -l' => new FakeProcessResult(output: "v1.0.0\nv2.0.0"),
            ]);

            $this->artisan('version:set', ['--match-git-tags' => true])
                ->assertFailed();

            $data = json_decode(file_get_contents($this->tempPath), true);
            expect($data['version'])->toBe('1.0.0');
        });
    });

    describe('production protection', function (): void {
        afterEach(function (): void {
            VersionSetCommand::prohibit(false);
        });

        it('fails when command is prohibited', function (): void {
            VersionSetCommand::prohibit(true);

            $this->artisan('version:set', ['version' => '2.0.0'])
                ->assertFailed();

            $data = json_decode(file_get_contents($this->tempPath), true);
            expect($data['version'])->toBe('1.0.0');
        });

        it('runs when command is not prohibited', function (): void {
            VersionSetCommand::prohibit(false);

            $this->artisan('version:set', ['version' => '2.0.0'])
                ->assertSuccessful();

            $data = json_decode(file_get_contents($this->tempPath), true);
            expect($data['version'])->toBe('2.0.0');
        });

        it('prohibits commands via Version class', function (): void {
            Version::prohibitCommands(true);

            // version:set should fail
            $this->artisan('version:set', ['version' => '2.0.0'])
                ->assertFailed();

            $data = json_decode(file_get_contents($this->tempPath), true);
            expect($data['version'])->toBe('1.0.0');

            // Unprohibit and verify it works
            Version::prohibitCommands(false);

            $this->artisan('version:set', ['version' => '2.0.0'])
                ->assertSuccessful();

            $data = json_decode(file_get_contents($this->tempPath), true);
            expect($data['version'])->toBe('2.0.0');
        });
    });
});
