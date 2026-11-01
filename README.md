# DressCost

Work out what it costs to make a dress (or any garment) and what to sell it for.

DressCost organises costing as **workspaces → projects → items**. Each item lists the materials it uses and any other costs (labour, overhead, embroidery…), the number of pieces to make in each size, and a profit margin. The app works out the cost, selling price and profit per piece and for the whole run, and rolls them up into project totals and reports.

## Features

- **Workspaces, projects and items**, e.g. _2026 Fashion › Summer collection › Resort maxi dress_.
- **Materials list** with units (metre, yard, piece, roll, kg) and prices per unit.
- **Item costing**: material quantities, other costs by costing type (pick an existing type or type a new one), profit margin, live cost estimate.
- **Sizes**: pieces to make in Small, Medium, Large and XL; every size costs the same per piece.
- **Reuse saved items**: start a new item from any saved one, copying its whole costing.
- **Reports**: drill down from workspaces to projects to items; costs by costing type; cost by year; materials used; filters by workspace, project, costing type and date.
- **Compare** two workspaces, two projects or two items side by side.
- **Share links**: give anyone a read-only link to a project's full costing, no login needed; stop sharing at any time.
- **Telescope** (local only): requests, exceptions and queries at `/telescope`.
- Light and dark themes, accounts with two-factor authentication and passkeys.

Business rules are documented in [`docs/requirements.md`](docs/requirements.md) and the UI in [`docs/design.md`](docs/design.md).

## Quick start with Docker

Needs only Docker (Docker Desktop, or `brew install colima docker docker-compose` then `colima start --cpu 4 --memory 4`).

```bash
docker compose up --build
```

Open <http://localhost:8080> and log in as **demo@dresscost.test** / **password**. The demo account comes with sample workspaces, projects and items. Telescope is at <http://localhost:8080/telescope>.

Stop with `Ctrl+C`. Data is kept in a Docker volume; `docker compose down -v` deletes it.

## Running locally without Docker

Requires PHP 8.4+, Composer, Node 20.19+ and MySQL (or SQLite).

```bash
composer setup                      # install, create .env, app key, migrate, build
php artisan db:seed                 # optional: demo data (demo@dresscost.test / password)
php artisan serve                   # http://localhost:8000
```

Set your database in `.env` (`DB_CONNECTION`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`) and your currency with `APP_CURRENCY` (default `LKR`). For live frontend reloading during development, use `composer dev` instead of `php artisan serve`.

## Development

```bash
composer test        # Pint, PHPStan (level 7) and the PHPUnit test suite
npm run check        # frontend lint and formatting
npm run types:check  # TypeScript
```

## Tech stack

Laravel 13 · Inertia 3 · React 19 · TypeScript · Tailwind CSS 4 · shadcn/ui · Recharts · Fortify (auth, 2FA, passkeys) · Wayfinder · MySQL · Docker.
