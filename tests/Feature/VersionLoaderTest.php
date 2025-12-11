<?php

declare(strict_types=1);

use Eznix86\Version\Version;
use Eznix86\Version\VersionLoader;

beforeEach(function (): void {
    $this->tempPath = sys_get_temp_dir().'/version_test_'.uniqid().'.json';
});

afterEach(function (): void {
    if (file_exists($this->tempPath)) {
        unlink($this->tempPath);
    }
});

describe('VersionLoader', function (): void {
    describe('constructor', function (): void {
        it('uses custom path when provided', function (): void {
            $loader = new VersionLoader($this->tempPath);

            expect($loader->path())->toBe($this->tempPath);
        });
    });

    describe('load', function (): void {
        it('loads version from existing file', function (): void {
            file_put_contents($this->tempPath, json_encode(['version' => '2.5.0']));
            $loader = new VersionLoader($this->tempPath);

            $version = $loader->load();

            expect($version)->toBeInstanceOf(Version::class);
            expect($version->get())->toBe('2.5.0');
        });

        it('creates default 1.0.0 when file does not exist', function (): void {
            $loader = new VersionLoader($this->tempPath);

            $version = $loader->load();

            expect($version->get())->toBe('1.0.0');
            expect(file_exists($this->tempPath))->toBeTrue();
        });

        it('defaults to 1.0.0 when version key is missing', function (): void {
            file_put_contents($this->tempPath, json_encode(['other' => 'data']));
            $loader = new VersionLoader($this->tempPath);

            $version = $loader->load();

            expect($version->get())->toBe('1.0.0');
        });

        it('loads pre-release version correctly', function (): void {
            file_put_contents($this->tempPath, json_encode(['version' => '1.0.0-beta.3']));
            $loader = new VersionLoader($this->tempPath);

            $version = $loader->load();

            expect($version->get())->toBe('1.0.0-beta.3');
            expect($version->isPreRelease())->toBeTrue();
        });
    });

    describe('save', function (): void {
        it('saves version to file', function (): void {
            $loader = new VersionLoader($this->tempPath);
            $version = new Version('3.2.1');

            $loader->save($version);

            $content = file_get_contents($this->tempPath);
            $data = json_decode($content, true);
            expect($data['version'])->toBe('3.2.1');
        });

        it('saves with JSON_PRETTY_PRINT formatting', function (): void {
            $loader = new VersionLoader($this->tempPath);
            $version = new Version('1.0.0');

            $loader->save($version);

            $content = file_get_contents($this->tempPath);
            expect($content)->toContain("\n");
            expect($content)->toContain('    ');
        });

        it('appends newline at end of file', function (): void {
            $loader = new VersionLoader($this->tempPath);
            $version = new Version('1.0.0');

            $loader->save($version);

            $content = file_get_contents($this->tempPath);
            expect(str_ends_with($content, "\n"))->toBeTrue();
        });

        it('saves pre-release version correctly', function (): void {
            $loader = new VersionLoader($this->tempPath);
            $version = new Version('2.0.0');
            $version->alpha(2);

            $loader->save($version);

            $data = json_decode(file_get_contents($this->tempPath), true);
            expect($data['version'])->toBe('2.0.0-alpha.2');
        });

        it('overwrites existing file', function (): void {
            file_put_contents($this->tempPath, json_encode(['version' => '1.0.0']));
            $loader = new VersionLoader($this->tempPath);
            $version = new Version('5.0.0');

            $loader->save($version);

            $data = json_decode(file_get_contents($this->tempPath), true);
            expect($data['version'])->toBe('5.0.0');
        });
    });

    describe('path', function (): void {
        it('returns the configured path', function (): void {
            $loader = new VersionLoader($this->tempPath);

            expect($loader->path())->toBe($this->tempPath);
        });
    });

    describe('integration', function (): void {
        it('round-trips version through save and load', function (): void {
            $loader = new VersionLoader($this->tempPath);
            $original = new Version('4.3.2-rc.1');

            $loader->save($original);
            $loaded = $loader->load();

            expect($loaded->get())->toBe('4.3.2-rc.1');
        });

        it('persists incremented version', function (): void {
            $loader = new VersionLoader($this->tempPath);
            $version = $loader->load();

            $version->incrementMinor();
            $loader->save($version);

            $newLoader = new VersionLoader($this->tempPath);
            $reloaded = $newLoader->load();

            expect($reloaded->get())->toBe('1.1.0');
        });
    });
});
