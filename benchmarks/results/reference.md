# ahmednour/validation benchmark results

PHP 8.5.9 · illuminate/validation v13.38.0 · Linux 6.18.33.2-microsoft-standard-WSL2 · Intel(R) Core(TM) i7-10870H CPU @ 2.20GHz (16 CPUs) · OPcache off · JIT off · 2026-10-25T10:10:39+00:00

## One-time costs (per schema)

| Scenario | Compile schema | Native codegen + persist | Artifact load | Native? |
| :--- | ---: | ---: | ---: | :---: |
| user_import | 0.214 ms | 2.070 ms | 0.562 ms | yes |
| order_lines | 0.048 ms | 0.750 ms | 0.349 ms | yes |
| api_payload | 0.063 ms | 1.619 ms | 0.622 ms | yes |
| wildcard_items | 0.050 ms | — | — | no |

## Steady-state throughput

| Scenario | Path | Rows | Time | Rows/s | vs Laravel | Peak memory added |
| :--- | :--- | ---: | ---: | ---: | ---: | ---: |
| user_import | laravel | 1,000 | 0.344 s | 2,903 | — | 65.9 KB |
| user_import | factory | 1,000 | 0.021 s | 48,111 | 16.6× | 6.8 KB |
| user_import | engine | 1,000 | 0.012 s | 83,599 | 28.8× | 5.2 KB |
| user_import | native | 1,000 | 0.004 s | 229,603 | 79.1× | 5.5 KB |
| user_import | laravel | 10,000 | 3.421 s | 2,923 | — | 45.5 KB |
| user_import | factory | 10,000 | 0.203 s | 49,361 | 16.9× | 6.8 KB |
| user_import | engine | 10,000 | 0.099 s | 101,175 | 34.6× | 5.2 KB |
| user_import | native | 10,000 | 0.040 s | 252,202 | 86.3× | 5.5 KB |
| user_import | factory | 100,000 | 1.992 s | 50,214 | 17.2× | 6.8 KB |
| user_import | engine | 100,000 | 0.992 s | 100,797 | 34.5× | 5.2 KB |
| user_import | native | 100,000 | 0.396 s | 252,644 | 86.4× | 5.5 KB |
| order_lines | laravel | 1,000 | 0.269 s | 3,714 | — | 338.3 KB |
| order_lines | factory | 1,000 | 0.021 s | 46,713 | 12.6× | 7.0 KB |
| order_lines | engine | 1,000 | 0.010 s | 95,815 | 25.8× | 5.4 KB |
| order_lines | native | 1,000 | 0.004 s | 270,313 | 72.8× | 5.8 KB |
| order_lines | laravel | 10,000 | 2.686 s | 3,724 | — | 46.1 KB |
| order_lines | factory | 10,000 | 0.218 s | 45,925 | 12.3× | 7.0 KB |
| order_lines | engine | 10,000 | 0.106 s | 94,245 | 25.3× | 5.4 KB |
| order_lines | native | 10,000 | 0.038 s | 260,618 | 70× | 5.8 KB |
| order_lines | factory | 100,000 | 2.261 s | 44,235 | 11.9× | 7.0 KB |
| order_lines | engine | 100,000 | 1.091 s | 91,634 | 24.6× | 5.4 KB |
| order_lines | native | 100,000 | 0.390 s | 256,708 | 68.9× | 5.8 KB |
| api_payload | laravel | 1,000 | 0.360 s | 2,780 | — | 50.5 KB |
| api_payload | factory | 1,000 | 0.027 s | 36,508 | 13.1× | 7.6 KB |
| api_payload | engine | 1,000 | 0.013 s | 74,364 | 26.7× | 6.0 KB |
| api_payload | native | 1,000 | 0.005 s | 189,067 | 68× | 5.9 KB |
| api_payload | laravel | 10,000 | 3.516 s | 2,844 | — | 48.4 KB |
| api_payload | factory | 10,000 | 0.275 s | 36,351 | 12.8× | 7.6 KB |
| api_payload | engine | 10,000 | 0.149 s | 67,241 | 23.6× | 6.0 KB |
| api_payload | native | 10,000 | 0.052 s | 190,451 | 67× | 5.9 KB |
| api_payload | factory | 100,000 | 2.697 s | 37,082 | 13× | 7.6 KB |
| api_payload | engine | 100,000 | 1.409 s | 70,969 | 25× | 6.0 KB |
| api_payload | native | 100,000 | 0.511 s | 195,528 | 68.8× | 5.9 KB |
| wildcard_items | laravel | 1,000 | 1.525 s | 656 | — | 60.8 KB |
| wildcard_items | factory | 1,000 | 0.077 s | 13,053 | 19.9× | 13.2 KB |
| wildcard_items | engine | 1,000 | 0.066 s | 15,236 | 23.2× | 11.6 KB |
| wildcard_items | laravel | 10,000 | 15.407 s | 649 | — | 60.8 KB |
| wildcard_items | factory | 10,000 | 0.780 s | 12,812 | 19.7× | 13.2 KB |
| wildcard_items | engine | 10,000 | 0.643 s | 15,563 | 24× | 11.6 KB |
| wildcard_items | factory | 100,000 | 7.849 s | 12,741 | 19.6× | 13.2 KB |
| wildcard_items | engine | 100,000 | 6.487 s | 15,416 | 23.8× | 11.6 KB |
