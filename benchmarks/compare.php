<?php

/**
 * Head-to-head comparison against other validators. See benchmarks/README.md.
 *
 *   composer install --working-dir=benchmarks/comparison   # once; PHP >= 8.3
 *   php benchmarks/compare.php                              # whole table
 *   php benchmarks/compare.php --rows=2000 --repeat=3 --scenarios=flat,nested
 */

declare(strict_types=1);

error_reporting(E_ALL & ~E_DEPRECATED & ~E_USER_DEPRECATED);

$autoload = __DIR__ . '/comparison/vendor/autoload.php';
if (!is_file($autoload)) {
    fwrite(STDERR, "Run `composer install --working-dir=benchmarks/comparison` first (needs PHP >= 8.3).\n");
    exit(1);
}
require $autoload;
require __DIR__ . '/Suite.php';

use Illuminate\Container\Container;
use Illuminate\Support\Facades\Facade;
use Illuminate\Translation\ArrayLoader;
use Illuminate\Translation\Translator;
use Illuminate\Validation\Factory as LaravelFactory;
use SanderMuller\FluentValidation\RuleSet;
use Vi\Validation\Benchmarks\Suite;
use Vi\Validation\Compilation\ValidatorCompiler;
use Vi\Validation\Laravel\FastValidatorFactory;
use Vi\Validation\SchemaValidator;
use Vi\Validation\Validator as ViValidator;
use YorCreative\DataValidation\Validator as YorValidator;

$options = getopt('', ['rows::', 'repeat::', 'scenarios::', 'output::', 'markdown::']);
$rowCount = (int) ($options['rows'] ?? 2000);
$repeat = max(1, (int) ($options['repeat'] ?? 3));
$scenarios = explode(',', (string) ($options['scenarios'] ?? 'flat,nested,wildcard,wildcard_bulk'));
$output = (string) ($options['output'] ?? __DIR__ . '/results/comparison.json');
$markdown = (string) ($options['markdown'] ?? __DIR__ . '/results/comparison.md');

const LIBRARIES = [
    'laravel' => 'Laravel `Validator`',
    'vi_factory' => 'vi `FastValidator::make()` per row',
    'vi_compiled' => 'vi compiled schema (native where possible)',
    'sandermuller' => 'sandermuller `RuleSet::validate()`',
    'yorcreative' => 'yorcreative `Validator::make()`',
];

/**
 * The rules every library supports. yorcreative has the smallest rule set, so it sets the limit: no
 * alpha_dash (a regex with the same meaning is used), digits_between, lte or a regex quantifier (it splits the pattern on commas).
 *
 * @return array{rules: array<string, string>, rows: callable(int, string): Generator, rows_divisor: int}
 */
function scenario(string $name): array
{
    $item = [
        'items.*.sku' => 'required|regex:/^[A-Za-z0-9_-]+$/|max:32',
        'items.*.qty' => 'required|integer|min:1|max:1000',
        'items.*.price' => 'required|numeric|min:0',
        'items.*.discount' => 'nullable|numeric|min:0',
        'items.*.tags.*' => 'string|max:20',
    ];

    return match ($name) {
        'flat' => [
            'rules' => [
                'name' => 'required|string|min:2|max:100',
                'email' => 'required|email|max:255',
                'age' => 'nullable|integer|min:13|max:120',
                'active' => 'required|boolean',
                'website' => 'nullable|url',
                'username' => 'required|regex:/^[A-Za-z0-9_-]+$/|min:3|max:30',
            ],
            'rows' => static fn (int $n, string $mode): Generator => Suite::rows('user_import', $n, $mode),
            'rows_divisor' => 1,
        ],
        'nested' => [
            'rules' => [
                'customer.name' => 'required|string|max:100',
                'customer.email' => 'required|email',
                'customer.phone' => 'nullable|regex:/^\+?[0-9 ]+$/',
                'shipping.method' => 'required|in:standard,express',
                'shipping.address.city' => 'required|string|max:60',
                'shipping.address.zip' => 'required_if:shipping.method,express|nullable|digits:5',
                'tags' => 'nullable|array|max:5',
                'notes' => 'nullable|string|max:500',
            ],
            'rows' => static fn (int $n, string $mode): Generator => Suite::rows('api_payload', $n, $mode),
            'rows_divisor' => 1,
        ],
        // 5 line items per payload.
        'wildcard' => [
            'rules' => ['order_id' => 'required|integer|min:1', 'items' => 'required|array|min:1'] + $item,
            'rows' => static fn (int $n, string $mode): Generator => Suite::rows('wildcard_items', $n, $mode),
            'rows_divisor' => 1,
        ],
        // 200 line items per payload (the shape the wildcard-optimised libraries advertise); fewer payloads.
        'wildcard_bulk' => [
            'rules' => ['order_id' => 'required|integer|min:1', 'items' => 'required|array|min:1'] + $item,
            'rows' => static function (int $n, string $mode): Generator {
                for ($i = 0; $i < $n; $i++) {
                    $items = [];
                    for ($k = 0; $k < 200; $k++) {
                        $items[] = [
                            'sku' => $mode === 'invalid' && $k === 0 ? 'SKU #' . $i : "SKU-{$i}-{$k}",
                            'qty' => 1 + (($i + $k) % 20),
                            'price' => 10 + $k,
                            'discount' => $k % 2 === 0 ? null : 5,
                            'tags' => ['new', 'promo'],
                        ];
                    }
                    yield ['order_id' => 1 + $i, 'items' => $items];
                }
            },
            'rows_divisor' => 40, // 2,000 rows -> 50 payloads of 200 items
        ],
        default => throw new InvalidArgumentException("Unknown scenario {$name}"),
    };
}

/**
 * @param array<string, string> $rules
 * @return callable(array<string, mixed>): bool  returns true when the row is valid
 */
function validatorFor(string $library, array $rules, string $cacheDir): callable
{
    switch ($library) {
        case 'laravel':
            $factory = new LaravelFactory(new Translator(new ArrayLoader(), 'en'), new Container());
            return static fn (array $row): bool => $factory->make($row, $rules)->passes();

        case 'vi_factory':
            $factory = new FastValidatorFactory();
            return static fn (array $row): bool => $factory->make($row, $rules)->passes();

        case 'vi_compiled':
            $validator = new SchemaValidator(ViValidator::fromRules($rules), null, new ValidatorCompiler(null, true, $cacheDir));
            $validator->warm(); // native when compilable, otherwise the engine
            return static fn (array $row): bool => $validator->validate($row)->isValid();

        case 'sandermuller':
            $container = new Container();
            $container->instance('validator', new LaravelFactory(new Translator(new ArrayLoader(), 'en'), $container));
            Facade::setFacadeApplication($container);
            $ruleSet = RuleSet::from($rules);
            return static function (array $row) use ($ruleSet): bool {
                try {
                    $ruleSet->validate($row);
                    return true;
                } catch (Illuminate\Validation\ValidationException) {
                    return false;
                }
            };

        case 'yorcreative':
            return static fn (array $row): bool => YorValidator::make($row, $rules)->validate();
    }

    throw new InvalidArgumentException("Unknown library {$library}");
}

/**
 * Best throughput of $repeat runs (the run's own peak memory goes with it).
 *
 * @param callable(array<string, mixed>): bool $validate
 * @param callable(int, string): Generator $rows
 * @return array{rows_per_sec: int, peak_memory_bytes: int, failures: int}
 */
function measure(callable $validate, callable $rows, int $count, string $mode, int $repeat): array
{
    foreach ($rows(1, $mode) as $warmup) {
        $validate($warmup);
    }

    $best = null;
    for ($r = 0; $r < $repeat; $r++) {
        gc_collect_cycles();
        memory_reset_peak_usage();
        $baseline = memory_get_usage();
        $failures = 0;

        $start = hrtime(true);
        foreach ($rows($count, $mode) as $row) {
            if (!$validate($row)) {
                $failures++;
            }
        }
        $seconds = (hrtime(true) - $start) / 1e9;

        $run = [
            'rows_per_sec' => (int) round($count / max($seconds, 1e-9)),
            'peak_memory_bytes' => max(0, memory_get_peak_usage() - $baseline),
            'failures' => $failures,
        ];
        if ($best === null || $run['rows_per_sec'] > $best['rows_per_sec']) {
            $best = $run;
        }
    }

    return $best;
}

$cacheDir = sys_get_temp_dir() . '/vi-validation-compare-' . getmypid();
$versions = [];
foreach (['illuminate/validation', 'ahmednour/validation', 'sandermuller/laravel-fluent-validation', 'yorcreative/data-validation'] as $package) {
    $versions[$package] = Composer\InstalledVersions::getPrettyVersion($package);
}
$report = ['environment' => Suite::environment() + ['packages' => $versions], 'rows' => $rowCount, 'repeat' => $repeat, 'results' => []];

fprintf(STDERR, "PHP %s; %s\n", PHP_VERSION, json_encode($versions));

foreach ($scenarios as $name) {
    $scenario = scenario($name);
    $count = max(1, intdiv($rowCount, $scenario['rows_divisor']));

    foreach (['valid', 'invalid'] as $mode) {
        foreach (array_keys(LIBRARIES) as $library) {
            fprintf(STDERR, "  %-13s %-7s %-12s ... ", $name, $mode, $library);
            try {
                $validate = validatorFor($library, $scenario['rules'], $cacheDir);
                $result = measure($validate, $scenario['rows'], $count, $mode, $repeat);
                fprintf(STDERR, "%s rows/s\n", number_format($result['rows_per_sec']));
            } catch (Throwable $e) {
                $result = ['error' => get_class($e) . ': ' . $e->getMessage()];
                fprintf(STDERR, "ERROR %s\n", $result['error']);
            }
            $report['results'][] = ['scenario' => $name, 'data' => $mode, 'library' => $library, 'rows' => $count] + $result;
        }
    }
}

(new ValidatorCompiler(null, false, $cacheDir))->clearNative();

@mkdir(dirname($output), 0755, true);
file_put_contents($output, json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n");
file_put_contents($markdown, render($report));
fprintf(STDERR, "\nWrote %s and %s\n", $output, $markdown);

/**
 * @param array<string, mixed> $report
 */
function render(array $report): string
{
    $env = $report['environment'];
    $out = [
        '# Head-to-head benchmark results',
        '',
        sprintf('PHP %s · %s (%s CPUs) · %s · OPcache %s · JIT %s · %s',
            $env['php'], $env['cpu'] ?? 'unknown CPU', $env['cpu_count'] ?? '?', $env['os'],
            $env['opcache'] ? 'on' : 'off', $env['jit'] ? 'on' : 'off', $env['timestamp']),
        '',
        'Pinned versions: ' . implode(' · ', array_map(
            static fn (string $p, string $v): string => "`{$p}` {$v}", array_keys($env['packages']), $env['packages']
        )),
        '',
        sprintf('Best of %d runs of %s rows per cell (wildcard_bulk: fewer, 200-item payloads). Speedups are relative to Laravel in the same row. '
            . '"≠" marks a library whose failure count differs from Laravel\'s, i.e. it did not validate the same way, so its rate is not comparable.',
            $report['repeat'], number_format($report['rows'])),
        '',
    ];

    foreach (['valid', 'invalid'] as $mode) {
        $out[] = "## {$mode} data";
        $out[] = '';
        $out[] = '| Scenario | ' . implode(' | ', LIBRARIES) . ' |';
        $out[] = '| :--- |' . str_repeat(' ---: |', count(LIBRARIES));

        foreach (array_unique(array_column($report['results'], 'scenario')) as $name) {
            $cells = [];
            $laravel = null;
            foreach ($report['results'] as $r) {
                if ($r['scenario'] === $name && $r['data'] === $mode && $r['library'] === 'laravel') {
                    $laravel = $r;
                }
            }
            foreach (array_keys(LIBRARIES) as $library) {
                $cell = '–';
                foreach ($report['results'] as $r) {
                    if ($r['scenario'] !== $name || $r['data'] !== $mode || $r['library'] !== $library) {
                        continue;
                    }
                    if (isset($r['error'])) {
                        $cell = 'error';
                        break;
                    }
                    $cell = number_format($r['rows_per_sec']) . ' /s';
                    if ($library !== 'laravel' && isset($laravel['rows_per_sec'])) {
                        $cell .= sprintf(' (%s×)', round($r['rows_per_sec'] / max(1, $laravel['rows_per_sec']), 1));
                    }
                    $cell .= sprintf(' · %d KB', $r['peak_memory_bytes'] / 1024);
                    if (isset($laravel['failures']) && $r['failures'] !== $laravel['failures']) {
                        $cell .= sprintf(' ≠ (%d vs %d failures)', $r['failures'], $laravel['failures']);
                    }
                    break;
                }
                $cells[] = $cell;
            }
            $out[] = "| `{$name}` | " . implode(' | ', $cells) . ' |';
        }
        $out[] = '';
    }

    return implode("\n", $out);
}
