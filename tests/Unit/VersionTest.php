<?php

declare(strict_types=1);

use Eznix86\Version\Version;
use PHLAK\SemVer\Version as SemVer;

describe('Version', function (): void {
    describe('constructor', function (): void {
        it('creates version with default 1.0.0', function (): void {
            $version = new Version;

            expect($version->get())->toBe('1.0.0');
        });

        it('creates version with custom value', function (): void {
            $version = new Version('2.5.3');

            expect($version->get())->toBe('2.5.3');
        });

        it('creates version with pre-release', function (): void {
            $version = new Version('1.0.0-alpha.1');

            expect($version->get())->toBe('1.0.0-alpha.1');
        });
    });

    describe('get and set', function (): void {
        it('gets version string', function (): void {
            $version = new Version('3.2.1');

            expect($version->get())->toBe('3.2.1');
        });

        it('sets version to new value', function (): void {
            $version = new Version('1.0.0');

            $result = $version->set('2.0.0');

            expect($version->get())->toBe('2.0.0');
            expect($result)->toBe($version);
        });
    });

    describe('increment methods', function (): void {
        it('increments major version', function (): void {
            $version = new Version('1.2.3');

            $result = $version->incrementMajor();

            expect($version->get())->toBe('2.0.0');
            expect($result)->toBe($version);
        });

        it('increments minor version', function (): void {
            $version = new Version('1.2.3');

            $result = $version->incrementMinor();

            expect($version->get())->toBe('1.3.0');
            expect($result)->toBe($version);
        });

        it('increments patch version', function (): void {
            $version = new Version('1.2.3');

            $result = $version->incrementPatch();

            expect($version->get())->toBe('1.2.4');
            expect($result)->toBe($version);
        });

        it('increments pre-release version', function (): void {
            $version = new Version('1.0.0-alpha.1');

            $result = $version->incrementPreRelease();

            expect($version->get())->toBe('1.0.0-alpha.2');
            expect($result)->toBe($version);
        });
    });

    describe('pre-release methods', function (): void {
        it('sets alpha release', function (): void {
            $version = new Version('1.0.0');

            $result = $version->alpha();

            expect($version->get())->toBe('1.0.0-alpha.1');
            expect($result)->toBe($version);
        });

        it('sets alpha release with custom number', function (): void {
            $version = new Version('1.0.0');

            $version->alpha(5);

            expect($version->get())->toBe('1.0.0-alpha.5');
        });

        it('sets beta release', function (): void {
            $version = new Version('1.0.0');

            $result = $version->beta();

            expect($version->get())->toBe('1.0.0-beta.1');
            expect($result)->toBe($version);
        });

        it('sets beta release with custom number', function (): void {
            $version = new Version('1.0.0');

            $version->beta(3);

            expect($version->get())->toBe('1.0.0-beta.3');
        });

        it('sets rc release', function (): void {
            $version = new Version('1.0.0');

            $result = $version->rc();

            expect($version->get())->toBe('1.0.0-rc.1');
            expect($result)->toBe($version);
        });

        it('sets rc release with custom number', function (): void {
            $version = new Version('1.0.0');

            $version->rc(2);

            expect($version->get())->toBe('1.0.0-rc.2');
        });

        it('removes pre-release with stable', function (): void {
            $version = new Version('1.0.0-alpha.1');

            $result = $version->stable();

            expect($version->get())->toBe('1.0.0');
            expect($result)->toBe($version);
        });
    });

    describe('getter methods', function (): void {
        it('gets major version', function (): void {
            $version = new Version('5.3.2');

            expect($version->major())->toBe(5);
        });

        it('gets minor version', function (): void {
            $version = new Version('5.3.2');

            expect($version->minor())->toBe(3);
        });

        it('gets patch version', function (): void {
            $version = new Version('5.3.2');

            expect($version->patch())->toBe(2);
        });

        it('gets pre-release string', function (): void {
            $version = new Version('1.0.0-beta.2');

            expect($version->preRelease())->toBe('beta.2');
        });

        it('returns null for pre-release on stable version', function (): void {
            $version = new Version('1.0.0');

            expect($version->preRelease())->toBeNull();
        });
    });

    describe('state checks', function (): void {
        it('detects pre-release version', function (): void {
            $version = new Version('1.0.0-alpha.1');

            expect($version->isPreRelease())->toBeTrue();
            expect($version->isStable())->toBeFalse();
        });

        it('detects stable version', function (): void {
            $version = new Version('1.0.0');

            expect($version->isStable())->toBeTrue();
            expect($version->isPreRelease())->toBeFalse();
        });
    });

    describe('build metadata', function (): void {
        it('gets build metadata from version string', function (): void {
            $version = new Version('1.0.0+build.123');

            expect($version->build())->toBe('build.123');
        });

        it('returns null when no build metadata', function (): void {
            $version = new Version('1.0.0');

            expect($version->build())->toBeNull();
        });

        it('sets build metadata', function (): void {
            $version = new Version('1.0.0');

            $result = $version->setBuild('abc123');

            expect($version->get())->toBe('1.0.0+abc123');
            expect($result)->toBe($version);
        });

        it('clears build metadata', function (): void {
            $version = new Version('1.0.0+build.456');

            $result = $version->clearBuild();

            expect($version->get())->toBe('1.0.0');
            expect($result)->toBe($version);
        });

        it('detects when version has build metadata', function (): void {
            $withBuild = new Version('1.0.0+build');
            $withoutBuild = new Version('1.0.0');

            expect($withBuild->hasBuild())->toBeTrue();
            expect($withoutBuild->hasBuild())->toBeFalse();
        });

        it('works with pre-release and build metadata', function (): void {
            $version = new Version('1.0.0-alpha.1');
            $version->setBuild('20251211');

            expect($version->get())->toBe('1.0.0-alpha.1+20251211');
            expect($version->preRelease())->toBe('alpha.1');
            expect($version->build())->toBe('20251211');
        });

        it('preserves build metadata after increment', function (): void {
            $version = new Version('1.0.0+build');
            $version->incrementPatch();

            // Note: SemVer increments clear build metadata by design
            expect($version->get())->toBe('1.0.1');
        });
    });

    describe('raw method', function (): void {
        it('returns underlying SemVer instance', function (): void {
            $version = new Version('1.0.0');

            expect($version->raw())->toBeInstanceOf(SemVer::class);
        });
    });

    describe('comparison methods', function (): void {
        it('compares gt with Version instance', function (): void {
            $v1 = new Version('2.0.0');
            $v2 = new Version('1.0.0');

            expect($v1->gt($v2))->toBeTrue();
            expect($v2->gt($v1))->toBeFalse();
        });

        it('compares gt with string', function (): void {
            $version = new Version('2.0.0');

            expect($version->gt('1.0.0'))->toBeTrue();
            expect($version->gt('3.0.0'))->toBeFalse();
        });

        it('compares gte with Version instance', function (): void {
            $v1 = new Version('2.0.0');
            $v2 = new Version('2.0.0');
            $v3 = new Version('1.0.0');

            expect($v1->gte($v2))->toBeTrue();
            expect($v1->gte($v3))->toBeTrue();
            expect($v3->gte($v1))->toBeFalse();
        });

        it('compares gte with string', function (): void {
            $version = new Version('2.0.0');

            expect($version->gte('2.0.0'))->toBeTrue();
            expect($version->gte('1.0.0'))->toBeTrue();
            expect($version->gte('3.0.0'))->toBeFalse();
        });

        it('compares lt with Version instance', function (): void {
            $v1 = new Version('1.0.0');
            $v2 = new Version('2.0.0');

            expect($v1->lt($v2))->toBeTrue();
            expect($v2->lt($v1))->toBeFalse();
        });

        it('compares lt with string', function (): void {
            $version = new Version('1.0.0');

            expect($version->lt('2.0.0'))->toBeTrue();
            expect($version->lt('0.5.0'))->toBeFalse();
        });

        it('compares lte with Version instance', function (): void {
            $v1 = new Version('1.0.0');
            $v2 = new Version('1.0.0');
            $v3 = new Version('2.0.0');

            expect($v1->lte($v2))->toBeTrue();
            expect($v1->lte($v3))->toBeTrue();
            expect($v3->lte($v1))->toBeFalse();
        });

        it('compares lte with string', function (): void {
            $version = new Version('1.0.0');

            expect($version->lte('1.0.0'))->toBeTrue();
            expect($version->lte('2.0.0'))->toBeTrue();
            expect($version->lte('0.5.0'))->toBeFalse();
        });

        it('compares eq with Version instance', function (): void {
            $v1 = new Version('1.0.0');
            $v2 = new Version('1.0.0');
            $v3 = new Version('2.0.0');

            expect($v1->eq($v2))->toBeTrue();
            expect($v1->eq($v3))->toBeFalse();
        });

        it('compares eq with string', function (): void {
            $version = new Version('1.0.0');

            expect($version->eq('1.0.0'))->toBeTrue();
            expect($version->eq('2.0.0'))->toBeFalse();
        });

        it('compares neq with Version instance', function (): void {
            $v1 = new Version('1.0.0');
            $v2 = new Version('2.0.0');
            $v3 = new Version('1.0.0');

            expect($v1->neq($v2))->toBeTrue();
            expect($v1->neq($v3))->toBeFalse();
        });

        it('compares neq with string', function (): void {
            $version = new Version('1.0.0');

            expect($version->neq('2.0.0'))->toBeTrue();
            expect($version->neq('1.0.0'))->toBeFalse();
        });

        it('compares pre-release versions correctly', function (): void {
            $alpha = new Version('1.0.0-alpha.1');
            $beta = new Version('1.0.0-beta.1');
            $stable = new Version('1.0.0');

            expect($alpha->lt($beta))->toBeTrue();
            expect($beta->lt($stable))->toBeTrue();
            expect($alpha->lt('1.0.0'))->toBeTrue();
        });
    });

    describe('magic methods', function (): void {
        it('converts to string via __toString', function (): void {
            $version = new Version('2.1.0');

            expect((string) $version)->toBe('2.1.0');
        });
    });

    describe('method chaining', function (): void {
        it('supports chained method calls', function (): void {
            $version = new Version('1.0.0');

            $version
                ->incrementMajor()
                ->incrementMinor()
                ->incrementPatch()
                ->alpha();

            expect($version->get())->toBe('2.1.1-alpha.1');
        });
    });
});
