# Agent guidance for this repository

This is a production Laravel 13 application (a school parent–teacher
appointment booking system), not a fresh skeleton — read
[`docs/ARCHITECTURE.md`](docs/ARCHITECTURE.md) before making non-trivial
changes. It records the reasoning behind schema and concurrency decisions
that are easy to accidentally undo if you don't know why they're there.

## Things that will look wrong but are deliberate

- **This branch runs on SQLite, deliberately traded off against MySQL's
  row-level locking** (see `docs/ARCHITECTURE.md`'s concurrency notes for
  the full reasoning — short version: SQLite has no row-level locks, so a
  `busy_timeout` plus the pre-existing `active_slot_id` unique-constraint
  backstop take over that job; correctness holds, only cross-teacher
  concurrency is coarser than it would be on MySQL). This is the
  `sqlite-migration` branch specifically *for* that trade-off — the `master`
  branch stays on MySQL/MariaDB and its own AGENTS.md still warns against
  "simplifying" it to SQLite. Don't merge this branch's database layer back
  into `master` without re-reading why that warning exists there.
- **`phpunit.xml` points at a real file-based SQLite database
  (`database/testing.sqlite`), not `:memory:`.** The mandatory concurrent
  double-booking test (`tests/Feature/Booking/ConcurrentBookingTest.php`)
  launches two genuine OS processes that must share one database — an
  in-memory SQLite database is private to a single connection and would
  make the two processes invisible to each other.
- **Never assign a `Carbon`/`DateTimeInterface` instance directly to a
  `date`-cast attribute** (e.g. `$slot->date = $someCarbonInstance`) —
  always pass a plain `'Y-m-d'` string (`->toDateString()`). Eloquent's
  `'date:Y-m-d'` cast only enforces that format when the input is a string;
  a `DateTimeInterface` input serializes through the connection's full
  datetime format instead. MySQL's `DATE` column type silently truncates
  the extra time portion so this never surfaced there; SQLite has no such
  coercion and stores/compares the literal (wrong) string. This actually
  bit three tests during the SQLite migration — grep for
  `->toDateString()` near `AppointmentSlot`/`Availability` factories for
  the fixed examples before adding a new one.
- **`appointments.active_slot_id`** is a nullable column that mirrors
  `slot_id` while an appointment is active and is set to `NULL` on
  cancellation, with a unique index on it. This is intentional — a plain
  unique constraint on `slot_id` would prevent legitimate rebooking after a
  cancellation.
- **`ConcurrentBookingTest` does not use `RefreshDatabase`.** That trait
  wraps each test in an uncommitted transaction, which would hide its
  fixture rows from the two separate OS processes it spawns to race for a
  real lock. It cleans up manually in `tearDown()` instead.
- **No `maatwebsite/excel` dependency** — Excel reading/writing uses
  `phpoffice/phpspreadsheet` directly. See `docs/ARCHITECTURE.md`'s Phase 6
  notes for why.
- **Public registration only ever creates guardian accounts.** Admin and
  teacher accounts are provisioned by an administrator (manually or via
  bulk import) — there is no self-registration path to those roles, by
  design (spec-driven, not an oversight).

## Conventions

- Business logic that must be atomic or race-safe lives in `app/Services/`
  (`BookingService`, `AvailabilityService`, `ExcelImportService`), not in
  controllers. Controllers stay thin.
- Every route-bound model access is authorized via a Policy
  (`app/Policies/`) or an explicit ownership check — never rely on hidden
  UI alone. If you add a new controller method that takes a route-bound
  model, it needs one of these before it touches the model.
- Enums live in `app/Enums/` with string-backed lowercase values and a
  `label()` method for display text — keep new status/type fields
  consistent with this pattern rather than inventing a new casing
  convention.
- Run `./vendor/bin/pint` before committing; the test suite and Pint are
  both expected to be clean at every commit in this repo's history.

## Testing

```bash
php artisan test
```

No database server required — `php artisan test` runs against
`database/testing.sqlite` (see `phpunit.xml`); the app's own dev database is
`database/database.sqlite`. Run the full suite, not just the file you
touched, before considering a change done — several tests exist
specifically to catch cross-role/cross-owner IDOR regressions that won't
fail in isolation.
