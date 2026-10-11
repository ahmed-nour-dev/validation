<?php

declare(strict_types=1);

namespace Vi\Validation\Tests\Unit\Parity;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use Vi\Validation\Tests\Unit\Parity\Support\ParityTestCase;

/**
 * Non-implicit rules (email, integer, min, ...) are skipped by Laravel only when the key is
 * absent or the value is a blank string. A present `null` or `[]` is validated - `['a' => null]`
 * fails `string`, `['a' => []]` fails `email` - and `nullable` skips `null` for non-implicit
 * rules only. Every rule below runs against each of those shapes through both validators (and the
 * native-compiled closure when the schema is compilable).
 */
#[Group('laravel')]
final class EmptyValueParityTest extends ParityTestCase
{
    /**
     * Rules whose handling of a present null / [] changed in Laravel 13: 12 and older pass it,
     * 13 fails it. The library follows 13, so these are only comparable on 13+ (see the matrix).
     */
    private const LARAVEL_13_BEHAVIOUR = ['ascii', 'doesnt_end_with', 'doesnt_start_with', 'max_digits'];

    private const OTHER_FIELDS = ['other' => 'yes', 'arr' => ['x', 'y'], 'a_confirmation' => 'q'];

    /**
     * @return array<string, array{string}>
     */
    public static function nonImplicitRules(): array
    {
        $rules = [
            'after:2020-01-01', 'after_or_equal:2020-01-01', 'alpha', 'alpha_dash', 'alpha_num', 'array', 'array:a,b',
            'ascii', 'before:2030-01-01', 'before_or_equal:2030-01-01', 'between:1,5', 'boolean', 'confirmed',
            'contains:x', 'date', 'date_equals:2020-01-01', 'date_format:Y-m-d', 'decimal:2', 'different:other',
            'digits:3', 'digits_between:2,4', 'dimensions:min_width=10', 'distinct', 'doesnt_end_with:x',
            'doesnt_start_with:x', 'email', 'ends_with:x', 'extensions:jpg', 'file', 'gt:other', 'gte:other',
            'hex_color', 'image', 'in:a,b', 'in_array:arr.*', 'integer', 'ip', 'ipv4', 'ipv6', 'json', 'list',
            'lt:other', 'lte:other', 'lowercase', 'mac_address', 'max:3', 'max_digits:3', 'mimes:jpg',
            'mimetypes:image/png', 'min:3', 'min_digits:3', 'multiple_of:3', 'not_in:a,b', 'not_regex:/x/',
            'numeric', 'regex:/^a/', 'required_array_keys:a', 'same:other', 'size:3', 'starts_with:a', 'string',
            'timezone', 'ulid', 'uppercase', 'url', 'uuid',
            // Laravel 12 / 13 additions; skipped below when the installed Laravel doesn't know them.
            'array_keys:a', 'base64', 'doesnt_contain:x', 'encoding:utf-8', 'in_array_keys:a',
        ];

        return array_combine($rules, array_map(static fn (string $r): array => [$r], $rules));
    }

    private function skipIfUnknownToLaravel(string $rule): void
    {
        $name = explode(':', $rule, 2)[0];
        $method = 'validate' . str_replace('_', '', ucwords($name, '_'));
        if (!method_exists(\Illuminate\Validation\Validator::class, $method)) {
            self::markTestSkipped("The installed Laravel version has no '{$name}' rule.");
        }
    }

    private function skipIfLaravelPredates13Behaviour(string $rule): void
    {
        $installed = \Composer\InstalledVersions::getVersion('illuminate/validation');
        if (
            in_array(explode(':', $rule, 2)[0], self::LARAVEL_13_BEHAVIOUR, true)
            && ($installed === null || version_compare($installed, '13.0.0', '<'))
        ) {
            self::markTestSkipped("Laravel < 13 passes a present null/[] for '{$rule}'; the library follows 13.");
        }
    }

    /**
     * Older Laravel versions throw a TypeError themselves for some inputs (e.g. `uppercase` on an
     * array); there is nothing to compare then. A TypeError from the library is still a failure.
     *
     * @param array<string, mixed> $rules
     * @param array<string, mixed> $data
     */
    private function assertParityWhereLaravelCanRun(array $rules, array $data): void
    {
        try {
            $this->assertParity($rules, $data);
        } catch (\TypeError $e) {
            if (!str_contains(str_replace('\\', '/', $e->getFile()), '/vendor/illuminate/')) {
                throw $e;
            }
        }
    }

    #[DataProvider('nonImplicitRules')]
    public function testPresentNullAndEmptyArrayAreValidated(string $rule): void
    {
        $this->skipIfUnknownToLaravel($rule);
        $this->skipIfLaravelPredates13Behaviour($rule);

        foreach ([null, []] as $value) {
            $this->assertParityWhereLaravelCanRun(['a' => $rule], ['a' => $value] + self::OTHER_FIELDS);
        }
    }

    #[DataProvider('nonImplicitRules')]
    public function testBlankStringsAndMissingKeysAreSkipped(string $rule): void
    {
        $this->skipIfUnknownToLaravel($rule);

        foreach (['', ' ', "\t\n"] as $value) {
            $this->assertParityWhereLaravelCanRun(['a' => $rule], ['a' => $value] + self::OTHER_FIELDS);
        }
        $this->assertParity(['a' => $rule], self::OTHER_FIELDS);
    }

    #[DataProvider('nonImplicitRules')]
    public function testNullableSkipsOnlyNull(string $rule): void
    {
        $this->skipIfUnknownToLaravel($rule);
        $this->skipIfLaravelPredates13Behaviour($rule);

        $this->assertParityWhereLaravelCanRun(['a' => "nullable|$rule"], ['a' => null] + self::OTHER_FIELDS);
        $this->assertParityWhereLaravelCanRun(['a' => "nullable|$rule"], ['a' => []] + self::OTHER_FIELDS);
    }

    public function testNullableDoesNotSkipImplicitRules(): void
    {
        foreach (['accepted', 'declined', 'accepted_if:other,yes', 'declined_if:other,yes', 'filled', 'required', 'present'] as $rule) {
            $this->assertParity(['a' => "nullable|$rule"], ['a' => null] + self::OTHER_FIELDS);
        }
        $this->assertParity(['a' => 'nullable|required_if:other,yes'], ['a' => null] + self::OTHER_FIELDS);
    }
}
