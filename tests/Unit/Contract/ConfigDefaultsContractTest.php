<?php

declare(strict_types=1);

namespace Vi\Validation\Tests\Unit\Contract;

use PHPUnit\Framework\TestCase;

/**
 * The published config keys, their environment variables and defaults are public API
 * (docs/public-api.md, README "Configuration"). Renaming or removing a key, renaming its
 * environment variable or changing its default is a breaking change.
 */
final class ConfigDefaultsContractTest extends TestCase
{
    /**
     * Dot key => [environment variable or null, default].
     *
     * @var array<string, array{0: string|null, 1: mixed}>
     */
    private const CONTRACT = [
        'mode' => [null, 'parallel'],
        'cache.enabled' => ['FAST_VALIDATION_CACHE', true],
        'cache.driver' => ['FAST_VALIDATION_CACHE_DRIVER', 'array'],
        'cache.ttl' => ['FAST_VALIDATION_CACHE_TTL', 3600],
        'cache.path' => [null, 'storage/framework/validation/cache'],
        'compilation.precompile' => ['FAST_VALIDATION_PRECOMPILE', false],
        'compilation.cache_path' => [null, 'storage/framework/validation/compiled'],
        'security.signing_key' => ['FAST_VALIDATION_SIGNING_KEY', null],
        'performance.fail_fast' => ['FAST_VALIDATION_FAIL_FAST', false],
        'performance.max_errors' => ['FAST_VALIDATION_MAX_ERRORS', 100],
        'performance.error_mode' => ['FAST_VALIDATION_ERROR_MODE', 'all'],
        'performance.fast_path_rules' => ['FAST_VALIDATION_FAST_PATH', true],
        'performance.batch_lookups' => ['FAST_VALIDATION_BATCH_LOOKUPS', false],
        'performance.lookahead' => ['FAST_VALIDATION_LOOKAHEAD', 1000],
        'localization.locale' => ['FAST_VALIDATION_LOCALE', 'en'],
        'localization.fallback_locale' => ['FAST_VALIDATION_FALLBACK_LOCALE', 'en'],
        'runtime.pooling' => ['FAST_VALIDATION_POOLING', false],
        'runtime.pool_size' => ['FAST_VALIDATION_POOL_SIZE', 10],
        'runtime.auto_detect' => ['FAST_VALIDATION_AUTO_DETECT', true],
    ];

    private static function configPath(): string
    {
        return dirname(__DIR__, 3) . '/config/fast-validation.php';
    }

    /**
     * Load the config file as Laravel would with no environment variables set.
     *
     * The file calls env() and storage_path(). Laravel's env() needs packages that aren't
     * installed here, and storage_path() needs laravel/framework, so the file is evaluated in a
     * namespace that defines both: unqualified function calls resolve there first.
     *
     * @return array<string, mixed>
     */
    private static function loadConfig(): array
    {
        $namespace = __NAMESPACE__ . '\\ConfigSandbox';

        if (!function_exists($namespace . '\\env')) {
            eval(<<<PHP
                namespace $namespace;
                function env(string \$key, mixed \$default = null): mixed { return \$default; }
                function storage_path(string \$path = ''): string { return 'storage/' . \$path; }
                PHP);
        }

        $source = (string) file_get_contents(self::configPath());
        $source = preg_replace('/^<\?php\s*(declare\(strict_types=1\);)?/', '', $source, 1);

        return eval("namespace $namespace; ?><?php " . $source);
    }

    /**
     * @param array<string, mixed> $config
     * @return array<string, mixed>
     */
    private static function flatten(array $config, string $prefix = ''): array
    {
        $flat = [];
        foreach ($config as $key => $value) {
            $path = $prefix . $key;
            if (is_array($value)) {
                $flat += self::flatten($value, $path . '.');
            } else {
                $flat[$path] = $value;
            }
        }

        return $flat;
    }

    public function testKeysAndDefaultsMatchTheContract(): void
    {
        foreach (self::CONTRACT as [$env]) {
            if ($env !== null && getenv($env) !== false) {
                $this->markTestSkipped("$env is set in the environment; defaults can't be checked.");
            }
        }

        $flat = self::flatten(self::loadConfig());

        $this->assertSame(array_keys(self::CONTRACT), array_keys($flat), 'Config keys changed.');
        foreach (self::CONTRACT as $key => [, $default]) {
            $this->assertSame($default, $flat[$key], "Default of '$key' changed.");
        }
    }

    public function testEachKeyReadsItsDocumentedEnvironmentVariable(): void
    {
        $source = (string) file_get_contents(self::configPath());

        foreach (self::CONTRACT as $key => [$env]) {
            if ($env === null) {
                continue;
            }
            $this->assertMatchesRegularExpression(
                "/env\\('" . preg_quote($env, '/') . "'/",
                $source,
                "'$key' must be overridable via $env."
            );
        }
    }

    public function testReadmeDocumentsEveryKey(): void
    {
        $readme = (string) file_get_contents(dirname(__DIR__, 3) . '/README.md');

        foreach (self::CONTRACT as $key => [$env]) {
            $row = '| `' . $key . '` | ' . ($env === null ? '—' : '`' . $env . '`') . ' |';
            $this->assertStringContainsString($row, $readme, "README's Configuration table must document '$key'.");
        }
    }
}
