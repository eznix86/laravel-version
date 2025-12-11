<?php

namespace Eznix86\Version;

use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\File;
use PHLAK\SemVer\Version as SemVer;

class Version
{
    protected SemVer $semver;

    protected string $path;

    public function __construct(?string $path = null)
    {
        $this->path = $path ?? App::basePath('version.json');
        $this->load();
    }

    /**
     * Load version from the version.json file.
     */
    protected function load(): void
    {
        if (File::exists($this->path)) {
            $data = json_decode(File::get($this->path), true);
            $this->semver = new SemVer($data['version'] ?? '1.0.0');
        } else {
            $this->semver = new SemVer('1.0.0');
            $this->save();
        }
    }

    /**
     * Save the current version to the version.json file.
     */
    public function save(): self
    {
        File::put($this->path, json_encode([
            'version' => (string) $this->semver,
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)."\n");

        return $this;
    }

    /**
     * Get the current version string.
     */
    public function get(): string
    {
        return (string) $this->semver;
    }

    /**
     * Get the path to the version file.
     */
    public function path(): string
    {
        return $this->path;
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
     * Set or increment pre-release version.
     */
    public function setPreRelease(?string $preRelease): self
    {
        $this->semver->setPreRelease($preRelease);

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
     * Check if this is a stable release (not a pre-release).
     */
    public function isStable(): bool
    {
        return ! $this->isPreRelease();
    }

    /**
     * Check if this is an alpha release.
     */
    public function isAlpha(): bool
    {
        return $this->semver->preRelease !== null
            && str_starts_with($this->semver->preRelease, 'alpha');
    }

    /**
     * Check if this is a beta release.
     */
    public function isBeta(): bool
    {
        return $this->semver->preRelease !== null
            && str_starts_with($this->semver->preRelease, 'beta');
    }

    /**
     * Check if this is a release candidate.
     */
    public function isRc(): bool
    {
        return $this->semver->preRelease !== null
            && str_starts_with($this->semver->preRelease, 'rc');
    }

    /**
     * Check if version matches a constraint (e.g., ">=1.0.0", "^2.0", "~1.5").
     */
    public function satisfies(string $constraint): bool
    {
        return $this->semver->satisfies($constraint);
    }

    /**
     * Compare with another version. Returns -1, 0, or 1.
     */
    public function compareTo(string $version): int
    {
        $other = new SemVer($version);

        if ($this->semver->gt($other)) {
            return 1;
        }

        if ($this->semver->lt($other)) {
            return -1;
        }

        return 0;
    }

    /**
     * Check if version is greater than another.
     */
    public function gt(string $version): bool
    {
        return $this->semver->gt(new SemVer($version));
    }

    /**
     * Check if version is greater than or equal to another.
     */
    public function gte(string $version): bool
    {
        return $this->semver->gte(new SemVer($version));
    }

    /**
     * Check if version is less than another.
     */
    public function lt(string $version): bool
    {
        return $this->semver->lt(new SemVer($version));
    }

    /**
     * Check if version is less than or equal to another.
     */
    public function lte(string $version): bool
    {
        return $this->semver->lte(new SemVer($version));
    }

    /**
     * Check if version equals another.
     */
    public function eq(string $version): bool
    {
        return $this->semver->eq(new SemVer($version));
    }

    /**
     * Check if version does not equal another.
     */
    public function neq(string $version): bool
    {
        return $this->semver->neq(new SemVer($version));
    }

    /**
     * Get the underlying SemVer instance.
     */
    public function semver(): SemVer
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
