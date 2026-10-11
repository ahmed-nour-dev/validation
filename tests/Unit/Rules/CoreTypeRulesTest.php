<?php

declare(strict_types=1);

namespace Vi\Validation\Tests\Unit\Rules;

use PHPUnit\Framework\TestCase;
use Vi\Validation\Execution\ErrorCollector;
use Vi\Validation\Execution\ValidationContext;
use Vi\Validation\Rules\NumericRule;
use Vi\Validation\Rules\BooleanRule;
use Vi\Validation\Rules\ArrayRule;
use Vi\Validation\Rules\DateRule;
use Vi\Validation\Rules\JsonRule;

class CoreTypeRulesTest extends TestCase
{
    private function createContext(array $data = []): ValidationContext
    {
        return new ValidationContext($data, new ErrorCollector());
    }

    // NumericRule Tests
    public function testNumericRulePassesWithInteger(): void
    {
        $rule = new NumericRule();
        $this->assertNull($rule->validate(123, 'field', $this->createContext()));
    }

    public function testNumericRulePassesWithFloat(): void
    {
        $rule = new NumericRule();
        $this->assertNull($rule->validate(123.45, 'field', $this->createContext()));
    }

    public function testNumericRulePassesWithNumericString(): void
    {
        $rule = new NumericRule();
        $this->assertNull($rule->validate('123.45', 'field', $this->createContext()));
    }

    public function testNumericRuleFailsWithString(): void
    {
        $rule = new NumericRule();
        $result = $rule->validate('abc', 'field', $this->createContext());
        $this->assertEquals(['rule' => 'numeric'], $result);
    }

    public function testNumericRuleFailsWithNull(): void
    {
        // A present null is validated like any other value (Laravel); only `nullable` skips it.
        $rule = new NumericRule();
        $this->assertEquals(['rule' => 'numeric'], $rule->validate(null, 'field', $this->createContext()));
    }

    // BooleanRule Tests
    public function testBooleanRulePassesWithTrue(): void
    {
        $rule = new BooleanRule();
        $this->assertNull($rule->validate(true, 'field', $this->createContext()));
    }

    public function testBooleanRulePassesWithFalse(): void
    {
        $rule = new BooleanRule();
        $this->assertNull($rule->validate(false, 'field', $this->createContext()));
    }

    public function testBooleanRulePassesWithOne(): void
    {
        $rule = new BooleanRule();
        $this->assertNull($rule->validate(1, 'field', $this->createContext()));
        $this->assertNull($rule->validate('1', 'field', $this->createContext()));
    }

    public function testBooleanRulePassesWithZero(): void
    {
        $rule = new BooleanRule();
        $this->assertNull($rule->validate(0, 'field', $this->createContext()));
        $this->assertNull($rule->validate('0', 'field', $this->createContext()));
    }

    public function testBooleanRuleFailsWithOtherValues(): void
    {
        $rule = new BooleanRule();
        $result = $rule->validate('yes', 'field', $this->createContext());
        $this->assertEquals(['rule' => 'boolean'], $result);
    }

    // ArrayRule Tests
    public function testArrayRulePassesWithArray(): void
    {
        $rule = new ArrayRule();
        $this->assertNull($rule->validate(['a', 'b'], 'field', $this->createContext()));
    }

    public function testArrayRulePassesWithEmptyArray(): void
    {
        $rule = new ArrayRule();
        $this->assertNull($rule->validate([], 'field', $this->createContext()));
    }

    public function testArrayRuleFailsWithString(): void
    {
        $rule = new ArrayRule();
        $result = $rule->validate('not an array', 'field', $this->createContext());
        $this->assertEquals(['rule' => 'array'], $result);
    }

    // DateRule Tests
    public function testDateRulePassesWithValidDate(): void
    {
        $rule = new DateRule();
        $this->assertNull($rule->validate('2024-01-15', 'field', $this->createContext()));
    }

    public function testDateRulePassesWithFormat(): void
    {
        $rule = new DateRule('Y-m-d');
        $this->assertNull($rule->validate('2024-01-15', 'field', $this->createContext()));
    }

    public function testDateRuleFailsWithInvalidDate(): void
    {
        $rule = new DateRule();
        $result = $rule->validate('not a date', 'field', $this->createContext());
        $this->assertEquals(['rule' => 'date'], $result);
    }

    public function testDateRuleFailsWithWrongFormat(): void
    {
        $rule = new DateRule('Y-m-d');
        $result = $rule->validate('15/01/2024', 'field', $this->createContext());
        $this->assertEquals(['rule' => 'date'], $result);
    }

    // JsonRule Tests
    public function testJsonRulePassesWithValidJson(): void
    {
        $rule = new JsonRule();
        $this->assertNull($rule->validate('{"name":"John"}', 'field', $this->createContext()));
    }

    public function testJsonRulePassesWithJsonArray(): void
    {
        $rule = new JsonRule();
        $this->assertNull($rule->validate('[1,2,3]', 'field', $this->createContext()));
    }

    public function testJsonRuleFailsWithInvalidJson(): void
    {
        $rule = new JsonRule();
        $result = $rule->validate('{invalid}', 'field', $this->createContext());
        $this->assertEquals(['rule' => 'json'], $result);
    }

    public function testJsonRuleFailsWithNonString(): void
    {
        $rule = new JsonRule();
        $result = $rule->validate(['array'], 'field', $this->createContext());
        $this->assertEquals(['rule' => 'json'], $result);
    }
}
