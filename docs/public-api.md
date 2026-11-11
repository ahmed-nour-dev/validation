# Public API, versioning and deprecation policy

This page says which parts of `ahmednour/validation` you can build on, how breaking changes are
released, and how long deprecated APIs keep working.

- **Public API**: everything listed in [What is public](#what-is-public). Changes to it follow the
  [versioning rules](#versioning) below.
- **Internal**: everything else, including every class or method tagged `@internal`. Internal
  code can change or disappear in any release, including a patch release. Don't use it from
  application code. If you need something that is only internal, open an issue so it can be made
  public deliberately.

`tests/Unit/Contract/` checks the most important public entry points, return shapes and
configuration defaults, so an accidental break fails CI.

## What is public

All classes are in the `Vi\Validation\` namespace (written without the prefix below). For a
public class, its public methods, public constants and public properties are covered, except
members tagged `@internal`. Parameter names are covered too: they can be passed as named
arguments.

### Standalone API

| Class | Public surface |
| :--- | :--- |
| `Validator` | `schema()`, `fromRules()` |
| `Schema\SchemaBuilder` | `field()`, `compile()` |
| `Schema\FieldDefinition` | every rule method (`required()`, `email()`, `min()`, ...), `rules()`, `when()`, `field()`, `compile()` |
| `SchemaValidator` | constructor, `build()`, `validate()`, `validateMany()`, `stream()`, `each()`, `failures()`, `report()`, `firstFailure()`, `firstFailureWithIndex()`, `allValid()`, `setErrorMode()`, `enableBatchLookups()`, `disableBatchLookups()`, `batchesLookups()`, `batchedQueryCount()`, `diagnostics()`, `warm()`, `usesNative()`, `getSchema()`, `getEngine()`, `getCompiler()`, `getMessageResolver()` |
| `Execution\CompiledSchema` | `validate()`, `fingerprint()`, `getCompileTimeMs()` |
| `Execution\ValidatorEngine` | constructor, `validate()`, and the getters/setters for fail-fast, max errors, error mode, message resolver, database validator and password hasher |
| `Execution\ValidationResult` | `isValid()`, `data()`, `validated()`, `errors()`, `messages()`, `allMessages()`, `firstMessage()`, `first()`, `errorCount()`, `errorCountsByField()`, `failedFields()`, `isCountOnly()`, `__toString()` |
| `Execution\ValidationReport` | its readonly properties, `passedRows()`, `allValid()`, `isTruncated()`, `summary()` |
| `Execution\ValidationFailure` | `index`, `key`, `result`, `messages()`, `errors()` |
| `Execution\ChunkedValidator` | constructor, `validateInChunks()`, `streamChunks()`, `streamFailures()`, `countFailures()` |
| `Execution\BatchValidationResult` | `all()`, `count()`, iteration, `allValid()`, `failures()`, `failureCount()` |
| `Execution\ErrorMode` | the four cases and their string values (`all`, `first_per_field`, `first_per_row`, `count_only`) |

Constructors of the result objects (`ValidationResult`, `ValidationReport`, `ValidationFailure`,
`BatchValidationResult`, `Diagnostics\SchemaDiagnostics`) are internal: you receive these
objects, you don't build them.

### Laravel integration

| Item | Public surface |
| :--- | :--- |
| `Laravel\Facades\FastValidator` | the facade and `FastValidator::make($data, $rules, $messages, $attributes)` |
| `Laravel\FastValidatorFactory` | constructor (taking the config array below), `make()`, `compile()`, `precompile()`, `diagnose()`, `cacheStats()`, `clearCompiled()`, `pruneCompiled()`, `setDatabaseValidator()`, `setPasswordHasher()`, `getCompiler()`, `getCache()` |
| `Laravel\FastValidatorWrapper` | everything it implements from Laravel's `Validator` contract, plus `stream()`, `each()`, `failures()`, `firstFailure()`, `firstFailureWithIndex()`, `allValid()`, `validateMany()`, `report()`, `setErrorMode()`, `diagnostics()`, `getSchemaValidator()`, `getRawData()`, `getTranslator()`, `safe()` |
| `Laravel\FastValidationServiceProvider` | the class name, the container bindings `FastValidatorFactory::class` and `'fast.validator'`, and the `config` publish tag |
| `Laravel\Octane\OctaneValidatorProvider` | the class name and the Octane events it listens to |
| Override mode | with `mode` set to `override`, `Validator::make()` returns a validator that behaves as documented in the README. The adapter class behind it is internal. |
| Rule strings | Laravel rule names and parameter grammar (`'required\|email'`, `in:"a,b",c`), and Laravel rule objects, as listed in [docs/rules.md](rules.md) |

### Configuration

Every key in [`config/fast-validation.php`](../config/fast-validation.php), its environment
variable and its default, as listed in the Configuration table in the
[README](../README.md). The same array shape is accepted by
`FastValidatorFactory` and `SchemaValidator::build()`.

Renaming or removing a key, renaming its environment variable, or changing its default is a
breaking change. Adding a key is not.

### Extension points

| Interface / class | Use |
| :--- | :--- |
| `Rules\RuleInterface` | implement it to pass your own rule objects to `FieldDefinition::rules()` |
| `Rules\ClosureRule` | wrap a Laravel-style `function ($attribute, $value, $fail)` closure |
| `Execution\ValidationContext` | inside a rule, only `getValue()`, `hasValue()` and `getData()` |
| `Rules\DatabaseValidatorInterface`, `Rules\BatchDatabaseValidatorInterface` | back `exists`/`unique` outside Laravel |
| `Rules\PasswordHasherInterface` | back `current_password` outside Laravel |
| `Rules\RuleId` | the built-in rule names |
| `Rules\RuleRegistry` | constructor, `registerBuiltInRules()`, `has()`, and passing an instance to `Validator::fromRules()` or `FastValidatorFactory` |
| `Messages\TranslatorInterface`, `Messages\Translator` | message catalogs and locales |
| `Messages\MessageResolver` | custom messages and attribute names |
| `Cache\SchemaCacheInterface`, `Cache\ArraySchemaCache`, `Cache\FileSchemaCache` | compiled-schema caches |

Adding a method to a public interface is a breaking change for anyone who implements it, so it
is treated as one (see [Versioning](#versioning)).

Registering your own rule classes (`RuleRegistry::register()` with the `#[RuleName]` attribute)
is not public yet. Use `ClosureRule`, a `RuleInterface` object, or a Laravel rule object.

### Native compilation and diagnostics

| Class | Public surface |
| :--- | :--- |
| `Compilation\NativeCompiler` | `canCompile()`, `findUnsupportedRules()`, `compile()` (that it returns PHP source or throws `UnsupportedNativeRuleException`; the source itself is internal) |
| `Compilation\ValidatorCompiler` | constructor, `writeNativeFor()`, `loadNativeFor()`, `nativeKeyFor()`, `nativeRepository()`, `isPrecompileEnabled()`, `clearNative()`, `pruneNative()` |
| `Compilation\NativeArtifactRepository` | the methods described in [native-compilation.md](native-compilation.md): `store()`, `load()`, `loadFor()`, `has()`, `clear()`, `prune()`, `stats()`, `flushMemory()` |
| `Compilation\SchemaFingerprint` | `schemaHash`, `artifactKey`, `stable`, `unstableReasons` |
| `Diagnostics\SchemaDiagnostics` | its readonly properties, `toArray()` and its keys |

Whether a given rule can be compiled natively is not a promise. A release may add native
support for a rule, or move one back to the engine. Either way validation results stay the same.

### Runtime

`Runtime\ValidatorPool`, `Runtime\StatelessValidator`, `Runtime\ContextManager`,
`Runtime\RuntimeAwareInterface`, `Runtime\Workers\SwooleAdapter` and
`Runtime\Workers\RoadRunnerAdapter`, including the worker-safety guarantees in the README.

### Exceptions

| Exception | Thrown when |
| :--- | :--- |
| `Laravel\UnsupportedRuleException` | a rule string or rule array names a rule that doesn't exist |
| `Compilation\UnsupportedNativeRuleException` | `NativeCompiler::compile()` meets a rule it can't inline |
| `Compilation\NativeArtifactException` | `NativeArtifactRepository` fails to write or verify an artifact |

The exception class and its parent class are public. Exception messages are not: don't parse
them.

### Return-value contracts

- `ValidationResult::errors()` returns `array<string, list<array{rule: string, params: array, message: ?string}>>`
  keyed by field. Wildcard fields use concrete keys (`items.0.sku`).
- `ValidationResult::messages()` returns `array<string, list<string>>` keyed by field.
- `RuleInterface::validate()` returns `null` on success, or an array on failure with a `rule`
  key (the message key) and optional `params` and `message` keys.
- `ValidationResult::validated()` is `data()` minus the fields removed by `exclude*` rules.
- `SchemaValidator::stream()` and the other streaming methods are lazy and keep memory bounded,
  as described in the README's streaming guarantees.

## What is not public

These can change in any release:

- every class and method tagged `@internal`, including the concrete rule classes in
  `Rules\` (`EmailRule`, `MinRule`, ...). Use rules by name (`'email'`) or through
  `FieldDefinition` (`->email()`), not by constructing the class;
- the Laravel adapters and parsers (`LaravelRuleParser`, `RuleSetCompiler`,
  `LaravelValidatorAdapter`, `LaravelRuleAdapter`, `PresenceVerifierDatabaseValidator`,
  `AuthPasswordHasher`) and the execution internals (`CompiledField`, `ErrorCollector`,
  `NativeValidator`, `LookupBatcher`, `DataHelper`, `Emptiness`);
- the PHP source that native compilation generates, the artifact and cache file formats, and the
  value of fingerprints and artifact keys (they change whenever `NativeCompiler::COMPILER_VERSION`
  or the PHP version changes);
- the text of `SchemaDiagnostics::__toString()` and of exception messages;
- the wording of the built-in English and Arabic messages. Message keys (`min.string`), and the
  placeholders they receive (`:attribute`, `:min`), are public.

Validation results are public. A change that makes data pass that used to fail, or fail that
used to pass, is a breaking change even if no signature changes. The only exceptions are listed
under [Versioning](#versioning).

## Versioning

The project follows [Semantic Versioning](https://semver.org/spec/v2.0.0.html). Every release
is a git tag (`v0.3.0`) with a [CHANGELOG](../CHANGELOG.md) entry.

### Before 1.0 (now)

Versions are `0.MINOR.PATCH`.

- **Patch releases** (`0.3.0` → `0.3.1`) only fix bugs, add documentation or make internal
  changes. They never break the public API or change validation results for correct input.
- **Minor releases** (`0.3.x` → `0.4.0`) may contain breaking changes. Each one is listed in
  the CHANGELOG under **Changed** or **Removed** with an upgrade note, as was done for `0.2.0`.
- Where practical, a public API is deprecated in one minor release before it is removed in a
  later one (see [Deprecation](#deprecation)).

Composer's caret constraint already matches this. `"ahmednour/validation": "^0.3"` accepts
`0.3.*` and never `0.4.0`, so you upgrade across a breaking change only when you change the
constraint.

### From 1.0

- **Patch** (`1.2.3` → `1.2.4`): bug fixes only.
- **Minor** (`1.2` → `1.3`): new features and deprecations. Nothing public is removed or broken.
- **Major** (`1.x` → `2.0`): breaking changes and removal of deprecated APIs.

Use `"ahmednour/validation": "^1.0"` to take every minor and patch release safely.

### What counts as breaking

Before 1.0 these ship only in a minor release. From 1.0 only in a major release:

- removing or renaming a public class, method, constant, property, parameter, config key or
  environment variable;
- adding a required parameter, narrowing a parameter type or widening a return type;
- adding a method to a public interface;
- changing a config default, a return shape or the exception a method throws;
- changing which data passes or fails, except for the cases below.

These are not breaking and can ship in any minor release (or a patch release, for the first two):

- fixing a result that contradicts this documentation or Laravel's documented behavior for a
  rule listed as `full` parity in [docs/rules.md](rules.md). If the fix could make previously
  valid data fail, it ships in a minor release with a CHANGELOG upgrade note, never in a patch;
- a security fix, even when it changes behavior. The CHANGELOG says what changed and why;
- adding a class, method, optional parameter, config key, rule or message;
- changing default message wording;
- changing anything internal.

### Supported PHP and Laravel versions

Raising the minimum PHP or Laravel version is a breaking change: before 1.0 it ships in a minor
release, from 1.0 in a major release. Adding support for a new version can ship in any release.
The tested combinations are listed in the README's
[Compatibility](../README.md#-compatibility) table.

## Deprecation

A public API is deprecated before it is removed, unless keeping it would leave a security hole.

When something is deprecated:

1. Its docblock gets `@deprecated`, naming the version that deprecated it, the replacement, and
   the earliest version that may remove it.
2. The CHANGELOG lists it under **Deprecated**.
3. Where calling it is a clear sign of old usage, it raises an `E_USER_DEPRECATED` notice with
   `trigger_error()`. The notice is never raised on a hot path more than once per process, and
   never from inside the library itself.
4. It keeps working as before until it is removed.

How long it stays:

| Version | Deprecated in | Removed no earlier than |
| :--- | :--- | :--- |
| before 1.0 | `0.N.0` | `0.(N+1).0`: at least one minor release later |
| from 1.0 | any `X.Y.0` minor | the next major, `(X+1).0.0` |

Anything deprecated before 1.0 is removed in 1.0 at the latest, so 1.0 starts without
deprecated APIs.

### Currently deprecated

| API | Deprecated in | Replacement | Removed no earlier than |
| :--- | :--- | :--- | :--- |
| `Compilation\NativeCompiler::generateKey()` | 0.1.0 | `ValidatorCompiler::nativeKeyFor($schema)` or `$schema->fingerprint()->artifactKey` | 0.4.0 |
| `Compilation\ValidatorCompiler::writeNative()` | 0.1.0 | `ValidatorCompiler::writeNativeFor($schema)` | 0.4.0 |

## Release checklist

For maintainers, before tagging a release:

1. Decide the version number from the CHANGELOG entries: any **Changed**/**Removed** entry that
   breaks the public API needs a minor release before 1.0 and a major one after.
2. Check that every new public class or method is listed on this page, and that new
   implementation classes are tagged `@internal`.
3. Check that every deprecation has a CHANGELOG entry, and update the
   [Currently deprecated](#currently-deprecated) table.
4. Remove deprecated APIs that have reached their removal version, if you want to, and list
   them under **Removed**.
5. If a contract test in `tests/Unit/Contract/` had to change, the change is breaking. Make
   sure the CHANGELOG says so.
