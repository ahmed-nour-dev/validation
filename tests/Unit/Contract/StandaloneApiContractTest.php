<?php

declare(strict_types=1);

namespace Vi\Validation\Tests\Unit\Contract;

use Generator;
use PHPUnit\Framework\TestCase;
use Vi\Validation\Execution\BatchValidationResult;
use Vi\Validation\Execution\ChunkedValidator;
use Vi\Validation\Execution\CompiledSchema;
use Vi\Validation\Execution\ErrorMode;
use Vi\Validation\Execution\ValidationContext;
use Vi\Validation\Execution\ValidationFailure;
use Vi\Validation\Execution\ValidationReport;
use Vi\Validation\Execution\ValidationResult;
use Vi\Validation\Laravel\UnsupportedRuleException;
use Vi\Validation\Rules\ClosureRule;
use Vi\Validation\Rules\RuleInterface;
use Vi\Validation\Schema\SchemaBuilder;
use Vi\Validation\SchemaValidator;
use Vi\Validation\Validator;

/**
 * Return contracts of the documented standalone entry points (docs/public-api.md).
 *
 * A failure here means a public behavior changed. If that is intended, it is a breaking change.
 */
final class StandaloneApiContractTest extends TestCase
{
    private function validator(): SchemaValidator
    {
        $schema = Validator::schema()
            ->field('email')->required()->email()
            ->field('age')->required()->integer()->min(18)
            ->compile();

        return new SchemaValidator($schema);
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function rows(): array
    {
        return [
            ['email' => 'a@example.com', 'age' => 30],
            ['email' => 'not-an-email', 'age' => 30],
            ['email' => 'b@example.com', 'age' => 12],
        ];
    }

    public function testSchemaBuilderCompilesToASchema(): void
    {
        $builder = Validator::schema();
        $this->assertInstanceOf(SchemaBuilder::class, $builder);

        $schema = $builder->field('name')->required()->compile();
        $this->assertInstanceOf(CompiledSchema::class, $schema);
        $this->assertInstanceOf(ValidationResult::class, $schema->validate(['name' => 'x']));
    }

    public function testFromRulesAcceptsLaravelRuleStrings(): void
    {
        $schema = Validator::fromRules(['email' => 'required|email', 'tags' => ['array']]);

        $this->assertInstanceOf(CompiledSchema::class, $schema);
        $this->assertTrue($schema->validate(['email' => 'a@example.com', 'tags' => []])->isValid());
        $this->assertFalse($schema->validate(['email' => 'nope', 'tags' => []])->isValid());
    }

    public function testUnknownRuleThrowsUnsupportedRuleException(): void
    {
        $this->expectException(UnsupportedRuleException::class);

        Validator::fromRules(['email' => 'required|definitely_not_a_rule']);
    }

    public function testPassingResult(): void
    {
        $result = $this->validator()->validate(['email' => 'a@example.com', 'age' => 30]);

        $this->assertTrue($result->isValid());
        $this->assertSame([], $result->errors());
        $this->assertSame([], $result->messages());
        $this->assertSame([], $result->allMessages());
        $this->assertNull($result->first());
        $this->assertNull($result->firstMessage('email'));
        $this->assertSame(0, $result->errorCount());
        $this->assertSame(['email' => 'a@example.com', 'age' => 30], $result->data());
        $this->assertSame(['email' => 'a@example.com', 'age' => 30], $result->validated());
        $this->assertSame('Validation passed.', (string) $result);
    }

    public function testFailingResultShape(): void
    {
        $result = $this->validator()->validate(['email' => 'nope', 'age' => 12]);

        $this->assertFalse($result->isValid());

        // errors(): field => list<array{rule, params, message}>
        $errors = $result->errors();
        $this->assertSame(['email', 'age'], array_keys($errors));
        foreach ($errors as $fieldErrors) {
            $this->assertIsList($fieldErrors);
            foreach ($fieldErrors as $error) {
                $this->assertSame(['rule', 'params', 'message'], array_keys($error));
                $this->assertIsString($error['rule']);
                $this->assertIsArray($error['params']);
            }
        }
        $this->assertSame('email', $errors['email'][0]['rule']);
        $this->assertSame('min', $errors['age'][0]['rule']);

        // messages(): field => list<string>
        $messages = $result->messages();
        $this->assertSame(['email', 'age'], array_keys($messages));
        $this->assertContainsOnly('string', $messages['email']);

        $this->assertSame(array_merge(...array_values($messages)), $result->allMessages());
        $this->assertSame($messages['email'][0], $result->first());
        $this->assertSame($messages['age'][0], $result->firstMessage('age'));
        $this->assertSame(2, $result->errorCount());
        $this->assertSame(['email' => 1, 'age' => 1], $result->errorCountsByField());
        $this->assertSame(['email', 'age'], $result->failedFields());
        $this->assertSame(implode("\n", $result->allMessages()), (string) $result);
    }

    public function testValidatedRemovesExcludedFields(): void
    {
        $schema = Validator::schema()
            ->field('name')->required()
            ->field('internal')->excludeIf('mode', 'public')
            ->compile();

        $result = $schema->validate(['name' => 'x', 'internal' => 'secret', 'mode' => 'public']);

        $this->assertSame(['name' => 'x', 'internal' => 'secret', 'mode' => 'public'], $result->data());
        $this->assertSame(['name' => 'x', 'mode' => 'public'], $result->validated());
    }

    public function testValidateManyReturnsOneResultPerRow(): void
    {
        $results = $this->validator()->validateMany($this->rows());

        $this->assertIsList($results);
        $this->assertCount(3, $results);
        $this->assertContainsOnlyInstancesOf(ValidationResult::class, $results);
        $this->assertSame([true, false, false], array_map(static fn ($r) => $r->isValid(), $results));
    }

    public function testStreamIsLazyAndHonoursPreserveKeys(): void
    {
        $read = 0;
        $source = (function () use (&$read): Generator {
            foreach (['r1' => ['email' => 'a@example.com', 'age' => 30], 'r2' => ['email' => 'x', 'age' => 30]] as $k => $row) {
                $read++;
                yield $k => $row;
            }
        })();

        $stream = $this->validator()->stream($source, preserveKeys: true);
        $this->assertInstanceOf(Generator::class, $stream);
        $this->assertSame(0, $read, 'stream() must not read rows before iteration starts.');

        $this->assertSame('r1', $stream->key());
        $this->assertSame(1, $read, 'stream() must read one row at a time.');

        $keys = [];
        foreach ($stream as $key => $result) {
            $this->assertInstanceOf(ValidationResult::class, $result);
            $keys[] = $key;
        }
        $this->assertSame(['r1', 'r2'], $keys);

        $positions = array_keys(iterator_to_array($this->validator()->stream($this->rows())));
        $this->assertSame([0, 1, 2], $positions);
    }

    public function testStreamingHelpers(): void
    {
        $validator = $this->validator();

        $failures = iterator_to_array($validator->failures($this->rows()));
        $this->assertSame([1, 2], array_keys($failures));

        $seen = [];
        $validator->each($this->rows(), function (ValidationResult $result, int $index) use (&$seen): void {
            $seen[$index] = $result->isValid();
        });
        $this->assertSame([true, false, false], $seen);

        $first = $validator->firstFailure($this->rows());
        $this->assertInstanceOf(ValidationResult::class, $first);
        $this->assertSame('not-an-email', $first->data()['email']);

        $withIndex = $validator->firstFailureWithIndex(['a' => $this->rows()[0], 'b' => $this->rows()[1]]);
        $this->assertInstanceOf(ValidationFailure::class, $withIndex);
        $this->assertSame(1, $withIndex->index);
        $this->assertSame('b', $withIndex->key);
        $this->assertInstanceOf(ValidationResult::class, $withIndex->result);

        $this->assertNull($validator->firstFailure([$this->rows()[0]]));
        $this->assertFalse($validator->allValid($this->rows()));
        $this->assertTrue($validator->allValid([$this->rows()[0]]));
    }

    public function testReport(): void
    {
        $sink = [];
        $report = $this->validator()->report(
            $this->rows(),
            maxStoredFailures: 1,
            onFailure: function (ValidationFailure $failure) use (&$sink): void {
                $sink[] = $failure->index;
            },
        );

        $this->assertInstanceOf(ValidationReport::class, $report);
        $this->assertSame(3, $report->rowsProcessed);
        $this->assertSame(2, $report->failedRows);
        $this->assertSame(1, $report->passedRows());
        $this->assertSame(2, $report->errorCount);
        $this->assertSame(['email' => 1, 'age' => 1], $report->errorCountsByField);
        $this->assertCount(1, $report->failures);
        $this->assertTrue($report->isTruncated());
        $this->assertFalse($report->allValid());
        $this->assertSame([1, 2], $sink, 'onFailure sees every failure, not only the stored ones.');
        $this->assertIsArray($report->summary());
    }

    public function testErrorModesChangeDetailButNotValidity(): void
    {
        $schema = Validator::schema()->field('name')->required()->string()->min(5)->compile();

        foreach (ErrorMode::cases() as $mode) {
            $validator = (new SchemaValidator($schema))->setErrorMode($mode);
            $result = $validator->validate(['name' => 123]);

            $this->assertFalse($result->isValid(), $mode->value);
            $this->assertSame(['name'], $result->failedFields(), $mode->value);
        }

        $countOnly = (new SchemaValidator($schema))->setErrorMode(ErrorMode::CountOnly)->validate(['name' => 123]);
        $this->assertTrue($countOnly->isCountOnly());
        $this->assertSame([], $countOnly->errors());
    }

    public function testChunkedValidator(): void
    {
        $chunked = new ChunkedValidator($this->validator());

        $chunks = [];
        $chunked->validateInChunks($this->rows(), 2, function (int $chunkIndex, array $results) use (&$chunks): void {
            $chunks[$chunkIndex] = count($results);
        });
        $this->assertSame([0 => 2, 1 => 1], $chunks);

        $batches = iterator_to_array($chunked->streamChunks($this->rows(), 2));
        $this->assertContainsOnlyInstancesOf(BatchValidationResult::class, $batches);
        $this->assertCount(2, $batches[0]);
        $this->assertSame(1, $batches[0]->failureCount());
        $this->assertFalse($batches[0]->allValid());
        $this->assertCount(1, $batches[0]->failures());
        $this->assertCount(2, $batches[0]->all());

        $this->assertSame([1, 2], array_keys(iterator_to_array($chunked->streamFailures($this->rows(), 2))));
        $this->assertSame(2, $chunked->countFailures($this->rows(), 2));
    }

    public function testBuildAcceptsTheConfigShape(): void
    {
        $validator = SchemaValidator::build(
            definition: fn (SchemaBuilder $schema) => $schema->field('email')->required()->email(),
            config: ['compilation' => ['precompile' => false, 'cache_path' => null]],
        );

        $this->assertTrue($validator->validate(['email' => 'a@example.com'])->isValid());
        $this->assertFalse($validator->usesNative());
    }

    public function testCustomRuleObjectsAndClosures(): void
    {
        $noSpaces = new class implements RuleInterface {
            public function validate(mixed $value, string $field, ValidationContext $context): ?array
            {
                if (!$context->hasValue('username') || $context->getValue('username') !== $value) {
                    throw new \LogicException('ValidationContext must expose the row being validated.');
                }

                return str_contains((string) $value, ' ') ? ['rule' => 'no_spaces', 'message' => 'No spaces.'] : null;
            }
        };

        $schema = Validator::schema()
            ->field('username')->required()->rules($noSpaces)
            ->field('nickname')->rules(new ClosureRule(function (string $attribute, mixed $value, \Closure $fail): void {
                if ($value === 'admin') {
                    $fail("The {$attribute} is reserved.");
                }
            }))
            ->compile();

        $this->assertTrue($schema->validate(['username' => 'ab', 'nickname' => 'x'])->isValid());

        $result = $schema->validate(['username' => 'a b', 'nickname' => 'admin']);
        $this->assertSame('no_spaces', $result->errors()['username'][0]['rule']);
        $this->assertSame(['No spaces.'], $result->messages()['username']);
        $this->assertSame(['The nickname is reserved.'], $result->messages()['nickname']);
    }
}
