<?php

declare(strict_types=1);

use Eznix86\Version\Facades\Version as VersionFacade;
use Eznix86\Version\Version;

describe('version() helper', function (): void {
    it('returns Version instance', function (): void {
        expect(version())->toBeInstanceOf(Version::class);
    });

    it('returns same singleton instance', function (): void {
        $version1 = version();
        $version2 = version();

        expect($version1)->toBe($version2);
    });

    it('allows chaining version methods', function (): void {
        $this->app->singleton(Version::class, fn (): \Eznix86\Version\Version => new Version('1.0.0'));

        $result = version()->incrementMinor()->get();

        expect($result)->toBe('1.1.0');
    });

    it('provides access to version string via get()', function (): void {
        $this->app->singleton(Version::class, fn (): \Eznix86\Version\Version => new Version('3.2.1'));

        expect(version()->get())->toBe('3.2.1');
    });

    it('provides access to version components', function (): void {
        $this->app->singleton(Version::class, fn (): \Eznix86\Version\Version => new Version('5.4.3-beta.2'));

        expect(version()->major())->toBe(5);
        expect(version()->minor())->toBe(4);
        expect(version()->patch())->toBe(3);
        expect(version()->preRelease())->toBe('beta.2');
        expect(version()->isPreRelease())->toBeTrue();
    });
});

describe('Version Facade', function (): void {
    beforeEach(function (): void {
        $this->app->singleton(Version::class, fn (): \Eznix86\Version\Version => new Version('2.0.0'));
    });

    it('resolves to Version instance', function (): void {
        expect(VersionFacade::getFacadeRoot())->toBeInstanceOf(Version::class);
    });

    it('proxies get() method', function (): void {
        expect(VersionFacade::get())->toBe('2.0.0');
    });

    it('proxies incrementMajor() method', function (): void {
        VersionFacade::incrementMajor();

        expect(VersionFacade::get())->toBe('3.0.0');
    });

    it('proxies incrementMinor() method', function (): void {
        VersionFacade::incrementMinor();

        expect(VersionFacade::get())->toBe('2.1.0');
    });

    it('proxies incrementPatch() method', function (): void {
        VersionFacade::incrementPatch();

        expect(VersionFacade::get())->toBe('2.0.1');
    });

    it('proxies alpha() method', function (): void {
        VersionFacade::alpha();

        expect(VersionFacade::get())->toBe('2.0.0-alpha.1');
    });

    it('proxies beta() method', function (): void {
        VersionFacade::beta();

        expect(VersionFacade::get())->toBe('2.0.0-beta.1');
    });

    it('proxies rc() method', function (): void {
        VersionFacade::rc();

        expect(VersionFacade::get())->toBe('2.0.0-rc.1');
    });

    it('proxies major() method', function (): void {
        expect(VersionFacade::major())->toBe(2);
    });

    it('proxies minor() method', function (): void {
        expect(VersionFacade::minor())->toBe(0);
    });

    it('proxies patch() method', function (): void {
        expect(VersionFacade::patch())->toBe(0);
    });

    it('proxies preRelease() method', function (): void {
        expect(VersionFacade::preRelease())->toBeNull();

        VersionFacade::alpha();

        expect(VersionFacade::preRelease())->toBe('alpha.1');
    });

    it('proxies isPreRelease() method', function (): void {
        expect(VersionFacade::isPreRelease())->toBeFalse();

        VersionFacade::beta();

        expect(VersionFacade::isPreRelease())->toBeTrue();
    });

    it('proxies isStable() method', function (): void {
        expect(VersionFacade::isStable())->toBeTrue();

        VersionFacade::rc();

        expect(VersionFacade::isStable())->toBeFalse();
    });

    it('proxies stable() method', function (): void {
        VersionFacade::alpha();
        expect(VersionFacade::isPreRelease())->toBeTrue();

        VersionFacade::stable();
        expect(VersionFacade::isStable())->toBeTrue();
    });

    it('proxies set() method', function (): void {
        VersionFacade::set('9.9.9');

        expect(VersionFacade::get())->toBe('9.9.9');
    });

    it('proxies raw() method', function (): void {
        $raw = VersionFacade::raw();

        expect($raw)->toBeInstanceOf(\PHLAK\SemVer\Version::class);
    });
});
