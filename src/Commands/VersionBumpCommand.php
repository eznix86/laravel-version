<?php

declare(strict_types=1);

namespace Eznix86\Version\Commands;

use Eznix86\Version\Git;
use Eznix86\Version\Version;
use Eznix86\Version\VersionLoader;
use Illuminate\Console\Command;
use Illuminate\Console\Prohibitable;

use function Laravel\Prompts\select;

class VersionBumpCommand extends Command
{
    use Prohibitable;

    protected $signature = 'version:bump
                            {type? : The version type to bump (major, minor, patch, alpha, beta, rc)}
                            {--build= : Set build metadata (e.g., --build=123 results in 1.0.0+123)}
                            {--no-git : Skip git commit and tag even if git integration is enabled}';

    protected $description = 'Bump the application version';

    /** @var list<string> */
    protected array $types = ['major', 'minor', 'patch', 'alpha', 'beta', 'rc'];

    public function handle(Version $version, VersionLoader $loader, Git $git): int
    {
        if ($this->isProhibited()) {
            return self::FAILURE;
        }

        $type = $this->argument('type');

        if (! is_string($type) || $type === '') {
            $type = select(
                label: 'What type of version bump?',
                options: [
                    'major' => 'Major (breaking changes)',
                    'minor' => 'Minor (new features)',
                    'patch' => 'Patch (bug fixes)',
                    'alpha' => 'Alpha (pre-release)',
                    'beta' => 'Beta (pre-release)',
                    'rc' => 'Release Candidate (pre-release)',
                ],
                default: 'patch'
            );
        }

        if (! in_array($type, $this->types, true)) {
            $this->error("Invalid version type: {$type}");
            $this->info('Valid types: '.implode(', ', $this->types));

            return self::FAILURE;
        }

        /** @var 'major'|'minor'|'patch'|'alpha'|'beta'|'rc' $type */
        $oldVersion = $version->get();

        $this->bumpVersion($version, $type);

        $build = $this->option('build');
        if (is_string($build) && $build !== '') {
            $version->setBuild($build);
        }

        $loader->save($version);

        $this->info("Version bumped: {$oldVersion} → {$version->get()}");

        $this->handleGit($git, $version, $loader);

        return self::SUCCESS;
    }

    protected function handleGit(Git $git, Version $version, VersionLoader $loader): void
    {
        if ($this->option('no-git')) {
            return;
        }

        if (! config('version.git.enabled')) {
            return;
        }

        if (! $git->isAvailable()) {
            $this->warn('Git is not available or not in a git repository. Skipping git integration.');

            return;
        }

        $newVersion = $version->get();

        if ($git->commit($newVersion, $loader->path())) {
            $this->info("Committed: {$newVersion}");
        } else {
            $this->warn('Failed to create git commit.');

            return;
        }

        if ($git->tag($newVersion)) {
            $tagFormat = config('version.git.tag_format');
            $tagName = str_replace('{version}', $newVersion, $tagFormat);
            $this->info("Tagged: {$tagName}");
        } else {
            $this->warn('Failed to create git tag.');
        }
    }

    /**
     * @param  'major'|'minor'|'patch'|'alpha'|'beta'|'rc'  $type
     */
    protected function bumpVersion(Version $version, string $type): void
    {
        match ($type) {
            'major' => $version->incrementMajor(),
            'minor' => $version->incrementMinor(),
            'patch' => $version->incrementPatch(),
            'alpha' => $this->handlePreRelease($version, 'alpha'),
            'beta' => $this->handlePreRelease($version, 'beta'),
            'rc' => $this->handlePreRelease($version, 'rc'),
        };
    }

    /**
     * @param  'alpha'|'beta'|'rc'  $type
     */
    protected function handlePreRelease(Version $version, string $type): void
    {
        $preRelease = $version->preRelease();

        if ($preRelease !== null && str_starts_with($preRelease, "{$type}.")) {
            $version->incrementPreRelease();
        } else {
            match ($type) {
                'alpha' => $version->alpha(),
                'beta' => $version->beta(),
                'rc' => $version->rc(),
            };
        }
    }
}
