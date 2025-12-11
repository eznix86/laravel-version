<?php

declare(strict_types=1);

// @formatter:off
// phpcs:ignoreFile

/**
 * IDE helper file for Pest PHP and Rector.
 * This file provides autocompletion and type hints for Intelephense.
 */

namespace Rector\Config {
    class RectorConfig
    {
        public static function configure(): \Rector\Configuration\RectorConfigBuilder {}
    }
}

namespace Rector\Configuration {
    class RectorConfigBuilder
    {
        /** @param list<string> $paths */
        public function withPaths(array $paths): self {}
        /** @param list<string> $skip */
        public function withSkip(array $skip): self {}
        public function withPhpSets(bool $php83 = false, bool $php82 = false, bool $php81 = false, bool $php80 = false): self {}
        /** @param list<string> $sets */
        public function withSets(array $sets): self {}
        public function withPreparedSets(bool $deadCode = false, bool $codeQuality = false, bool $typeDeclarations = false, bool $earlyReturn = false): self {}
        public function withImportNames(bool $importNames = true, bool $importDocBlockNames = true, bool $importShortClasses = true, bool $removeUnusedImports = false): self {}
    }
}

namespace Rector\Set\ValueObject {
    class SetList
    {
        public const CODE_QUALITY = 'code-quality';
        public const DEAD_CODE = 'dead-code';
        public const EARLY_RETURN = 'early-return';
        public const TYPE_DECLARATION = 'type-declaration';
    }
}

namespace {
    /**
     * @template TValue
     *
     * @param  TValue  $value
     * @return \Pest\Expectation<TValue>
     */
    function expect(mixed $value): \Pest\Expectation {}

    /**
     * @param  \Closure|string  $description
     * @return \Pest\PendingCalls\TestCall|\Pest\Support\HigherOrderTapProxy
     */
    function it(string $description, ?\Closure $closure = null): \Pest\PendingCalls\TestCall {}

    /**
     * @param  \Closure|string  $description
     * @return \Pest\PendingCalls\TestCall|\Pest\Support\HigherOrderTapProxy
     */
    function test(string $description, ?\Closure $closure = null): \Pest\PendingCalls\TestCall {}

    function describe(string $description, \Closure $closure): \Pest\PendingCalls\DescribeCall {}

    function beforeEach(\Closure $closure): \Pest\PendingCalls\BeforeEachCall {}

    function afterEach(\Closure $closure): \Pest\PendingCalls\AfterEachCall {}

    function beforeAll(\Closure $closure): void {}

    function afterAll(\Closure $closure): void {}

    /**
     * @param  class-string  $class
     */
    function uses(string ...$class): \Pest\PendingCalls\UsesCall {}

    function pest(): \Pest\PendingCalls\Concerns\Extendable {}
}
