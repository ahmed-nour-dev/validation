<?php

declare(strict_types=1);

namespace Vi\Validation\Tests\Unit\Rules;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Vi\Validation\Execution\ErrorCollector;
use Vi\Validation\Execution\ValidationContext;
use Vi\Validation\Laravel\FastValidatorFactory;
use Vi\Validation\Rules\ArrayKeysRule;
use Vi\Validation\Rules\Base64Rule;
use Vi\Validation\Rules\DoesntContainRule;
use Vi\Validation\Rules\EncodingRule;
use Vi\Validation\Rules\InArrayKeysRule;
use Vi\Validation\Validator;

/**
 * Rules added by Laravel 12 / 13: doesnt_contain, encoding, in_array_keys, array_keys, base64.
 * Laravel-vs-library parity lives in LaravelCoverageParityTest; these tests do not need the
 * installed Laravel to know the rule.
 */
final class LaravelNewerRulesTest extends TestCase
{
    private function context(): ValidationContext
    {
        return new ValidationContext([], new ErrorCollector());
    }

    public function testDoesntContainComparesLoosely(): void
    {
        $rule = new DoesntContainRule(['1', 'x']);

        $this->assertNull($rule->validate([2, 3], 'f', $this->context()));
        $this->assertNull($rule->validate(['a'], 'f', $this->context()));
        $this->assertSame('doesnt_contain', $rule->validate([1, 2], 'f', $this->context())['rule'] ?? null);
        $this->assertSame('doesnt_contain', $rule->validate(['1'], 'f', $this->context())['rule'] ?? null);
        $this->assertSame('doesnt_contain', $rule->validate('x', 'f', $this->context())['rule'] ?? null);
    }

    public function testEncoding(): void
    {
        $rule = new EncodingRule('utf-8');

        $this->assertNull($rule->validate('héllo', 'f', $this->context()));
        $this->assertNull($rule->validate(123, 'f', $this->context()));
        $this->assertSame('encoding', $rule->validate("\xff\xfe", 'f', $this->context())['rule'] ?? null);
        $this->assertSame('encoding', (new EncodingRule('ASCII'))->validate('é', 'f', $this->context())['rule'] ?? null);
    }

    public function testEncodingRejectsUnknownEncodingWhenBuilt(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new EncodingRule('utf8');
    }

    public function testInArrayKeysNeedsAtLeastOneKey(): void
    {
        $rule = new InArrayKeysRule('a', 'b');

        $this->assertNull($rule->validate(['b' => null], 'f', $this->context()));
        $this->assertNull((new InArrayKeysRule('1'))->validate([1 => 'one'], 'f', $this->context()));
        $this->assertSame('in_array_keys', $rule->validate(['c' => 1], 'f', $this->context())['rule'] ?? null);
        $this->assertSame('in_array_keys', $rule->validate('a', 'f', $this->context())['rule'] ?? null);
    }

    public function testArrayKeysRejectsAnyOtherKeyAndNamesIt(): void
    {
        $rule = new ArrayKeysRule('a', 'b');

        $this->assertNull($rule->validate([], 'f', $this->context()));
        $this->assertNull($rule->validate(['a' => 1, 'b' => 2], 'f', $this->context()));

        $error = $rule->validate(['a' => 1, 'x' => 2, 'y' => 3], 'f', $this->context());
        $this->assertSame('array_keys', $error['rule'] ?? null);
        $this->assertSame('x, y', $error['params']['unexpected'] ?? null);
        $this->assertSame('a, b', $error['params']['values'] ?? null);
        $this->assertSame('array_keys', $rule->validate('a', 'f', $this->context())['rule'] ?? null);
    }

    public function testBase64MustBeCanonical(): void
    {
        $rule = new Base64Rule();

        foreach (['aGVsbG8=', 'YQ==', 'AAAA'] as $valid) {
            $this->assertNull($rule->validate($valid, 'f', $this->context()), $valid);
        }
        foreach (['aGVsbG8', 'aGVsbG8==', 'YR==', '!!', 'not base64', '', 123, ['YQ==']] as $invalid) {
            $this->assertSame('base64', $rule->validate($invalid, 'f', $this->context())['rule'] ?? null, var_export($invalid, true));
        }
    }

    public function testRuleStringsNoLongerThrowUnsupported(): void
    {
        $factory = new FastValidatorFactory(['cache' => ['enabled' => false], 'localization' => ['locale' => 'en']]);

        $validator = $factory->make(
            [
                'tags' => ['a', 'b'],
                'name' => "\xff",
                'meta' => ['id' => 1, 'extra' => 2],
                'blob' => 'not base64!',
                'opts' => ['x' => 1],
            ],
            [
                'tags' => 'doesnt_contain:a',
                'name' => 'encoding:utf-8',
                'meta' => 'array_keys:id',
                'blob' => 'base64',
                'opts' => 'in_array_keys:x,y',
            ]
        );

        $this->assertTrue($validator->fails());
        $errors = $validator->errors();
        $this->assertSame('The tags field must not contain any of the following: a.', $errors->first('tags'));
        $this->assertSame('The name field must be encoded in utf-8.', $errors->first('name'));
        $this->assertSame('The meta field must only contain the following keys: id.', $errors->first('meta'));
        $this->assertSame('The blob field must be a valid Base64 string.', $errors->first('blob'));
        $this->assertFalse($errors->has('opts'));
    }

    public function testFluentSchemaMethods(): void
    {
        $schema = Validator::schema()
            ->field('payload')->base64()
            ->field('keys')->arrayKeys('a', 'b')->inArrayKeys('a')
            ->field('list')->doesntContain('x')
            ->field('text')->encoding('utf-8')
            ->compile();

        $this->assertTrue($schema->validate([
            'payload' => 'aGVsbG8=', 'keys' => ['a' => 1], 'list' => ['y'], 'text' => 'ok',
        ])->isValid());
        $this->assertFalse($schema->validate([
            'payload' => 'nope!', 'keys' => ['z' => 1], 'list' => ['x'], 'text' => "\xff",
        ])->isValid());
    }
}
