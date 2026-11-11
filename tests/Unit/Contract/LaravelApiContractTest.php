<?php

declare(strict_types=1);

namespace Vi\Validation\Tests\Unit\Contract;

use Illuminate\Container\Container;
use Illuminate\Contracts\Support\MessageBag as MessageBagContract;
use Illuminate\Contracts\Validation\Validator as LaravelValidatorContract;
use Illuminate\Support\Facades\Facade;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use Vi\Validation\Execution\CompiledSchema;
use Vi\Validation\Execution\ValidationResult;
use Vi\Validation\Laravel\Facades\FastValidator;
use Vi\Validation\Laravel\FastValidatorFactory;
use Vi\Validation\Laravel\FastValidatorWrapper;
use Vi\Validation\Laravel\UnsupportedRuleException;
use Vi\Validation\SchemaValidator;

/**
 * Return contracts of the documented Laravel entry points: the FastValidator facade, the
 * factory behind it and the wrapper it returns (docs/public-api.md).
 */
#[Group('laravel')]
final class LaravelApiContractTest extends TestCase
{
    private const RULES = ['email' => 'required|email', 'name' => 'required|string|max:5'];

    protected function setUp(): void
    {
        $container = new Container();
        $container->singleton(FastValidatorFactory::class, static fn () => new FastValidatorFactory());
        Facade::setFacadeApplication($container);
        Facade::clearResolvedInstances();
    }

    protected function tearDown(): void
    {
        Facade::clearResolvedInstances();
        Facade::setFacadeApplication(null);
    }

    public function testFacadeResolvesTheFactory(): void
    {
        $this->assertInstanceOf(FastValidatorFactory::class, FastValidator::getFacadeRoot());

        $validator = FastValidator::make(['email' => 'a@example.com', 'name' => 'Ann'], self::RULES);

        $this->assertInstanceOf(FastValidatorWrapper::class, $validator);
        $this->assertInstanceOf(LaravelValidatorContract::class, $validator);
        $this->assertTrue($validator->passes());
    }

    public function testWrapperBehavesLikeLaravelsValidator(): void
    {
        $passing = FastValidator::make(['email' => 'a@example.com', 'name' => 'Ann'], self::RULES);
        $this->assertFalse($passing->fails());
        $this->assertSame(['email' => 'a@example.com', 'name' => 'Ann'], $passing->validated());
        $this->assertSame($passing->validated(), $passing->validate());

        $failing = FastValidator::make(['email' => 'nope', 'name' => 'Too long'], self::RULES);
        $this->assertTrue($failing->fails());
        $this->assertFalse($failing->passes());

        $errors = $failing->errors();
        $this->assertInstanceOf(MessageBagContract::class, $errors);
        $this->assertSame(['email', 'name'], $errors->keys());
        $this->assertSame(['email', 'name'], array_keys($failing->failed()));

        $this->assertSame($errors->all(), $failing->getMessageBag()->all());
    }

    /**
     * Laravel's ValidationException asks the validator for its translator to summarize two or
     * more errors. The wrapper used to have no getTranslator(), so this crashed with an Error.
     */
    public function testValidatedThrowsValidationExceptionOnFailure(): void
    {
        foreach (['validated', 'validate'] as $method) {
            $failing = FastValidator::make(['email' => 'nope', 'name' => 'Too long'], self::RULES);

            try {
                $failing->{$method}();
                $this->fail("$method() must throw on failing data.");
            } catch (ValidationException $e) {
                $this->assertSame($failing, $e->validator);
                $this->assertSame(['email', 'name'], array_keys($e->errors()));
                $this->assertSame($failing->errors()->first() . ' (and 1 more error)', $e->getMessage());
            }
        }
    }

    public function testCustomMessagesAndAttributes(): void
    {
        $validator = FastValidator::make(
            ['email' => 'nope'],
            ['email' => 'required|email'],
            ['email.email' => 'Bad :attribute.'],
            ['email' => 'e-mail address'],
        );

        $this->assertSame('Bad e-mail address.', $validator->errors()->first('email'));
    }

    public function testStreamingThroughTheFacade(): void
    {
        $rows = [
            ['email' => 'a@example.com', 'name' => 'Ann'],
            ['email' => 'nope', 'name' => 'Bob'],
        ];

        $results = iterator_to_array(FastValidator::make($rows, self::RULES)->stream());
        $this->assertCount(2, $results);
        $this->assertContainsOnlyInstancesOf(ValidationResult::class, $results);
        $this->assertSame([true, false], array_map(static fn ($r) => $r->isValid(), $results));

        $wrapper = FastValidator::make($rows, self::RULES);
        $this->assertSame([1], array_keys(iterator_to_array($wrapper->failures())));
        $this->assertFalse($wrapper->allValid());
        $this->assertInstanceOf(SchemaValidator::class, $wrapper->getSchemaValidator());
    }

    public function testFactoryCompilesRuleArrays(): void
    {
        $factory = new FastValidatorFactory();

        $this->assertInstanceOf(CompiledSchema::class, $factory->compile(self::RULES));
        $this->assertSame(['hits', 'misses', 'uncacheable'], array_keys($factory->cacheStats()));
    }

    public function testUnknownRuleThrows(): void
    {
        $this->expectException(UnsupportedRuleException::class);

        FastValidator::make(['email' => 'x'], ['email' => 'definitely_not_a_rule']);
    }
}
