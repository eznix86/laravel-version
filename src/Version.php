<?php

declare(strict_types=1);

namespace Eznix86\Version;

use Eznix86\Version\Commands\VersionBumpCommand;
use Eznix86\Version\Commands\VersionSetCommand;
use PHLAK\SemVer\Version as SemVer;

class Version implements \Stringable
{
    /**
     * Prohibit version commands from running.
     */
    public static function prohibitCommands(bool $prohibited = true): void
    {
        VersionBumpCommand::prohibit($prohibited);
        VersionSetCommand::prohibit($prohibited);
    }

    protected SemVer $semver;

    public function __construct(string $version = '1.0.0')
    {
        $this->semver = new SemVer($version);
    }

    /**
     * Get the current version string.
     */
    public function get(): string
    {
        return (string) $this->semver;
    }

    /**
     * Set the version to a specific value.
     */
    public function set(string $version): self
    {
        $this->semver = new SemVer($version);

        return $this;
    }

    /**
     * Increment the major version.
     */
    public function incrementMajor(): self
    {
        $this->semver->incrementMajor();

        return $this;
    }

    /**
     * Increment the minor version.
     */
    public function incrementMinor(): self
    {
        $this->semver->incrementMinor();

        return $this;
    }

    /**
     * Increment the patch version.
     */
    public function incrementPatch(): self
    {
        $this->semver->incrementPatch();

        return $this;
    }

    /**
     * Increment the pre-release version.
     */
    public function incrementPreRelease(): self
    {
        $this->semver->incrementPreRelease();

        return $this;
    }

    /**
     * Set as alpha release.
     */
    public function alpha(int $num = 1): self
    {
        $this->semver->setPreRelease("alpha.{$num}");

        return $this;
    }

    /**
     * Set as beta release.
     */
    public function beta(int $num = 1): self
    {
        $this->semver->setPreRelease("beta.{$num}");

        return $this;
    }

    /**
     * Set as release candidate.
     */
    public function rc(int $num = 1): self
    {
        $this->semver->setPreRelease("rc.{$num}");

        return $this;
    }

    /**
     * Remove pre-release designation (stable release).
     */
    public function stable(): self
    {
        $this->semver->setPreRelease(null);

        return $this;
    }

    /**
     * Get the major version number.
     */
    public function major(): int
    {
        return $this->semver->major;
    }

    /**
     * Get the minor version number.
     */
    public function minor(): int
    {
        return $this->semver->minor;
    }

    /**
     * Get the patch version number.
     */
    public function patch(): int
    {
        return $this->semver->patch;
    }

    /**
     * Get the pre-release string.
     */
    public function preRelease(): ?string
    {
        return $this->semver->preRelease;
    }

    /**
     * Check if this is a pre-release version.
     */
    public function isPreRelease(): bool
    {
        return $this->semver->preRelease !== null;
    }

    /**
     * Check if this is a stable release.
     */
    public function isStable(): bool
    {
        return ! $this->isPreRelease();
    }

    /**
     * Get the build metadata string.
     */
    public function build(): ?string
    {
        return $this->semver->build;
    }

    /**
     * Set build metadata.
     */
    public function setBuild(?string $build): self
    {
        $this->semver->setBuild($build);

        return $this;
    }

    /**
     * Clear build metadata.
     */
    public function clearBuild(): self
    {
        $this->semver->setBuild(null);

        return $this;
    }

    /**
     * Check if this version has build metadata.
     */
    public function hasBuild(): bool
    {
        return $this->semver->build !== null;
    }

    /**
     * Check if this version is greater than another.
     */
    public function gt(self|string $version): bool
    {
        return $this->semver->gt($this->toSemVer($version));
    }

    /**
     * Check if this version is greater than or equal to another.
     */
    public function gte(self|string $version): bool
    {
        return $this->semver->gte($this->toSemVer($version));
    }

    /**
     * Check if this version is less than another.
     */
    public function lt(self|string $version): bool
    {
        return $this->semver->lt($this->toSemVer($version));
    }

    /**
     * Check if this version is less than or equal to another.
     */
    public function lte(self|string $version): bool
    {
        return $this->semver->lte($this->toSemVer($version));
    }

    /**
     * Check if this version is equal to another.
     */
    public function eq(self|string $version): bool
    {
        return $this->semver->eq($this->toSemVer($version));
    }

    /**
     * Check if this version is not equal to another.
     */
    public function neq(self|string $version): bool
    {
        return $this->semver->neq($this->toSemVer($version));
    }

    /**
     * Convert a Version or string to SemVer instance.
     */
    protected function toSemVer(self|string $version): SemVer
    {
        if ($version instanceof self) {
            return $version->raw();
        }

        return new SemVer($version);
    }

    /**
     * Get the underlying SemVer instance.
     */
    public function raw(): SemVer
    {
        return $this->semver;
    }

    /**
     * Convert version to string.
     */
    public function __toString(): string
    {
        return $this->get();
    }
}
