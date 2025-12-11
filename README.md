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
```

### Helper

```php
version()->get();              // "1.0.0"
version()->major();            // 1
version()->minor();            // 0
version()->patch();            // 0
version()->preRelease();       // null or "alpha.1"
version()->build();            // null or "123"
version()->isStable();         // true
version()->isPreRelease();     // false

// Comparisons
version()->gt('0.9.0');        // true
version()->eq('1.0.0');        // true
version()->lt(new Version('2.0.0'));
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

## Production Protection

To prevent accidental version bumps in production, add this to your `AppServiceProvider`:

```php
use Eznix86\Version\Commands\VersionBumpCommand;

public function boot(): void
{
    VersionBumpCommand::prohibit($this->app->isProduction());
}
```

## License

MIT
