# Agent guidance for this repository

This is a production Laravel 13 application (a school parent–teacher
appointment booking system), not a fresh skeleton — read
[`docs/ARCHITECTURE.md`](docs/ARCHITECTURE.md) before making non-trivial
changes. It records the reasoning behind schema and concurrency decisions
that are easy to accidentally undo if you don't know why they're there.

## Things that will look wrong but are deliberate

- **`phpunit.xml` points at real MySQL/MariaDB, not SQLite.** The mandatory
  concurrent double-booking test (`tests/Feature/Booking/ConcurrentBookingTest.php`)
  needs genuine row-locking semantics that SQLite can't provide. Don't
  "simplify" this back to `:memory:`.
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

Requires two real MySQL/MariaDB databases configured (dev + test — see
`phpunit.xml` and `README.md`). Run the full suite, not just the file you
touched, before considering a change done — several tests exist
specifically to catch cross-role/cross-owner IDOR regressions that won't
fail in isolation.
