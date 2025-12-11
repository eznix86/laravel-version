<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Version Prefix
    |--------------------------------------------------------------------------
    |
    | This value is prepended to the version string when using the @version
    | Blade directive. Set to an empty string to disable the prefix.
    |
    */

    'prefix' => 'v',

    /*
    |--------------------------------------------------------------------------
    | Git Integration
    |--------------------------------------------------------------------------
    |
    | When enabled, version bumps will automatically create a git commit and
    | tag. You can customize the commit message and tag format using the
    | {version} placeholder which will be replaced with the new version.
    |
    */

    'git' => [
        'enabled' => true,
        'commit_message' => 'Bump version to {version}',
        'tag_format' => 'v{version}',
    ],

];
