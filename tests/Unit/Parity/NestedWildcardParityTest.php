<?php

declare(strict_types=1);

namespace Vi\Validation\Tests\Unit\Parity;

use PHPUnit\Framework\Attributes\Group;
use Vi\Validation\Tests\Unit\Parity\Support\ParityTestCase;

/**
 * Explicitly covers nested and wildcard attribute addressing (see _attribute_addressing in
 * resources/compatibility-matrix.json): dot nesting of any depth and '*' wildcards, which the
 * ValidatorEngine expands per payload (native compilation is skipped for wildcard schemas).
 */
#[Group('laravel')]
class NestedWildcardParityTest extends ParityTestCase
{
    public function testDepth2NestedRequiredPasses(): void
    {
        $this->assertParity(
            ['address.city' => 'required|string'],
            ['address' => ['city' => 'Springfield']]
        );
    }

    public function testDepth2NestedRequiredFailsWhenMissing(): void
    {
        $this->assertParity(
            ['address.city' => 'required|string'],
            ['address' => ['street' => 'Main St']]
        );
    }

    public function testDepth2NestedRequiredFailsWhenParentMissing(): void
    {
        $this->assertParity(['address.city' => 'required|string'], []);
    }

    public function testDepth2NestedNumericRulePasses(): void
    {
        $this->assertParity(
            ['order.total' => 'required|numeric|min:1'],
            ['order' => ['total' => 25.5]]
        );
    }

    public function testDepth2NestedNumericRuleFails(): void
    {
        $this->assertParity(
            ['order.total' => 'required|numeric|min:1'],
            ['order' => ['total' => 0]]
        );
    }

    public function testDepth2NestedEmailValidation(): void
    {
        $this->assertParity(
            ['contact.email' => 'required|email'],
            ['contact' => ['email' => 'not-an-email']]
        );
    }

    public function testWildcardRequiredPassesWhenEveryElementIsValid(): void
    {
        $this->assertParity(
            ['items.*.sku' => 'required|string'],
            ['items' => [['sku' => 'ABC-1'], ['sku' => 'ABC-2']]]
        );
    }

    public function testWildcardRequiredFailsOnlyForTheOffendingElements(): void
    {
        $this->assertParity(
            ['items.*.sku' => 'required|string'],
            ['items' => [['sku' => 'a'], [], ['sku' => ''], ['sku' => 'b'], ['other' => 1]]]
        );
    }

    public function testWildcardNonImplicitRuleValidatesEveryElement(): void
    {
        $this->assertParity(
            ['items.*.sku' => 'email'],
            ['items' => [['sku' => 'not an email'], ['sku' => 'a@b.co'], ['sku' => 'also not']]]
        );
    }

    public function testWildcardOverMissingEmptyOrNonArrayExpandsToNothing(): void
    {
        $this->assertParity(['items.*.sku' => 'required'], []);
        $this->assertParity(['items.*.sku' => 'required'], ['items' => []]);
        $this->assertParity(['items.*.sku' => 'required'], ['items' => 'nope']);
        $this->assertParity(['items.*.sku' => 'required'], ['items' => ['x', 'y']]);
        $this->assertParity(['items.*' => 'required|integer'], ['items' => []]);
    }

    public function testTrailingWildcardValidatesEachElement(): void
    {
        $this->assertParity(['tags.*' => 'required|string|min:2'], ['tags' => ['ab', 'c', 5, null, '']]);
        $this->assertParity(['tags.*' => 'integer'], ['tags' => ['1' => 1, 'k' => 'x']]);
    }

    public function testNestedWildcards(): void
    {
        $data = ['a' => [
            ['b' => [['c' => 1], ['c' => 'x'], []]],
            ['b' => []],
            ['b' => [['c' => 2]]],
        ]];

        $this->assertParity(['a.*.b.*.c' => 'required|integer'], $data);
        $this->assertParity(['a.*.b.*.c' => 'integer'], $data);
        $this->assertParity(['a.*.b.*' => 'array'], $data);
    }

    public function testWildcardErrorKeysUseConcreteIndexes(): void
    {
        $fast = $this->fast(['items' => [['sku' => 'ok'], ['sku' => '']]], ['items.*.sku' => 'required']);

        self::assertSame(['items.1.sku'], array_keys($fast->errors()->toArray()));
    }

    public function testWildcardWithPlainFieldsAlongside(): void
    {
        $this->assertParity(
            ['name' => 'required', 'items.*.qty' => 'required|integer|min:1', 'note' => 'nullable|string'],
            ['name' => 'x', 'items' => [['qty' => 3], ['qty' => 0], ['qty' => 'a']], 'note' => 5]
        );
    }

    public function testWildcardSometimesAndNullable(): void
    {
        $data = ['items' => [['sku' => null], [], ['sku' => 'x'], ['sku' => '']]];

        $this->assertParity(['items.*.sku' => 'sometimes|required|string'], $data);
        $this->assertParity(['items.*.sku' => 'nullable|string|min:2'], $data);
        $this->assertParity(['items.*.sku' => 'bail|required|string|min:2'], $data);
    }

    public function testSameAndDifferentCompareWithTheSiblingInTheSameElement(): void
    {
        $data = ['rows' => [
            ['a' => 1, 'b' => 1],
            ['a' => 1, 'b' => 2],
            ['a' => 'x', 'b' => 'x'],
        ]];

        $this->assertParity(['rows.*.a' => 'same:rows.*.b'], $data);
        $this->assertParity(['rows.*.a' => 'different:rows.*.b'], $data);
    }

    public function testSiblingReferencesInNestedWildcards(): void
    {
        $data = ['g' => [
            ['k' => 'p', 'm' => [['v' => 'p'], ['v' => 'q']]],
            ['k' => 'r', 'm' => [['v' => 'r']]],
        ]];

        $this->assertParity(['g.*.m.*.v' => 'same:g.*.k'], $data);
    }

    public function testConditionalRequiredRulesReferenceTheSameElement(): void
    {
        $data = ['rows' => [
            ['type' => 'a', 'x' => 'present', 'y' => null],
            ['type' => 'b', 'x' => null, 'y' => null],
            ['type' => 'a'],
            ['type' => 'b', 'x' => 'z', 'y' => 'z'],
        ]];

        $this->assertParity(['rows.*.y' => 'required_with:rows.*.x'], $data);
        $this->assertParity(['rows.*.y' => 'required_without:rows.*.x'], $data);
        $this->assertParity(['rows.*.y' => 'required_if:rows.*.type,a'], $data);
        $this->assertParity(['rows.*.y' => 'required_unless:rows.*.type,a'], $data);
        $this->assertParity(['rows.*.y' => 'required_with_all:rows.*.x,rows.*.type'], $data);
        $this->assertParity(['rows.*.y' => 'required_without_all:rows.*.x,rows.*.type'], $data);
        $this->assertParity(['rows.*.y' => 'prohibited_if:rows.*.type,a'], $data);
        $this->assertParity(['rows.*.y' => 'prohibited_unless:rows.*.type,a'], $data);
        $this->assertParity(['rows.*.y' => 'exclude_if:rows.*.type,a|required'], $data);
    }

    public function testPresentWithReferencesTheSameElement(): void
    {
        if (!method_exists(\Illuminate\Validation\Validator::class, 'validatePresentWith')) {
            self::markTestSkipped("The installed Laravel version has no 'present_with' rule.");
        }

        $this->assertParity(['rows.*.y' => 'present_with:rows.*.x'], ['rows' => [
            ['x' => 'present', 'y' => null],
            ['x' => null],
            ['x' => 'z'],
        ]]);
    }

    public function testConfirmedAndComparisonSiblings(): void
    {
        $data = ['rows' => [
            ['min' => 1, 'max' => 5, 'v' => 3],
            ['min' => 4, 'max' => 2, 'v' => 3],
        ]];

        $this->assertParity(['rows.*.v' => 'gte:rows.*.min|lte:rows.*.max'], $data);
        $this->assertParity(['rows.*.v' => 'gt:rows.*.min|lt:rows.*.max'], $data);
    }

    public function testDistinctComparesWildcardSiblings(): void
    {
        $this->assertParity(['tags.*' => 'distinct'], ['tags' => ['a', 'b', 'a']]);
        $this->assertParity(['tags.*' => 'distinct'], ['tags' => ['a', 'b', 'c']]);
        $this->assertParity(['tags.*' => 'distinct'], ['tags' => [1, '1', 2]]);
        $this->assertParity(['tags.*' => 'distinct:strict'], ['tags' => [1, '1', 2]]);
        $this->assertParity(['tags.*' => 'distinct:ignore_case'], ['tags' => ['A', 'a', 'b']]);
        $this->assertParity(['items.*.id' => 'distinct'], ['items' => [['id' => 1], ['id' => 2], ['id' => 1]]]);
    }

    public function testInArrayWithWildcardOnBothSides(): void
    {
        $data = ['allowed' => ['a', 'b'], 'picks' => ['a', 'z', 'b']];

        $this->assertParity(['picks.*' => 'in_array:allowed.*'], $data);

        $nested = ['rows' => [
            ['v' => 'a', 'ok' => ['a', 'b']],
            ['v' => 'c', 'ok' => ['a', 'b']],
        ]];

        $this->assertParity(['rows.*.v' => 'in_array:rows.*.ok.*'], $nested);
    }

    public function testRulesWithClosureAndCustomMessageForWildcardKey(): void
    {
        $fast = $this->fast(
            ['items' => [['sku' => ''], ['sku' => 'x']]],
            ['items.*.sku' => 'required'],
            ['items.*.sku.required' => 'SKU needed']
        );

        self::assertSame('SKU needed', $fast->errors()->first('items.0.sku'));
    }

    public function testWildcardIsNeverSilentlySkipped(): void
    {
        foreach ([
            ['items.*.sku' => 'required'],
            ['items.*.sku' => 'string'],
            ['items.*.sku' => 'integer|min:5'],
            ['items.*.sku' => 'in:a,b'],
            ['items.*' => 'array'],
        ] as $rules) {
            $this->assertParity($rules, ['items' => [['sku' => 12], ['sku' => 'zz'], 'scalar']]);
        }
    }

    /**
     * Regression (found by the property tests, #14): "a.b.c" used to be split into
     * parent="a", child="b.c" and looked up as the literal key "b.c". Paths of any depth now
     * resolve like Laravel's dot notation, on both the engine and native paths.
     */
    public function testDepth3NestedPathsResolveLikeLaravel(): void
    {
        $this->assertParity(['a.b.c' => 'required|string'], ['a' => ['b' => ['c' => 'present']]]);
        $this->assertParity(['a.b.c' => 'required|string'], ['a' => ['b' => ['c' => 5]]]);
        $this->assertParity(['a.b.c' => 'required|string'], ['a' => ['b' => []]]);
        $this->assertParity(['a.b.c.d' => 'required|integer'], ['a' => ['b' => ['c' => ['d' => 7]]]]);
        $this->assertParity(['a.b.c' => 'sometimes|required|string'], ['a' => ['b' => ['c' => '']]]);
        $this->assertParity(['a.b.c' => 'sometimes|required|string'], ['a' => ['b' => []]]);
        $this->assertParity(['x' => 'required_with:a.b.c'], ['a' => ['b' => ['c' => 1]]]);
    }
}
