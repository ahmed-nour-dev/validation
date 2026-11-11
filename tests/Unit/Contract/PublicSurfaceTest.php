<?php

declare(strict_types=1);

namespace Vi\Validation\Tests\Unit\Contract;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionMethod;

/**
 * Keeps the public API listed in docs/public-api.md from disappearing by accident.
 *
 * If a test here fails because you removed or renamed something on purpose, that is a breaking
 * change: update docs/public-api.md and the CHANGELOG, and follow the deprecation policy.
 */
final class PublicSurfaceTest extends TestCase
{
    /**
     * Public classes and the public methods docs/public-api.md promises on each.
     *
     * @var array<class-string, list<string>>
     */
    private const PUBLIC_API = [
        // Standalone API
        \Vi\Validation\Validator::class => ['schema', 'fromRules'],
        \Vi\Validation\Schema\SchemaBuilder::class => ['field', 'compile'],
        \Vi\Validation\Schema\FieldDefinition::class => [
            'field', 'compile', 'rules', 'when', 'required', 'nullable', 'email', 'string',
            'integer', 'min', 'max', 'in', 'regex', 'requiredIf', 'excludeIf',
        ],
        \Vi\Validation\SchemaValidator::class => [
            '__construct', 'build', 'validate', 'validateMany', 'stream', 'each', 'failures',
            'report', 'firstFailure', 'firstFailureWithIndex', 'allValid', 'setErrorMode',
            'enableBatchLookups', 'disableBatchLookups', 'batchesLookups', 'batchedQueryCount',
            'diagnostics', 'warm', 'usesNative', 'getSchema', 'getEngine', 'getCompiler',
            'getMessageResolver',
        ],
        \Vi\Validation\Execution\CompiledSchema::class => ['validate', 'fingerprint', 'getCompileTimeMs'],
        \Vi\Validation\Execution\ValidatorEngine::class => [
            '__construct', 'validate', 'setFailFast', 'isFailFast', 'setMaxErrors', 'getMaxErrors',
            'setErrorMode', 'getErrorMode', 'setMessageResolver', 'getMessageResolver',
            'setDatabaseValidator', 'getDatabaseValidator', 'setPasswordHasher',
        ],
        \Vi\Validation\Execution\ValidationResult::class => [
            'isValid', 'data', 'validated', 'errors', 'messages', 'allMessages', 'firstMessage',
            'first', 'errorCount', 'errorCountsByField', 'failedFields', 'isCountOnly', '__toString',
        ],
        \Vi\Validation\Execution\ValidationReport::class => ['passedRows', 'allValid', 'isTruncated', 'summary'],
        \Vi\Validation\Execution\ValidationFailure::class => ['messages', 'errors'],
        \Vi\Validation\Execution\ChunkedValidator::class => [
            '__construct', 'validateInChunks', 'streamChunks', 'streamFailures', 'countFailures',
        ],
        \Vi\Validation\Execution\BatchValidationResult::class => [
            'all', 'count', 'getIterator', 'allValid', 'failures', 'failureCount',
        ],
        \Vi\Validation\Execution\ErrorMode::class => [],

        // Laravel integration
        \Vi\Validation\Laravel\Facades\FastValidator::class => [],
        \Vi\Validation\Laravel\FastValidatorFactory::class => [
            '__construct', 'make', 'compile', 'precompile', 'diagnose', 'cacheStats', 'clearCompiled',
            'pruneCompiled', 'setDatabaseValidator', 'setPasswordHasher', 'getCompiler', 'getCache',
        ],
        \Vi\Validation\Laravel\FastValidatorWrapper::class => [
            'fails', 'passes', 'errors', 'validated', 'validate', 'failed', 'getMessageBag', 'getTranslator', 'after',
            'sometimes', 'getData', 'setData', 'getRules', 'setRules', 'addRules', 'safe',
            'stopOnFirstFailure', 'getCustomMessages', 'setCustomMessages', 'getCustomAttributes',
            'setCustomAttributes', 'stream', 'each', 'failures', 'firstFailure',
            'firstFailureWithIndex', 'allValid', 'validateMany', 'report', 'setErrorMode',
            'diagnostics', 'getSchemaValidator', 'getRawData',
        ],
        \Vi\Validation\Laravel\FastValidationServiceProvider::class => ['register', 'boot'],
        \Vi\Validation\Laravel\Octane\OctaneValidatorProvider::class => ['register', 'boot'],

        // Extension points
        \Vi\Validation\Rules\RuleInterface::class => ['validate'],
        \Vi\Validation\Rules\ClosureRule::class => ['__construct', 'validate'],
        \Vi\Validation\Execution\ValidationContext::class => ['getValue', 'hasValue', 'getData'],
        \Vi\Validation\Rules\DatabaseValidatorInterface::class => ['exists', 'unique'],
        \Vi\Validation\Rules\BatchDatabaseValidatorInterface::class => ['matchingValues'],
        \Vi\Validation\Rules\PasswordHasherInterface::class => ['check'],
        \Vi\Validation\Rules\RuleId::class => [],
        \Vi\Validation\Rules\RuleRegistry::class => ['registerBuiltInRules', 'has'],
        \Vi\Validation\Messages\TranslatorInterface::class => ['get', 'getLocale', 'setLocale'],
        \Vi\Validation\Messages\Translator::class => [
            '__construct', 'get', 'getLocale', 'setLocale', 'addMessages', 'setFallbackLocale',
            'loadFromPath', 'setLangPath',
        ],
        \Vi\Validation\Messages\MessageResolver::class => [
            '__construct', 'resolve', 'setCustomMessages', 'setCustomAttributes', 'getTranslator',
        ],
        \Vi\Validation\Cache\SchemaCacheInterface::class => ['get', 'put', 'has', 'forget', 'flush'],
        \Vi\Validation\Cache\ArraySchemaCache::class => ['get', 'put', 'has', 'forget', 'flush'],
        \Vi\Validation\Cache\FileSchemaCache::class => ['__construct', 'get', 'put', 'has', 'forget', 'flush'],

        // Native compilation and diagnostics
        \Vi\Validation\Compilation\NativeCompiler::class => ['canCompile', 'findUnsupportedRules', 'compile'],
        \Vi\Validation\Compilation\ValidatorCompiler::class => [
            '__construct', 'writeNativeFor', 'loadNativeFor', 'nativeKeyFor', 'nativeRepository',
            'isPrecompileEnabled', 'clearNative', 'pruneNative',
        ],
        \Vi\Validation\Compilation\NativeArtifactRepository::class => [
            'store', 'load', 'loadFor', 'has', 'clear', 'prune', 'stats', 'flushMemory',
        ],
        \Vi\Validation\Compilation\SchemaFingerprint::class => [],
        \Vi\Validation\Diagnostics\SchemaDiagnostics::class => ['toArray', '__toString'],

        // Runtime
        \Vi\Validation\Runtime\RuntimeAwareInterface::class => [
            'onWorkerStart', 'onRequestStart', 'onRequestEnd', 'onWorkerStop',
        ],
        \Vi\Validation\Runtime\ValidatorPool::class => [
            '__construct', 'acquire', 'release', 'withValidator', 'onWorkerStart', 'onWorkerStop',
            'getPoolSize', 'getMaxSize', 'getCheckedOutCount',
        ],
        \Vi\Validation\Runtime\StatelessValidator::class => ['__construct', 'validate'],
        \Vi\Validation\Runtime\ContextManager::class => [
            'createContext', 'getMessageResolver', 'setCustomMessages', 'setCustomAttributes', 'reset',
        ],
        \Vi\Validation\Runtime\Workers\SwooleAdapter::class => ['__construct', 'getPool'],
        \Vi\Validation\Runtime\Workers\RoadRunnerAdapter::class => ['__construct', 'getPool', 'handleRequest'],

        // Exceptions
        \Vi\Validation\Laravel\UnsupportedRuleException::class => [],
        \Vi\Validation\Compilation\UnsupportedNativeRuleException::class => [],
        \Vi\Validation\Compilation\NativeArtifactException::class => [],
    ];

    /**
     * Public readonly properties promised on value objects.
     *
     * @var array<class-string, list<string>>
     */
    private const PUBLIC_PROPERTIES = [
        \Vi\Validation\Execution\ValidationReport::class => [
            'rowsProcessed', 'failedRows', 'errorCount', 'failures', 'errorCountsByField', 'maxStoredFailures',
        ],
        \Vi\Validation\Execution\ValidationFailure::class => ['index', 'key', 'result'],
        \Vi\Validation\Compilation\SchemaFingerprint::class => ['schemaHash', 'artifactKey', 'stable', 'unstableReasons'],
        \Vi\Validation\Diagnostics\SchemaDiagnostics::class => [
            'schemaHash', 'artifactKey', 'stable', 'unstableReasons', 'fieldCount', 'ruleCount', 'fields',
            'nativeCompatible', 'unsupportedRules', 'artifactStatus', 'artifactPath', 'strategy',
            'strategyReason', 'compilerVersion', 'phpVersionId', 'compileTimeMs',
        ],
    ];

    /**
     * @return iterable<string, array{class-string, list<string>}>
     */
    public static function publicClasses(): iterable
    {
        foreach (self::PUBLIC_API as $class => $methods) {
            yield $class => [$class, $methods];
        }
    }

    /**
     * @param class-string $class
     * @param list<string> $methods
     */
    #[DataProvider('publicClasses')]
    public function testPublicClassKeepsItsDocumentedMethods(string $class, array $methods): void
    {
        $this->assertTrue(
            class_exists($class) || interface_exists($class) || enum_exists($class),
            "$class is public API and must exist."
        );

        $reflection = new ReflectionClass($class);
        $this->assertFalse(
            self::isInternal($reflection->getDocComment()),
            "$class is public API but is tagged @internal."
        );

        foreach ($methods as $method) {
            $this->assertTrue($reflection->hasMethod($method), "$class::$method() is public API and must exist.");

            $m = new ReflectionMethod($class, $method);
            $this->assertTrue($m->isPublic(), "$class::$method() is public API and must stay public.");
            $this->assertFalse(
                self::isInternal($m->getDocComment()),
                "$class::$method() is public API but is tagged @internal."
            );
        }
    }

    public function testValueObjectsKeepTheirPublicProperties(): void
    {
        foreach (self::PUBLIC_PROPERTIES as $class => $properties) {
            $reflection = new ReflectionClass($class);
            foreach ($properties as $property) {
                $this->assertTrue($reflection->hasProperty($property), "$class::\$$property must exist.");
                $p = $reflection->getProperty($property);
                $this->assertTrue($p->isPublic() && $p->isReadOnly(), "$class::\$$property must stay public readonly.");
            }
        }
    }

    public function testErrorModeValuesAreStable(): void
    {
        $this->assertSame(
            ['All' => 'all', 'FirstPerField' => 'first_per_field', 'FirstPerRow' => 'first_per_row', 'CountOnly' => 'count_only'],
            array_combine(
                array_map(static fn ($c) => $c->name, \Vi\Validation\Execution\ErrorMode::cases()),
                array_map(static fn ($c) => $c->value, \Vi\Validation\Execution\ErrorMode::cases()),
            )
        );
    }

    public function testExceptionsKeepTheirParentClasses(): void
    {
        $this->assertTrue(is_subclass_of(\Vi\Validation\Laravel\UnsupportedRuleException::class, \InvalidArgumentException::class));
        $this->assertTrue(is_subclass_of(\Vi\Validation\Compilation\UnsupportedNativeRuleException::class, \RuntimeException::class));
        $this->assertTrue(is_subclass_of(\Vi\Validation\Compilation\NativeArtifactException::class, \RuntimeException::class));
    }

    /**
     * Every class in src/ must be either listed as public above (and in docs/public-api.md) or
     * tagged @internal, so a new class is never public by accident.
     */
    public function testEveryClassIsEitherPublicOrInternal(): void
    {
        $classes = self::sourceClasses();
        $this->assertGreaterThan(count(self::PUBLIC_API), count($classes), 'src/ scan found too few classes.');

        $unclassified = [];
        foreach ($classes as $class) {
            if (array_key_exists($class, self::PUBLIC_API)) {
                continue;
            }
            if (!self::isInternal((new ReflectionClass($class))->getDocComment())) {
                $unclassified[] = $class;
            }
        }

        $this->assertSame(
            [],
            $unclassified,
            "These classes are neither listed as public API nor tagged @internal. Tag them @internal, "
            . "or list them in docs/public-api.md and in " . self::class . "::PUBLIC_API."
        );
    }

    public function testConcreteRuleClassesAreInternal(): void
    {
        foreach (self::sourceClasses() as $class) {
            if (str_starts_with($class, 'Vi\\Validation\\Rules\\')
                && str_ends_with($class, 'Rule')
                && $class !== \Vi\Validation\Rules\ClosureRule::class
            ) {
                $this->assertTrue(
                    self::isInternal((new ReflectionClass($class))->getDocComment()),
                    "$class must be tagged @internal: rules are public by name, not by class."
                );
            }
        }
    }

    /**
     * @return list<class-string>
     */
    private static function sourceClasses(): array
    {
        $src = dirname(__DIR__, 3) . '/src';
        $classes = [];
        $files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($src, \FilesystemIterator::SKIP_DOTS));
        foreach ($files as $file) {
            if ($file->getExtension() !== 'php') {
                continue;
            }
            $relative = substr($file->getPathname(), strlen($src) + 1, -4);
            $class = 'Vi\\Validation\\' . str_replace('/', '\\', $relative);
            // Laravel-only classes still load: their parents come from illuminate/support.
            if (class_exists($class) || interface_exists($class) || enum_exists($class) || trait_exists($class)) {
                $classes[] = $class;
            }
        }
        sort($classes);

        return $classes;
    }

    private static function isInternal(string|false $docComment): bool
    {
        return $docComment !== false && preg_match('/@internal\b/', $docComment) === 1;
    }
}
