<?php

declare(strict_types=1);

namespace Eznix86\Version\Commands;

use Eznix86\Version\Git;
use Eznix86\Version\Version;
use Eznix86\Version\VersionLoader;
use Illuminate\Console\Command;
use Illuminate\Console\Prohibitable;
use PHLAK\SemVer\Exceptions\InvalidVersionException;

class VersionSetCommand extends Command
{
    use Prohibitable;

    protected $signature = 'version:set
                            {version? : The version to set (e.g., 1.2.3, 2.0.0-alpha.1)}
                            {--match-git-tags : Use the latest git tag as the version (requires git repository)}
                            {--no-git : Skip git commit and tag even if git integration is enabled}';

    protected $description = 'Set the application version to a specific value';

    public function __construct(
        protected VersionLoader $loader,
        protected Git $git
    ) {
        parent::__construct();
    }

    public function handle(Version $version): int
    {
        if ($this->isProhibited()) {
            return self::FAILURE;
        }

        $versionString = $this->resolveVersionString();

        if (blank($versionString)) {
            $this->error('A version string is required.');

            return self::FAILURE;
        }

        if (! $this->validateVersion($versionString)) {
            return self::FAILURE;
        }

        $this->updateVersion($version, $versionString);

        $this->handleGit($version);

        return self::SUCCESS;
    }

    protected function resolveVersionString(): ?string
    {
        if ($this->option('match-git-tags')) {
            return $this->resolveVersionFromGitTags();
        }

        $version = $this->argument('version');

        return is_string($version) ? $version : null;
    }

    protected function resolveVersionFromGitTags(): ?string
    {
        if (! $this->git->isAvailable()) {
            $this->error('Git is not available or not in a git repository.');

            return null;
        }

        $tags = $this->git->allTags();

        if ($tags->isEmpty()) {
            $this->error('No git tags found.');

            return null;
        }

        $versionString = $tags->last()->get();
        $this->info("Using latest git tag: {$versionString}");

        return $versionString;
    }

    protected function validateVersion(string $versionString): bool
    {
        try {
            new Version($versionString);

            return true;
        } catch (InvalidVersionException) {
            $this->error("Invalid version format: {$versionString}");
            $this->info('Please provide a valid semver string (e.g., 1.0.0, 2.1.0-alpha.1, 3.0.0+build.123)');

            return false;
        }
    }

    protected function updateVersion(Version $version, string $versionString): void
    {
        $oldVersion = $version->get();

        $version->set($versionString);
        $this->loader->save($version);

        $this->info("Version set: {$oldVersion} → {$version->get()}");
    }

    protected function handleGit(Version $version): void
    {
        if ($this->option('no-git') || ! config('version.git.enabled')) {
            return;
        }

        if (! $this->git->isAvailable()) {
            $this->warn('Git is not available or not in a git repository. Skipping git integration.');

            return;
        }

        $newVersion = $version->get();

        if (! $this->git->commit($newVersion, $this->loader->path())) {
            $this->warn('Failed to create git commit.');

            return;
        }

        $this->info("Committed: {$newVersion}");

        if ($this->option('match-git-tags')) {
            return;
        }

        if (! $this->git->tag($newVersion)) {
            $this->warn('Failed to create git tag.');

            return;
        }

        $tagFormat = config('version.git.tag_format');
        $tagName = str_replace('{version}', $newVersion, $tagFormat);
        $this->info("Tagged: {$tagName}");
    }
}
