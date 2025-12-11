<?php

declare(strict_types=1);

use Eznix86\Version\Git;
use Illuminate\Process\FakeProcessResult;
use Illuminate\Support\Facades\Process;

beforeEach(function (): void {
    config(['version.git.commit_message' => 'Bump version to {version}']);
    config(['version.git.tag_format' => 'v{version}']);
});

describe('Git', function (): void {
    describe('isAvailable', function (): void {
        it('returns true when inside git repository', function (): void {
            Process::fake([
                'git rev-parse --is-inside-work-tree 2>/dev/null' => new FakeProcessResult(output: 'true'),
            ]);

            $git = new Git;

            expect($git->isAvailable())->toBeTrue();
        });

        it('returns false when not in git repository', function (): void {
            Process::fake([
                'git rev-parse --is-inside-work-tree 2>/dev/null' => new FakeProcessResult(exitCode: 128, output: ''),
            ]);

            $git = new Git;

            expect($git->isAvailable())->toBeFalse();
        });

        it('returns false when git outputs false', function (): void {
            Process::fake([
                'git rev-parse --is-inside-work-tree 2>/dev/null' => new FakeProcessResult(output: 'false'),
            ]);

            $git = new Git;

            expect($git->isAvailable())->toBeFalse();
        });
    });

    describe('commit', function (): void {
        it('returns true on successful commit', function (): void {
            Process::fake([
                '*' => new FakeProcessResult,
            ]);

            $git = new Git;
            $result = $git->commit('2.0.0', '/path/to/version.json');

            expect($result)->toBeTrue();
        });

        it('returns false on failed commit', function (): void {
            Process::fake([
                'git add *' => new FakeProcessResult,
                'git commit *' => new FakeProcessResult(exitCode: 1),
            ]);

            $git = new Git;
            $result = $git->commit('2.0.0', '/path/to/version.json');

            expect($result)->toBeFalse();
        });

        it('stages the file before committing', function (): void {
            Process::fake([
                '*' => new FakeProcessResult,
            ]);

            $git = new Git;
            $git->commit('1.0.0', '/path/to/version.json');

            Process::assertRan(fn ($process): bool => $process->command === ['git', 'add', '/path/to/version.json']);
        });

        it('uses configured commit message with version placeholder', function (): void {
            Process::fake([
                '*' => new FakeProcessResult,
            ]);

            $git = new Git;
            $git->commit('3.0.0', '/path/to/version.json');

            Process::assertRan(fn ($process): bool => $process->command === ['git', 'commit', '-m', 'Bump version to 3.0.0']);
        });

        it('uses custom commit message from config', function (): void {
            config(['version.git.commit_message' => 'Release {version}']);
            Process::fake([
                '*' => new FakeProcessResult,
            ]);

            $git = new Git;
            $git->commit('4.0.0', '/path/to/version.json');

            Process::assertRan(fn ($process): bool => $process->command === ['git', 'commit', '-m', 'Release 4.0.0']);
        });
    });

    describe('tag', function (): void {
        it('returns true on successful tag creation', function (): void {
            Process::fake([
                '*' => new FakeProcessResult,
            ]);

            $git = new Git;
            $result = $git->tag('1.0.0');

            expect($result)->toBeTrue();
        });

        it('returns false on failed tag creation', function (): void {
            Process::fake([
                '*' => new FakeProcessResult(exitCode: 1),
            ]);

            $git = new Git;
            $result = $git->tag('1.0.0');

            expect($result)->toBeFalse();
        });

        it('uses configured tag format with version placeholder', function (): void {
            Process::fake([
                '*' => new FakeProcessResult,
            ]);

            $git = new Git;
            $git->tag('2.5.0');

            Process::assertRan(fn ($process): bool => $process->command === ['git', 'tag', 'v2.5.0']);
        });

        it('uses custom tag format from config', function (): void {
            config(['version.git.tag_format' => 'release-{version}']);
            Process::fake([
                '*' => new FakeProcessResult,
            ]);

            $git = new Git;
            $git->tag('1.2.3');

            Process::assertRan(fn ($process): bool => $process->command === ['git', 'tag', 'release-1.2.3']);
        });

        it('handles pre-release versions', function (): void {
            Process::fake([
                '*' => new FakeProcessResult,
            ]);

            $git = new Git;
            $git->tag('1.0.0-alpha.1');

            Process::assertRan(fn ($process): bool => $process->command === ['git', 'tag', 'v1.0.0-alpha.1']);
        });
    });
});
