# Laravel Version

Semantic versioning for Laravel applications with git integration.

## Installation

```bash
composer require eznix86/laravel-version
```

Publish the configuration and version file:

```bash
php artisan vendor:publish --tag=version-config
php artisan vendor:publish --tag=version-json
```

## Usage

### Commands

```bash
# Show current version
php artisan version:show

# Bump version
php artisan version:bump patch      # 1.0.0 → 1.0.1
php artisan version:bump minor      # 1.0.0 → 1.1.0
php artisan version:bump major      # 1.0.0 → 2.0.0

# Pre-release versions
php artisan version:bump alpha      # 1.0.0 → 1.0.0-alpha.1
php artisan version:bump beta       # 1.0.0 → 1.0.0-beta.1
php artisan version:bump rc         # 1.0.0 → 1.0.0-rc.1

# With build metadata
php artisan version:bump patch --build=123              # 1.0.0 → 1.0.1+123
php artisan version:bump minor --build=$(git rev-parse --short HEAD)

# Skip git commit/tag
php artisan version:bump patch --no-git

# Set version to specific value
php artisan version:set 2.0.0         # Set version to 2.0.0
php artisan version:set 1.2.3-alpha.1  # Set version with pre-release
php artisan version:set 1.0.0+build.1  # Set version with build metadata
php artisan version:set 2.1.0 --no-git  # Skip git integration

# Set version from latest git tag
php artisan version:set --match-git-tags            # Use latest git tag as version (commits, doesn't create tag)
php artisan version:set --match-git-tags --no-git   # Use latest git tag but skip commit/tag
```

### Helper

```php

// Mirrors the Version Facade

version()->get();              // "1.0.0"
version()->major();            // 1
version()->minor();            // 0
version()->patch();            // 0
version()->preRelease();       // null or "alpha.1"
version()->build();            // null or "123"
version()->isStable();         // true
version()->isPreRelease();     // false

// Comparisons
version()->gt('0.9.0');                  // true
version()->isGreaterThan('0.9.0');       // true (alias of gt)
version()->gte('1.0.0');                 // true
version()->isGreaterThanOrEqualTo('1.0.0'); // true (alias of gte)
version()->lt('2.0.0');                  // true
version()->isLessThan('2.0.0');          // true (alias of lt)
version()->lte('1.0.0');                 // true
version()->isLessThanOrEqualTo('1.0.0'); // true (alias of lte)
version()->eq('1.0.0');                  // true
version()->isCompatibleWith('1.0.0');    // true (alias of eq)
version()->neq('2.0.0');                 // true
version()->isNotEqualTo('2.0.0');        // true (alias of neq)
```

### Blade

```blade
<footer>@version</footer>  <!-- outputs: v1.0.0 (with default prefix) -->
```

The `@version` directive automatically includes the configured prefix.

### Facade

```php
use Eznix86\Version\Facades\Version;

Version::get();
Version::incrementMinor();
```

## Configuration

```php
// config/version.php
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
```

## Artisan About

The package automatically registers the version in Laravel's `about` command:

```bash
php artisan about
```

This will display your application version in the Application section.

## Production Protection

To prevent accidental version changes in production, add this to your `AppServiceProvider`:

```php
use Eznix86\Version\Version;

public function boot(): void
{
    Version::prohibitCommands($this->app->isProduction());
}
```

Or prohibit commands individually:

```php
use Eznix86\Version\Commands\VersionBumpCommand;
use Eznix86\Version\Commands\VersionSetCommand;

public function boot(): void
{
    VersionBumpCommand::prohibit($this->app->isProduction());
    VersionSetCommand::prohibit($this->app->isProduction());
}
```

## License

MIT
