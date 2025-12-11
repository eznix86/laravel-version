<?php

declare(strict_types=1);

namespace Eznix86\Version\Facades;

use Eznix86\Version\Version as VersionInstance;
use Illuminate\Support\Facades\Facade;

/**
 * @method static string get()
 * @method static VersionInstance set(string $version)
 * @method static VersionInstance incrementMajor()
 * @method static VersionInstance incrementMinor()
 * @method static VersionInstance incrementPatch()
 * @method static VersionInstance incrementPreRelease()
 * @method static VersionInstance alpha(int $num = 1)
 * @method static VersionInstance beta(int $num = 1)
 * @method static VersionInstance rc(int $num = 1)
 * @method static VersionInstance stable()
 * @method static int major()
 * @method static int minor()
 * @method static int patch()
 * @method static string|null preRelease()
 * @method static bool isPreRelease()
 * @method static bool isStable()
 * @method static string|null build()
 * @method static VersionInstance setBuild(?string $build)
 * @method static VersionInstance clearBuild()
 * @method static bool hasBuild()
 * @method static \PHLAK\SemVer\Version raw()
 * @method static bool gt(VersionInstance|string $version)
 * @method static bool gte(VersionInstance|string $version)
 * @method static bool lt(VersionInstance|string $version)
 * @method static bool lte(VersionInstance|string $version)
 * @method static bool eq(VersionInstance|string $version)
 * @method static bool neq(VersionInstance|string $version)
 *
 * @see \Eznix86\Version\Version
 */
class Version extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return VersionInstance::class;
    }
}
