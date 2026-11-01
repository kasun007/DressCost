# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Business domain

Business requirements live in a separate doc; follow them when building features: @docs/requirements.md

Frontend design and layout requirements; follow them when building UI: @docs/design.md

## Stack

Laravel 13 (PHP 8.3+) backend + Inertia v3 + React 19 / TypeScript frontend, built from the Laravel React starter kit. Tailwind v4, shadcn/ui (Radix) components, Fortify for auth (incl. 2FA and passkeys via `@laravel/passkeys`), Wayfinder for typed routes. SQLite by default (`database/database.sqlite`). JS package manager is pnpm (`pnpm-workspace.yaml`, `.npmrc` sets `ignore-scripts=true`). Frontend tooling runs through `vite-plus` (`vp`), which provides build, lint and format in one CLI.

## Commands

- Run everything with Docker (no local PHP/Node/MySQL needed): `docker compose up --build`, then open http://localhost:8080 (demo login `demo@dresscost.test` / `password`; Telescope at http://localhost:8080/telescope). `compose.yaml` runs the app (PHP 8.5, `Dockerfile`) and MySQL 8.4; `docker/entrypoint.sh` waits for MySQL, migrates, and seeds demo data only into an empty database. `docker compose down -v` deletes the data volume.
- First-time setup: `composer setup` (install deps, copy .env, key:generate, migrate, build assets)
- Dev server (PHP server, queue, logs, Vite together): `composer dev`
- Full test pipeline: `composer test` — runs Pint check, PHPStan, then `php artisan test`
- Single test: `php artisan test --filter=test_method_name` or `php artisan test tests/Feature/Auth/AuthenticationTest.php`
- Everything CI runs: `composer ci:check` (frontend lint/format + `tsc` + `composer test`)
- PHP format: `composer lint` (Pint, `laravel` preset); check only: `composer lint:check`
- PHP static analysis: `composer types:check` (Larastan, level 7, over app/, config/, database/, routes/, bootstrap/app.php)
- Frontend lint + format check: `npm run check`; autofix: `npm run check:fix` (warnings are errors — `denyWarnings: true`)
- TS type check: `npm run types:check`
- Build: `npm run build` (SSR: `npm run build:ssr`)

Tests are PHPUnit classes (not Pest), in `tests/Feature` and `tests/Unit`. They run against in-memory SQLite (see `phpunit.xml`).

## Architecture

- **Routing → pages**: Routes in `routes/web.php` and `routes/settings.php` render Inertia pages by name (`Route::inertia('dashboard', 'dashboard')` or `Inertia::render(...)` in controllers). Names map to `resources/js/pages/<name>.tsx`.
- **Layouts are assigned centrally** in `resources/js/app.tsx` by page-name prefix: `welcome` → none, `auth/*` → `AuthLayout`, `settings/*` → `[AppLayout, SettingsLayout]`, everything else → `AppLayout`. Pages don't wrap themselves in layouts.
- **Shared props** (`name`, `auth.user`, `sidebarOpen`) come from `app/Http/Middleware/HandleInertiaRequests.php`; their TS types live in `resources/js/types/`.
- **Wayfinder**: the Vite plugin generates typed route/controller helpers into `resources/js/routes/`, `resources/js/actions/` and `resources/js/wayfinder/` (gitignored, regenerated on dev/build). Frontend code imports URLs from these (e.g. `import { dashboard } from '@/routes'`, `import ProfileController from '@/actions/App/Http/Controllers/Settings/ProfileController'`) instead of hardcoding paths. After adding/renaming routes or controller methods, the dev server or build must run to regenerate them. `formVariants: true` is enabled for use with Inertia `<Form>`.
- **Auth** is handled by Fortify (no auth controllers in app/): config in `config/fortify.php`, views/actions/rate limiting wired in `app/Providers/FortifyServiceProvider.php`, user-creation/reset logic in `app/Actions/Fortify/`. Shared validation rules live in `app/Concerns/` traits. Only profile/security settings have app controllers (`app/Http/Controllers/Settings/`).
- **Appearance (light/dark)**: persisted in the `appearance` cookie, read server-side by `HandleAppearance` middleware and client-side by `hooks/use-appearance.tsx`. `appearance` and `sidebar_state` cookies are excluded from encryption in `bootstrap/app.php`.
- **React Compiler** is enabled via Babel in `vite.config.ts`, so manual `useMemo`/`useCallback` is generally unnecessary.
- `resources/js/components/ui/` holds shadcn-generated components (`components.json`); they are excluded from lint/format — add new ones via the shadcn CLI rather than hand-editing style.
- **Telescope** (`laravel/telescope`) records requests, exceptions, queries, logs and more at `/telescope`. It is excluded from package discovery and registered only in the `local` environment by `AppServiceProvider::registerTelescope()`; Docker runs with `APP_ENV=local`, so it's available at http://localhost:8080/telescope there too. Never set `APP_ENV=local` on a public server. It is disabled in tests (`phpunit.xml`) and pruned to 48 hours daily (`routes/console.php`) and on each Docker start.

## Conventions

- Frontend formatting: 4-space indent, single quotes, semicolons, 80-col, Tailwind classes auto-sorted (including inside `cn`/`clsx`/`cva`). Path alias `@/` → `resources/js/`.
