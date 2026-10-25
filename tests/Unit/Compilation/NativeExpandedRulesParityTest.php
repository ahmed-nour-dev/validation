<?php

declare(strict_types=1);

namespace Vi\Validation\Tests\Unit\Compilation;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use Vi\Validation\Compilation\NativeCompiler;
use Vi\Validation\Compilation\UnsupportedNativeRuleException;
use Vi\Validation\Execution\ValidatorEngine;
use Vi\Validation\Laravel\RuleSetCompiler;
use Vi\Validation\Rules\NativeCompilableInterface;
use Vi\Validation\Rules\RuleRegistry;
use Vi\Validation\Validator;

/**
 * Differential tests for the rules that were added to native compilation after the first 17:
 * for every rule, over a battery of awkward values (and pairs of values, for the rules that
 * look at a sibling field), the generated closure must report exactly the errors - rule name
 * *and* params - that ValidatorEngine reports.
 */
#[Group('native')]
final class NativeExpandedRulesParityTest extends TestCase
{
    /** Values chosen to cover each rule's type branches, not to be exhaustive. */
    private const BATTERY = [
        null, '', '   ', 'a', 'php', 'go', 'rust', '0', '1', '12', '123', '12345', '1.5', 'abc-def',
        0, 1, 3, -2, 1.0, 1.5, 100, true, false,
        [], ['php'], ['php', 'go'], ['php', 'rust'], [['php']], ['k' => 'v'],
        '2024-01-31', '2024-02-30', '2024-1-5', "20\0",
        'Hello World', 'héllo', 'ab', 'abc', 'abcd', 'prefix-x', 'x-suffix',
        '123e4567-e89b-12d3-a456-426614174000', '123E4567-E89B-62D3-A456-426614174000', 'not-a-uuid',
        'null', 'true', 'express', 'standard',
    ];

    /**
     * @return array<string, array{0: array<string, string>}>
     */
    public static function singleFieldRules(): array
    {
        $rules = [
            'in' => 'in:php,go,1,true',
            'in (array)' => 'array|in:php,go',
            'not_in' => 'not_in:php,go,1,true',
            'not_in (array)' => 'array|not_in:php,go',
            'regex' => 'regex:/^[a-z]+$/',
            'regex with delimiters in pattern' => 'regex:/^[0-9]{2,5}$/i',
            'not_regex' => 'not_regex:/^[a-z]+$/',
            'date_format' => 'date_format:Y-m-d',
            'date_format (time)' => 'date_format:H:i',
            'between (string)' => 'between:2,4',
            'between (numeric)' => 'numeric|between:1,100',
            'between (integer)' => 'integer|between:-2,3',
            'size (string)' => 'size:3',
            'size (numeric)' => 'numeric|size:1',
            'size (array)' => 'array|size:2',
            'digits' => 'digits:3',
            'digits_between' => 'digits_between:1,3',
            'uuid' => 'uuid',
            'starts_with' => 'starts_with:pre,ab,12',
            'ends_with' => 'ends_with:fix,ab,12',
            'nullable in' => 'nullable|in:php',
            'bail chain' => 'bail|required|regex:/^[a-z]+$/|in:php,go|min:3',
            'sometimes' => 'sometimes|uuid',
        ];

        return array_map(static fn (string $rule): array => [['value' => $rule]], $rules);
    }

    /**
     * @param array<string, string> $rules
     */
    #[DataProvider('singleFieldRules')]
    public function testSingleFieldRuleMatchesTheEngine(array $rules): void
    {
        $rows = [[]];
        foreach (self::BATTERY as $value) {
            $rows[] = ['value' => $value];
        }

        $this->assertNativeMatchesEngine($rules, $rows);
    }

    /**
     * @return array<string, array{0: array<string, string>}>
     */
    public static function siblingRules(): array
    {
        $rules = [
            'same' => ['value' => 'same:other'],
            'different' => ['value' => 'different:other'],
            'same (nested sibling)' => ['value' => 'same:p.q'],
            'required_if' => ['value' => 'required_if:other,php,1,true,null'],
            'required_if (nested)' => ['value' => 'required_if:p.q,php,go'],
            'required_unless' => ['value' => 'required_unless:other,php,1,false,null'],
            'required_with' => ['value' => 'required_with:other'],
            'required_with (several)' => ['value' => 'required_with:other,third'],
            'required_without' => ['value' => 'required_without:other'],
            'required_without (several)' => ['value' => 'required_without:other,third'],
            'required_if + nullable' => ['value' => 'nullable|required_if:other,php|string'],
            'required_with + format rule' => ['value' => 'required_with:other|regex:/^[a-z]+$/'],
        ];

        return array_map(static fn (array $r): array => [$r], $rules);
    }

    /**
     * @param array<string, string> $rules
     */
    #[DataProvider('siblingRules')]
    public function testSiblingDependentRuleMatchesTheEngine(array $rules): void
    {
        $rows = [[], ['other' => 'php'], ['value' => 'x']];
        foreach (self::BATTERY as $value) {
            foreach (self::BATTERY as $other) {
                $rows[] = ['value' => $value, 'other' => $other, 'third' => $other === null ? 'z' : null, 'p' => ['q' => $other]];
            }
            $rows[] = ['value' => $value, 'third' => $value];
        }

        $this->assertNativeMatchesEngine($rules, $rows);
    }

    public function testErrorParamsHaveTheEnginesShape(): void
    {
        $rules = [
            'a' => 'date_format:Y-m-d', 'b' => 'between:2,4', 'c' => 'size:3', 'd' => 'digits:3',
            'e' => 'digits_between:1,3', 'f' => 'starts_with:x,y', 'g' => 'required_if:b,x', 'h' => 'required_with:a,b',
        ];
        $row = ['a' => 'nope', 'b' => 'x', 'c' => 'toolong', 'd' => 'abc', 'e' => 'abcd', 'f' => 'z', 'g' => null, 'h' => null];

        $native = $this->nativeErrors($rules, $row);

        // Values are compared loosely (the parser yields 2.0 for `between:2,4`); the differential
        // tests above pin the exact engine equality.
        $this->assertEquals([0 => 'Y-m-d', 'format' => 'Y-m-d'], $native['a'][0]['params']);
        $this->assertEquals(['type' => 'string', 'min' => 2, 'max' => 4], $native['b'][0]['params']);
        $this->assertEquals(['type' => 'string', 'size' => 3], $native['c'][0]['params']);
        $this->assertEquals(['digits' => 3], $native['d'][0]['params']);
        $this->assertEquals(['min' => 1, 'max' => 3], $native['e'][0]['params']);
        $this->assertEquals(['values' => 'x, y'], $native['f'][0]['params']);
        $this->assertEquals(['other' => 'b', 'values' => 'x'], $native['g'][0]['params']);
        $this->assertEquals(['values' => 'a, b'], $native['h'][0]['params']);
    }

    public function testHostileParametersCannotBreakOutOfGeneratedCode(): void
    {
        $evil = "/x/'; echo 'pwned'; \$errors = []; //";
        $rules = [
            'a' => ['regex:' . $evil],
            'b' => ['in:' . "a'); system('id'); ('b"],
            'c' => ['starts_with:' . "'.phpinfo().'"],
            'd' => ['same:' . "o'].phpinfo().['"],
            'e' => ['date_format:' . "Y'; die(); '"],
        ];

        $schema = (new RuleSetCompiler(self::registry()))->compile($rules);
        $code = (new NativeCompiler())->compile($schema);

        $closure = eval(substr($code, strlen('<?php')));
        ob_start();
        $result = $closure(['a' => 'x', 'b' => 'b', 'c' => 'z', 'd' => 'x', 'e' => '2024']);
        $this->assertSame('', ob_get_clean(), 'generated code must not execute injected statements');
        $this->assertFalse($result['valid']);
        $this->assertSame(
            (new ValidatorEngine())->validate($schema, ['a' => 'x', 'b' => 'b', 'c' => 'z', 'd' => 'x', 'e' => '2024'])->errors(),
            $result['errors']
        );
    }

    public function testNonScalarRuleParametersStayOnTheEngine(): void
    {
        $rule = new \Vi\Validation\Rules\RequiredIfRule('other', [static fn () => 1]);
        $schema = Validator::schema()->field('value')->rules($rule)->compile();

        $this->expectException(UnsupportedNativeRuleException::class);
        (new NativeCompiler())->compile($schema);
    }

    public function testEveryRuleListedInTheIssueIsNativelyCompilable(): void
    {
        $registry = self::registry();
        $compiler = new NativeCompiler();

        foreach ([
            'in:a', 'not_in:a', 'regex:/a/', 'not_regex:/a/', 'date_format:Y', 'between:1,2', 'size:1', 'digits:1',
            'digits_between:1,2', 'uuid', 'starts_with:a', 'ends_with:a', 'same:b', 'different:b',
            'required_if:b,1', 'required_unless:b,1', 'required_with:b', 'required_without:b',
        ] as $definition) {
            $rule = (new \Vi\Validation\Laravel\LaravelRuleParser($registry))->parse($definition, 'a')[0];
            $this->assertInstanceOf(NativeCompilableInterface::class, $rule, $definition);
            $this->assertTrue($compiler->isSupported($rule), $definition);
        }
    }

    /**
     * @param array<string, string> $rules
     * @param list<array<string, mixed>> $rows
     */
    private function assertNativeMatchesEngine(array $rules, array $rows): void
    {
        $schema = (new RuleSetCompiler(self::registry()))->compile($rules);
        $compiler = new NativeCompiler();
        $this->assertTrue($compiler->canCompile($schema), implode(', ', $compiler->findUnsupportedRules($schema)));

        $closure = eval(substr($compiler->compile($schema), strlen('<?php')));
        $engine = new ValidatorEngine();

        foreach ($rows as $row) {
            $expected = $engine->validate($schema, $row)->errors();
            $actual = $closure($row)['errors'];

            $this->assertSame($expected, $actual, 'rules=' . json_encode($rules) . ' row=' . var_export($row, true));
        }
    }

    /**
     * @param array<string, string> $rules
     * @param array<string, mixed> $row
     * @return array<string, list<array{rule: string, params: array<mixed>, message: ?string}>>
     */
    private function nativeErrors(array $rules, array $row): array
    {
        $schema = (new RuleSetCompiler(self::registry()))->compile($rules);
        $closure = eval(substr((new NativeCompiler())->compile($schema), strlen('<?php')));

        return $closure($row)['errors'];
    }

    private static function registry(): RuleRegistry
    {
        $registry = new RuleRegistry();
        $registry->registerBuiltInRules();

        return $registry;
    }
}
