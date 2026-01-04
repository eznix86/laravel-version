<?php

declare(strict_types=1);

namespace Eznix86\Version;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Process;
use PHLAK\SemVer\Exceptions\InvalidVersionException;

class Git
{
    /**
     * Check if git is available and we're in a git repository.
     */
    public function isAvailable(): bool
    {
        $result = Process::run('git rev-parse --is-inside-work-tree 2>/dev/null');

        return $result->successful() && trim($result->output()) === 'true';
    }

    /**
     * Commit the version file with the configured message.
     */
    public function commit(string $version, string $filePath): bool
    {
        $message = $this->formatMessage(config('version.git.commit_message'), $version);

        Process::run("git add {$filePath}");

        $result = Process::run("git commit -m {$message}");

        return $result->successful();
    }

    /**
     * Create a git tag for the version.
     */
    public function tag(string $version): bool
    {
        $tagName = $this->formatMessage(config('version.git.tag_format'), $version);

        $result = Process::run("git tag {$tagName}");

        return $result->successful();
    }

    /**
     * Get all git tags as a collection of Version objects.
     *
     * @return Collection<int, Version>
     */
    public function allTags(): Collection
    {
        $result = Process::run('git tag -l');

        if (! $result->successful()) {
            return Collection::empty();
        }

        $tagFormat = config('version.git.tag_format', 'v{version}');
        $prefix = str_replace('{version}', '', $tagFormat);

        return Collection::make(explode("\n", trim($result->output())))
            ->filter()
            ->map(function (string $tag) use ($prefix): ?Version {
                $versionString = str_starts_with($tag, $prefix)
                    ? substr($tag, strlen($prefix))
                    : $tag;

                try {
                    return new Version($versionString);
                } catch (InvalidVersionException) {
                    return null;
                }
            })
            ->filter()
            ->sort(fn (Version $a, Version $b): int => $a->gt($b) ? 1 : -1)
            ->values();
    }

    /**
     * Format a message template with the version.
     */
    protected function formatMessage(string $template, string $version): string
    {
        return str_replace('{version}', $version, $template);
    }
}
