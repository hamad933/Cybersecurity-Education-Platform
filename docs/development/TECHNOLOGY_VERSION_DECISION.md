# Technology version decision

Verified 2026-07-22 from primary documentation and official package registries. No preview, RC, nightly, or EOL release was selected.

| Component | Selected / locked | Primary verification and compatibility |
|---|---:|---|
| Laravel skeleton | 13.8.0 | Official `laravel/laravel` Packagist release used only as the controlled scaffold |
| Laravel framework | 13.21.1 | [`laravel.com/docs/13.x/releases`](https://laravel.com/docs/13.x/releases): Laravel 13 supports PHP 8.3–8.5; released 2026-03-17, security fixes through 2028-03-17. Exact package in `composer.lock` |
| PHP | 8.5.8 NTS x64 | [`php.net`](https://www.php.net/) and [`supported-versions.php`](https://www.php.net/supported-versions.php): 8.5 is supported; official Windows archive SHA-256 `63A3F6493F37C9FF3E288EC16621222A6CDA5167DD1ABFFEC0019E7F18C8E7E9` |
| Composer | 2.10.2 | [`getcomposer.org/download`](https://getcomposer.org/download/); official PHAR SHA-256 `5EE7125F8A30A34D246CEFDC0BC85B8A783B28F2AEC968994118512350D28027` |
| Inertia Laravel | 3.1.1 | [`packagist.org/packages/inertiajs/inertia-laravel`](https://packagist.org/packages/inertiajs/inertia-laravel); supports Laravel 11/12/13 |
| Inertia Vue / Vite | 3.6.1 / 3.6.1 | Official npm registry; matches the official Laravel Vue starter approach documented at [`laravel.com/docs/13.x/starter-kits`](https://laravel.com/docs/13.x/starter-kits) |
| Vue | 3.5.40 | Official npm registry and [`vuejs.org`](https://vuejs.org/) |
| TypeScript | 6.0.3 | Official npm registry; selected as the latest stable 6.x compatible with locked `typescript-eslint` (<6.1 peer constraint). TypeScript 7.0.2 was rejected by npm's peer resolver and was not forced |
| Tailwind CSS | 4.3.3 | Official npm registry and [`tailwindcss.com/docs`](https://tailwindcss.com/docs/installation/using-vite) |
| Vite / Vue plugin | 8.1.5 / 6.0.8 | Official npm registry; Node 20.19+ or 22.12+ compatible |
| Node / npm | 24.18.0 LTS / 11.16.0 | [`nodejs.org/en/download`](https://nodejs.org/en/download/); official Windows x64 ZIP SHA-256 `0AE68406B42D7725661DA979B1403EC9926DA205C6770827F33AAC9D8F26E821` |
| PostgreSQL | 18.4 | [`postgresql.org/support/versioning`](https://www.postgresql.org/support/versioning/): supported major through 2030-11-14; EDB portable archive SHA-256 `02E239529ED7833D169F98D915D3FEFFE0813264B08B3AE353E78E8B9C97E1A6` |
| PostgreSQL container | `postgres:18.4-bookworm` | Official [`hub.docker.com/_/postgres`](https://hub.docker.com/_/postgres); pinned tag; PostgreSQL 18 data layout used |
| PHPUnit / Pint / Larastan | 12.5.31 / 1.29.3 / 3.10.0 | Exact Composer lock entries; Larastan resolves PHPStan 2.2.5 and supports Laravel 13 |

The complete JavaScript versions are exact in `package.json` and `package-lock.json`: Vue Test Utils 2.4.11, Vitest 4.1.10, ESLint 10.7.0, eslint-plugin-vue 10.10.0, `@vue/eslint-config-typescript` 14.9.0, Prettier 3.9.6, Tailwind Prettier plugin 0.8.1, jsdom 29.1.1, and `@types/node` 24.13.3. Composer uses semantic constraints in `composer.json` and exact resolved versions in `composer.lock`.

## Security and support result

`composer audit --locked` found no advisory; `npm audit --audit-level=high` found zero vulnerabilities. npm reported deprecated transitive `glob@10.5.0` through `@vue/test-utils -> js-beautify`; it is not a direct dependency and produced no audit advisory. It is recorded for future lock refresh, not silently suppressed.
