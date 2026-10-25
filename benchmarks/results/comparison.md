# Head-to-head benchmark results

PHP 8.5.9 · Intel(R) Core(TM) i7-10870H CPU @ 2.20GHz (16 CPUs) · Linux 6.18.33.2-microsoft-standard-WSL2 · OPcache off · JIT off · 2026-10-25T10:30:45+00:00

Pinned versions: `illuminate/validation` v13.38.0 · `ahmednour/validation` dev-ahmed-nour-dev/p2-head-to-head-benchmark-against-sandermuller-a · `sandermuller/laravel-fluent-validation` 1.35.0 · `yorcreative/data-validation` v1.1.0

Best of 3 runs of 2,000 rows per cell (wildcard_bulk: fewer, 200-item payloads). Speedups are relative to Laravel in the same row. "≠" marks a library whose failure count differs from Laravel's, i.e. it did not validate the same way, so its rate is not comparable.

## valid data

| Scenario | Laravel `Validator` | vi `FastValidator::make()` per row | vi compiled schema (native where possible) | sandermuller `RuleSet::validate()` | yorcreative `Validator::make()` |
| :--- | ---: | ---: | ---: | ---: | ---: |
| `flat` | 3,061 /s · 38 KB | 53,408 /s (17.4×) · 5 KB | 261,113 /s (85.3×) · 3 KB | 2,788 /s (0.9×) · 42 KB | 33,273 /s (10.9×) · 7 KB |
| `nested` | 2,928 /s · 40 KB | 38,538 /s (13.2×) · 5 KB | 200,483 /s (68.5×) · 4 KB | 2,713 /s (0.9×) · 42 KB | 28,489 /s (9.7×) · 8 KB |
| `wildcard` | 680 /s · 56 KB | 14,083 /s (20.7×) · 9 KB | 17,515 /s (25.8×) · 8 KB | 961 /s (1.4×) · 50 KB | 9,030 /s (13.3×) · 25 KB |
| `wildcard_bulk` | 19 /s · 917 KB | 542 /s (28.5×) · 297 KB | 542 /s (28.5×) · 295 KB | 29 /s (1.5×) · 342 KB | 180 /s (9.5×) · 795 KB |

## invalid data

| Scenario | Laravel `Validator` | vi `FastValidator::make()` per row | vi compiled schema (native where possible) | sandermuller `RuleSet::validate()` | yorcreative `Validator::make()` |
| :--- | ---: | ---: | ---: | ---: | ---: |
| `flat` | 2,384 /s · 61 KB | 47,528 /s (19.9×) · 6 KB | 229,080 /s (96.1×) · 5 KB | 2,162 /s (0.9×) · 190 KB | 28,665 /s (12×) · 8 KB |
| `nested` | 2,706 /s · 42 KB | 37,445 /s (13.8×) · 6 KB | 203,816 /s (75.3×) · 4 KB | 2,555 /s (0.9×) · 43 KB | 27,403 /s (10.1×) · 8 KB |
| `wildcard` | 647 /s · 55 KB | 13,901 /s (21.5×) · 10 KB | 16,984 /s (26.3×) · 9 KB | 910 /s (1.4×) · 52 KB | 8,750 /s (13.5×) · 25 KB |
| `wildcard_bulk` | 19 /s · 917 KB | 519 /s (27.3×) · 298 KB | 530 /s (27.9×) · 206 KB | 30 /s (1.6×) · 171 KB | 175 /s (9.2×) · 795 KB |
