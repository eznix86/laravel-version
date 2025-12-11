<?php

declare(strict_types=1);

use Eznix86\Version\Version;

if (! function_exists('version')) {
    /**
     * Get the version instance or the current version string.
     */
    function version(): Version
    {
        return app(Version::class);
    }
}
