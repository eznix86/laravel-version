<?php

namespace Eznix86\Version\Commands;

use Eznix86\Version\Git;
use Eznix86\Version\Version;
use Illuminate\Console\Command;

use function Laravel\Prompts\select;

class VersionBumpCommand extends Command
{
    protected $signature = 'version:bump
                            {type? : The version type to bump (major, minor, patch, alpha, beta, rc)}
                            {--no-git : Skip git commit and tag even if git integration is enabled}';

    protected $description = 'Bump the application version';

    protected array $types = ['major', 'minor', 'patch', 'alpha', 'beta', 'rc'];

    public function handle(Version $version, Git $git): int
    {
        $type = $this->argument('type');

        if (! $type) {
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

        if (! in_array($type, $this->types)) {
            $this->error("Invalid version type: {$type}");
            $this->info('Valid types: '.implode(', ', $this->types));

            return self::FAILURE;
        }

        $oldVersion = $version->get();

        $this->bumpVersion($version, $type);

        $version->save();

        $this->info("Version bumped: {$oldVersion} → {$version->get()}");

        $this->handleGit($git, $version);

        return self::SUCCESS;
    }

    protected function handleGit(Git $git, Version $version): void
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

        if ($git->commit($newVersion, $version->path())) {
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

    protected function handlePreRelease(Version $version, string $type): void
    {
        $preRelease = $version->preRelease();

        if ($preRelease && str_starts_with($preRelease, "{$type}.")) {
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
