# Architecture — Lyceum Parent–Teacher Appointment Booking System

Status: Phase 1 (foundation) complete. This document is updated as later phases land.

## 1. Technology decision

| Concern | Decision | Reason |
|---|---|---|
| Backend | Laravel 13 (PHP 8.3) | Matches spec; mature ecosystem; runs on conventional shared hosting (Plesk). |
| Database | MySQL/MariaDB (dev: XAMPP MariaDB 10.4) | Required by spec; supports row-level locking (`SELECT ... FOR UPDATE`) needed for double-booking protection. |
| Frontend | Blade + Tailwind CSS + Alpine.js via **Laravel Breeze (Blade stack)** | Gives complete auth scaffolding (login/register/forgot-password/reset/email verify) for free, server-rendered, no SPA build complexity, Alpine already wired in. Vite is a **build-time** tool only — `npm run build` produces static assets, no Node process at runtime. |
| Excel import/export | `maatwebsite/excel` 4.0.2 (PhpSpreadsheet 5.9) | De facto standard Laravel Excel library; supports chunked reading, validation, queued imports if ever needed, formula-injection escaping on export. |
| Sessions/Cache/Queue | `database` driver for sessions & cache, `sync` for queue | No Redis, no persistent worker process required — fits shared hosting. `sync` queue means notifications/emails execute inline; acceptable since we avoid slow operations there. This can be upgraded to `database` queue + cron-driven `schedule:run` later without code changes if email volume grows. |
| Roles | Single `role` enum column on `users` (`admin`/`teacher`/`guardian`) + Policies/Gates + middleware | Matches the spec's explicit `users.role` field. Avoids pulling in spatie/permission for a fixed, small role set — less complexity for a shared-hosting deployment. |
| Timezone | `Europe/Athens`, set via `APP_TIMEZONE` env, read by `config/app.php` | Must not depend on server timezone (spec §28). All date/time handling in the app will use `now()`/Carbon which respects this config. |

PHP extensions `zip` and `intl` were disabled by default in this environment's `php.ini` and have been enabled — `zip` is required by PhpSpreadsheet to read/write `.xlsx`, `intl` is recommended by Laravel for locale-aware formatting. Both are commonly available on Plesk/webhost.sch.gr; this is documented in the deployment guide (Phase 10).

## 2. Database schema (as it will evolve through Phase 2–4)

Refinements versus the spec's literal table list, and why:

- **`users`**: split `name` into `first_name`/`last_name` (spec asks for this on guardians/teachers; kept consistent on the base table rather than a parallel `profile` table). Add `role` enum, `status` enum (`active`/`inactive`), `subject` (nullable, teacher-only), `phone` (nullable, guardian-only), `must_change_password` boolean, `password_reset_required_at`. Single-table-per-role keeps auth simple (one `users` table = one login mechanism) while nullable role-specific columns avoid a join for the common case. A `teacher_profile`/`guardian_profile` split was considered but rejected: it adds a mandatory join to nearly every query for no real normalization benefit at this size.
- **`children`**: `guardian_id` FK → `users.id` (`cascadeOnDelete` is *not* used here — deactivating/deleting a guardian must not silently delete appointment history; instead guardians are soft-deactivated via `status`, never hard-deleted, per spec §7/§27 intent).
- **`availability`**: a teacher-authored window (date + start_time + end_time). A DB-level check + application-level overlap validation prevents overlapping windows for the same teacher/date (spec §11).
- **`appointment_slots`**: the generated 5-minute atoms of an `availability` window. `status` enum `AVAILABLE`/`BOOKED`/`DISABLED`. **Unique constraint on (`teacher_id`, `date`, `start_time`)** — this is the hard backstop against double booking, independent of application logic.
- **`appointments`**: one row per booking attempt outcome that succeeded; `slot_id` has a **unique constraint** enforcing "at most one active appointment per slot" at the DB layer (belt-and-braces alongside the slot's `status` and row locking — see §4 below). `status` enum `NEW`/`CONFIRMED`/`CANCELLED`/`COMPLETED`.
- **`notifications`**: simple in-app notification table (not Laravel's built-in polymorphic notifications table, to match the spec's exact column list and keep querying trivial for a notification center + unread badge).
- **`import_batches`** / **`import_errors`**: import history and per-row error detail, exactly as specified.

Full column-level migrations are written in Phase 2 (users/children/teachers) and Phase 3–4 (availability/slots/appointments/notifications) rather than all at once, so each phase's migrations ship with the tests that exercise them.

## 3. Authorization strategy

- Route groups per role prefix (`/admin/*`, `/teacher/*`, `/guardian/*`), each behind an `EnsureUserHasRole` middleware — **never** relying on hidden UI alone (spec §4).
- Laravel **Policies** for object-level checks that middleware can't express: a guardian may only view/cancel *their own* appointments and *their own* children; a teacher may only manage *their own* availability and see appointments booked with *them*. Every controller action authorizes via `$this->authorize()` / policy, so even a guessed URL/ID (IDOR) is rejected server-side.
- Admin accounts are seeded via an Artisan command (`app:create-admin`, Phase 2) — never exposed on the public registration route (spec §7, §41).

## 4. Booking concurrency strategy (critical requirement)

Implemented in a dedicated `BookingService::book()`:

```
DB::transaction(function () {
    $slot = AppointmentSlot::where('id', $slotId)->lockForUpdate()->firstOrFail();
    abort_unless($slot->status === 'AVAILABLE', 409, 'slot no longer available');
    // ... ownership/child/date validation ...
    $appointment = Appointment::create([...]); // slot_id has a DB unique constraint
    $slot->update(['status' => 'BOOKED']);
    Notification for teacher created in the same transaction;
});
```

- `lockForUpdate()` takes a row-level exclusive lock on the slot inside a transaction, so a second concurrent request blocks until the first commits, then re-reads `status` and finds it already `BOOKED` — it fails cleanly instead of double-booking.
- The **unique constraint on `appointments.slot_id`** (and on `appointment_slots(teacher_id, date, start_time)`) is a second, independent safety net: even a bug that bypassed the lock would hit a DB constraint violation, caught and turned into the same user-facing message, rather than silently creating two active appointments.
- This is why **tests run against real MariaDB, not sqlite `:memory:`** (`phpunit.xml` updated in Phase 1) — sqlite's locking/concurrency model does not reflect production behavior, and the mandatory concurrent-booking test (spec §36) needs two genuinely concurrent connections racing for the same row lock.
- On conflict the user sees: *"Unfortunately, this appointment slot was just booked by another user. Please select another available slot."* — never a false confirmation.

## 5. Excel import strategy

A dedicated `ExcelImportService` (Phase 6) handles both teacher and guardian imports through the same staged pipeline (spec §22):

1. **Upload** → stored temporarily outside the public webroot (`storage/app/imports`, not `public/`), validated by MIME type, extension, and size before anything is parsed.
2. **Parse & validate** (no DB writes yet) → row-by-row validation (email format, required fields, valid role/class, duplicate emails *within the file*, guardian-email grouping so repeated rows become one guardian + N children) produces a `ValidatedImportRow[]` collection and an error list.
3. **Preview** → counts + first N errors shown to the admin before anything is committed.
4. **Confirm & commit** → wrapped in a DB transaction per logical unit (per guardian, or per teacher row) so a failure partway through doesn't leave a half-created guardian without their children; overall batch failure never corrupts previously-committed rows because each unit commits independently and errors are recorded, not thrown.
5. **Report** → `import_batches` + `import_errors` rows persisted; summary counts (created/skipped/failed) shown and downloadable as CSV, with temporary passwords never written to logs or the error report.

Existing accounts (matched by normalized, lowercased, trimmed email) are **skipped and reported**, never overwritten — matching the spec's safe default (§19).

## 6. Route structure (high level, finalized per phase)

```
/                          public marketing/login landing
/login, /register, /forgot-password, /reset-password   (Breeze, guardians register here; admin/teacher accounts are provisioned, not self-registered)
/dashboard                 role-based redirect to the correct dashboard

/guardian/...              guardian-only (middleware: role:guardian)
  children, teachers, book/{teacher}, appointments, notifications

/teacher/...               teacher-only (middleware: role:teacher)
  dashboard, availability, appointments, notifications

/admin/...                 admin-only (middleware: role:admin)
  users (teachers/guardians), children, appointments, availability,
  imports (teachers|guardians), imports/templates, imports/history, stats
```

No database IDs are exposed where avoidable in guardian-facing URLs beyond what's needed for routing (route-model binding still authorizes via policy on every hit).

## 7. Plesk / webhost.sch.gr deployment strategy (elaborated fully in Phase 10)

- No Docker, no Redis, no persistent Node/queue-worker process required.
- `npm run build` is a **local/CI build step**; only the compiled `public/build/*` assets are uploaded, not `node_modules`.
- Document root points at `/public`.
- Migrations run via Plesk's "Run Composer/Artisan" scheduled task feature or one-off SSH/Plesk console command; a documented fallback via phpMyAdmin SQL export is provided for hosts without Artisan console access.
- First admin created via `php artisan app:create-admin` (Phase 2), never hardcoded.
- Cron is only needed if/when Laravel's scheduler is used (e.g., future reminder emails) — not required for the MVP feature set, documented as optional.

## 8. Testing strategy

- PHPUnit against a real MariaDB **test** database (`lykeio_appointments_test`), not sqlite, specifically so lock-based concurrency tests are meaningful.
- Each phase ships Feature/Unit tests for that phase's functionality per spec §36; the mandatory concurrent-booking race test (two overlapping transactions racing for one slot, asserting exactly one `Appointment` row exists) is written in Phase 4 and must pass before Phase 4 is considered done.
