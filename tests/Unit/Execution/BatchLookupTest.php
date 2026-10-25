<?php

declare(strict_types=1);

namespace Vi\Validation\Tests\Unit\Execution;

use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Validation\DatabasePresenceVerifier;
use PHPUnit\Framework\TestCase;
use Vi\Validation\Execution\ChunkedValidator;
use Vi\Validation\Laravel\FastValidatorFactory;
use Vi\Validation\Laravel\PresenceVerifierDatabaseValidator;
use Vi\Validation\SchemaValidator;

final class BatchLookupTest extends TestCase
{
    private Capsule $capsule;
    /** @var list<string> */
    private array $queries = [];

    protected function setUp(): void
    {
        $this->capsule = new Capsule();
        $this->capsule->addConnection(['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '']);
        $conn = $this->capsule->getConnection();
        $conn->statement('create table users (id integer primary key, email text, status text)');
        for ($i = 1; $i <= 50; $i++) {
            $conn->table('users')->insert(['id' => $i, 'email' => "user{$i}@example.com", 'status' => $i % 2 ? 'active' : 'inactive']);
        }
        $conn->beforeExecuting(function (string $sql) { $this->queries[] = $sql; });
    }

    /**
     * @param array<string, string> $rules
     */
    private function validator(array $rules, bool $batch): SchemaValidator
    {
        $factory = new FastValidatorFactory();
        $factory->setDatabaseValidator(new PresenceVerifierDatabaseValidator(
            new DatabasePresenceVerifier($this->capsule->getDatabaseManager())
        ));
        $v = $factory->make([], $rules)->getSchemaValidator();
        if ($batch) {
            $v->enableBatchLookups(100);
        }

        return $v;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function rows(): array
    {
        $rows = [];
        for ($i = 1; $i <= 300; $i++) {
            $rows[] = ['email' => $i % 3 === 0 ? "USER{$i}@example.com" : "user{$i}@example.com", 'id' => $i % 60];
        }

        return $rows;
    }

    /**
     * @param \Vi\Validation\Execution\ValidationResult[] $results
     * @return list<array<string, mixed>>
     */
    private static function errors(iterable $results): array
    {
        $out = [];
        foreach ($results as $r) {
            $out[] = $r->errors();
        }

        return $out;
    }

    public function testQueryCountDropsFromNToNOverChunk(): void
    {
        $rules = ['email' => 'unique:users,email', 'id' => 'exists:users,id'];
        $rows = $this->rows();

        $this->queries = [];
        $perRow = self::errors($this->validator($rules, false)->validateMany($rows));
        $perRowQueries = count($this->queries);

        $this->queries = [];
        $batched = self::errors($this->validator($rules, true)->validateMany($rows));
        $batchedQueries = count($this->queries);

        self::assertSame($perRow, $batched, 'batched results must equal per-row results');
        self::assertGreaterThan(300, $perRowQueries);
        // 300 rows / lookahead 100 = 3 windows x 2 rules; the case-ambiguous rows (USERn)
        // fall back per row, never more than one query each.
        self::assertLessThanOrEqual(6 + 100, $batchedQueries);
        self::assertLessThan($perRowQueries / 2, $batchedQueries);
    }

    public function testStreamAndChunkedMatchPerRowAndBatch(): void
    {
        $rules = ['email' => 'exists:users,email'];
        $rows = array_map(fn ($i) => ['email' => "user{$i}@example.com"], range(1, 400));

        $expected = self::errors($this->validator($rules, false)->validateMany($rows));

        $this->queries = [];
        $v = $this->validator($rules, true);
        self::assertSame($expected, self::errors($v->stream((function () use ($rows) { yield from $rows; })())));
        self::assertSame(4, count($this->queries));

        $chunked = new ChunkedValidator($v);
        $seen = [];
        $chunked->validateInChunks($rows, 100, function (int $i, array $results) use (&$seen) { array_push($seen, ...self::errors($results)); });
        self::assertSame($expected, $seen);
    }

    public function testUniqueIgnoreAndExtraConstraintsAreRespected(): void
    {
        $rules = [
            'email' => [\Illuminate\Validation\Rule::unique('users', 'email')->ignore(3)],
            'status' => [\Illuminate\Validation\Rule::exists('users', 'status')->where('id', 2)],
        ];
        $rows = [
            ['email' => 'user3@example.com', 'status' => 'inactive'],
            ['email' => 'user4@example.com', 'status' => 'active'],
            ['email' => 'new@example.com', 'status' => 'inactive'],
        ];

        $expected = self::errors($this->validator($rules, false)->validateMany($rows));
        self::assertSame($expected, self::errors($this->validator($rules, true)->validateMany($rows)));
    }

    public function testClosureConstraintsFallBackPerRow(): void
    {
        $rules = ['email' => [\Illuminate\Validation\Rule::exists('users', 'email')->where(fn ($q) => $q->where('status', 'active'))]];
        $rows = [['email' => 'user1@example.com'], ['email' => 'user2@example.com']];

        $expected = self::errors($this->validator($rules, false)->validateMany($rows));
        self::assertSame($expected, self::errors($this->validator($rules, true)->validateMany($rows)));
    }

    public function testBatcherStateIsClearedAfterStream(): void
    {
        $v = $this->validator(['email' => 'unique:users,email'], true);
        foreach ($v->stream([['email' => 'fresh@example.com']]) as $_) {
        }

        $this->capsule->getConnection()->table('users')->insert(['id' => 99, 'email' => 'fresh@example.com', 'status' => 'x']);

        self::assertFalse($v->validate(['email' => 'fresh@example.com'])->isValid());
    }
}
