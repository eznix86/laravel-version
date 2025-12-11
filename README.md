# Laravel Version

Semantic versioning for Laravel applications with git integration.

## Installation

```bash
composer require eznix86/laravel-version
```

Publish the version file:

```bash
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
<footer>v@version</footer>
```

### Facade

```php
use Eznix86\Version\Facades\Version;

Version::get();
Version::incrementMinor();
```

## Configuration

Publish the config file:

```bash
php artisan vendor:publish --tag=version-config
```

```php
// config/version.php
return [
    'git' => [
        'enabled' => true,
        'commit_message' => 'Bump version to {version}',
        'tag_format' => 'v{version}',
    ],
];
```

## License

MIT
