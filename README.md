# Lyceum Appointments

A parent–teacher appointment booking system for a Greek Lyceum (upper
secondary school), built with Laravel 13. Guardians book 5-minute
appointment slots with teachers; teachers manage their own availability;
administrators manage accounts and run bulk Excel imports.

## Stack

- **Backend**: PHP 8.3+, Laravel 13, SQLite
- **Frontend**: Blade + Tailwind CSS + Alpine.js (Laravel Breeze scaffolding)
- **Excel import/export**: PhpSpreadsheet, used directly (no Laravel-Excel wrapper)

No Docker, Redis, WebSockets, or persistent Node.js process is required —
see [`docs/ARCHITECTURE.md`](docs/ARCHITECTURE.md) for why, and for the
full set of architectural decisions made across each development phase.

## Local development setup

```bash
composer install
npm install

cp .env.example .env
php artisan key:generate
```

No database setup needed — `DB_CONNECTION=sqlite` in `.env.example` and
`config/database.php`'s default both point at `database/database.sqlite`,
which Laravel creates for you:

```bash
touch database/database.sqlite
php artisan migrate
npm run build      # or `npm run dev` for hot-reloading during frontend work
php artisan serve
```

Create the first administrator account (there is no public registration
path to the admin role by design):

```bash
php artisan app:create-admin
```

## Running the tests

```bash
php artisan test
```

The test suite runs against a real file-based SQLite database
(`database/testing.sqlite`, configured in `phpunit.xml`), not `:memory:` —
the mandatory concurrent double-booking test launches two genuine OS
processes that race for the same slot, and an in-memory database is private
to a single connection/process, so they'd never see each other's data. See
`docs/ARCHITECTURE.md`'s concurrency-strategy notes for how correctness is
still guaranteed without MySQL-style row-level locking.

## Documentation

- [`docs/ARCHITECTURE.md`](docs/ARCHITECTURE.md) — technology decisions,
  schema design, the booking concurrency strategy, the Excel import
  pipeline, and a running log of what was built and why in each
  development phase.
- [`docs/DEPLOYMENT.md`](docs/DEPLOYMENT.md) — deploying to Plesk /
  webhost.sch.gr, including a production checklist, backup
  recommendations, and troubleshooting guide.
