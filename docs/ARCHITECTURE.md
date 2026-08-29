# Architecture — Lyceum Parent–Teacher Appointment Booking System

Status: Phases 1–7 complete. This document is updated as later phases land.

## Phase 7 note — UI/UX polish

- **Custom error pages** (401/403/404/419/429/500/503) replace Laravel's defaults. Deliberately self-contained — no `@vite`, no Blade components — because the 500 page must still render a friendly message even if the compiled asset pipeline itself is what's broken. Verified end-to-end (not just the isolated view) with a test that forces `app.debug=false`, throws a real exception through a real route, and asserts the exception message never reaches the response.
- **Confirmation dialogs**: replaced every native `confirm()` (cancel appointment, remove child, remove availability window) with a reusable `<x-confirm-form-button>` — a proper `role="dialog"`/`aria-modal` element with Escape-to-close, click-outside-to-close, and focus moved to the Cancel button on open (and back to the trigger on Escape).
- **Loading states**: the three forms doing meaningful server work while the user waits (booking confirmation, import file upload, import commit) disable their submit button and swap its label the instant they're submitted, via a small `x-data="{ submitting: false }"` pattern — prevents accidental double-submit and gives feedback on slower connections.
- **Accessibility fixes found in the existing Breeze scaffolding**: two icon/avatar-only trigger buttons (the profile dropdown, the notification bell) had `focus:outline-none` with no replacement focus indicator — fixed by adding `focus:ring-2`. Slot-picker buttons (teacher availability grid, guardian booking date grid) were bumped to a 44px minimum touch target (`min-h-11`), matching the widely-used accessible touch-target guideline — the original `py-2` sizing measured closer to 32px.
- Verified via the dev server rather than a full interactive browser session (no browser-automation tool was available in this environment): confirmed the correct rebuilt CSS/JS assets are served, the dialog's ARIA/Alpine markup renders exactly as written, the loading-state markup is present on all three target forms, and the 404/403 custom pages render for real unauthenticated/cross-role requests.

## Phase 6 note — bulk Excel import

**Library swap**: dropped `maatwebsite/excel` (installed in Phase 1) in favor of using `phpoffice/phpspreadsheet` directly. Maatwebsite v4 is a very recent major rewrite whose API I could not verify with confidence; PhpSpreadsheet is the stable, well-documented library it wraps anyway, and our needs — read cells into arrays, write a simple sheet — don't benefit from the extra abstraction. This is a more predictable dependency for a Plesk deployment.

**Formula safety on import**: `Worksheet::toArray(..., calculateFormulas: false, ...)` — a cell containing `=SOMETHING()` is read back as that literal string, never evaluated, so it simply fails normal field validation (e.g. not a valid email) instead of executing anything (spec §25).

**Formula-injection safety on export**: every CSV cell (error reports, credential downloads, templates) is passed through a small `csvSafe()` that prefixes a value with `'` if it starts with `=`, `+`, `-`, or `@`, neutralizing the classic CSV/Excel formula-injection vector for whoever opens the downloaded file.

**Staged pipeline, matching spec §22 exactly**: upload → parse+validate (no DB writes) → preview (counts + per-row errors) → admin confirms → commit. The handoff between preview and commit is a server-side session entry keyed by an opaque UUID token (never a raw file path trusted from the client), pointing at the file stored under `storage/app/private/imports/pending` (outside the public webroot). Commit **re-reads and re-validates the file from scratch** rather than trusting the preview's cached result — this closes a TOCTOU gap (e.g. someone else creates a colliding account between preview and confirm) for free.

**Per-row/per-group resilience, not one giant transaction**: each teacher row (or each guardian's whole group of children) is created in its own small transaction with its own try/catch. One bad row can't roll back 99 good ones — a real all-or-nothing transaction around the whole batch would have violated spec §22's explicit requirement that partial success be possible and accurately reported.

**Guardian grouping**: rows are validated independently, then grouped by normalized (trimmed, lowercased) `guardian_email` only at commit time. A repeated email is never itself an error for guardians (unlike teachers, where spec's own error-table example shows a repeated email flagged `Duplicate`) — it's the intended multi-child signal. If the email already exists in the database, the **entire group is skipped** (no guardian mutation, no new children attached) — the conservative reading of spec §19's "SKIP existing account, never silently overwrite."

**Schema addition beyond the spec's literal column list**: `import_batches.skipped_rows`. Without it, "skipped because already exists" (a deliberate no-op) is indistinguishable from "failed because invalid" using only `total/successful/failed` — but spec §23's own result-summary example reports them as separate numbers.

**Temporary password delivery**: reuses `AccountProvisioningService` from Phase 2. Since SMTP is not guaranteed configured (spec §32), plaintext temporary passwords are never written to the database, logs, or `import_errors.row_data` — they exist only in memory during the commit request and are flashed to the *admin's own session* for a single CSV download (`/admin/imports/history/{batch}/credentials.csv`), consumed and cleared on first read. A second request for the same batch/session correctly 404s (verified manually and by test). This is a pragmatic middle ground given no guaranteed email delivery; the deployment guide will note the admin should delete the downloaded file after distributing credentials.

**Class validation reused app-wide**: added a `SchoolClass` enum (the 9 fixed Lyceum classes) and tightened `StoreChildRequest` (guardian's manual "add child" form, from Phase 2) to the same list — so a Lyceum's class vocabulary is validated consistently whether a child is added by hand or via bulk import.

## Phase 5 note — notification center

The write-path (`notifications` table, BookingService writing rows on booking/cancellation) already existed from Phase 4. This phase added the read-side UI, shared across all three roles rather than duplicated per-role, since nothing about "show my notifications" is role-specific:

- A class-based Blade component (`<x-notification-bell />`) queries the 5 most recent notifications and the unread count once per page render, and is dropped into the shared navigation partial — so every dashboard gets it for free with no per-controller wiring.
- The badge count then self-refreshes via a 30-second `setInterval` fetch to `GET /notifications/unread-count` (Alpine `x-data`/`x-init`, no page reload, no WebSockets) — satisfying spec §15's "polling/AJAX if appropriate" without a persistent connection.
- `NotificationPolicy` enforces that a user can only mark their *own* notifications read; `markAllAsRead` is scoped to `$request->user()->notifications()`, so it can never touch another user's rows even via a crafted request.
- The dedicated `/notifications` page is the actual "notification center" (spec §15) for the full history, paginated; the dropdown is a fast-access preview of the latest 5.

## Phase 4 note — booking engine (the critical phase)

`BookingService::book()` implements the exact pseudocode from spec §10: transaction → `lockForUpdate()` on the slot → verify `AVAILABLE` → create appointment → mark slot `BOOKED` → commit, throwing `App\Exceptions\SlotUnavailableException` (fixed message: *"Unfortunately, this appointment slot was just booked by another user..."*) both when the status check fails and — as a second, independent backstop — when a `QueryException` surfaces a unique-constraint violation or a lock-wait-timeout/deadlock. `BookingService::cancel()` mirrors this: lock the appointment, refuse to double-cancel, free the slot, notify the teacher.

**The DB-level unique constraint problem, and its resolution:** a plain `UNIQUE` on `appointments.slot_id` cannot work, because a cancelled appointment must let its slot be legitimately rebooked (spec §13/§27) — a second row with the same `slot_id` is exactly what a rebooking is. The fix is `active_slot_id`: a nullable column that mirrors `slot_id` while the appointment is `new`/`confirmed`, and is set to `NULL` on cancellation. MySQL/MariaDB treat `NULL` as distinct in a unique index, so `UNIQUE(active_slot_id)` gives a real, enforced-by-the-database guarantee ("at most one *active* booking per slot, ever") without blocking legitimate rebooking after cancellation.

**Schema pulled forward from Phase 5:** the `notifications` table (exact columns from spec §9) was built now, one phase earlier than originally planned, because `BookingService`'s step 9 ("create notification") must happen inside the same atomic transaction as the booking/cancellation itself — writing it later, outside the transaction, would let a booking succeed while its notification silently failed to be created, or vice versa. Phase 5 will build the actual notification center UI (badge, unread count, mark-as-read) on top of this table; the write-path is already correct and tested.

**Testing real concurrency, not a simulation:** `tests/Feature/Booking/ConcurrentBookingTest.php` deliberately does not use `RefreshDatabase` — that trait wraps each test in an uncommitted transaction, which would make its fixture rows invisible to a genuinely separate process. Instead it launches two independent PHP processes (`tests/Support/concurrent_booking_worker.php`, via Symfony Process) that both call the real `BookingService::book()` against the same committed slot row, synchronized through a file-based ready/go rendezvous so they race for the same row lock as closely as two real concurrent HTTP requests would. The test asserts exactly one process succeeds, the other receives the exact spec-mandated message, and the database ends up with exactly one appointment. This was manually verified outside PHPUnit too (two real background OS processes racing for the same slot) before being wired into the automated suite.

**Factory bug found and fixed along the way:** `AppointmentSlotFactory::definition()` originally called `Availability::factory()->create()` *eagerly*, so every `AppointmentSlot::factory()->create([...])` call silently created and orphaned an extra availability+teacher, even when the caller overrode `teacher_id`/`availability_id`. Harmless under `RefreshDatabase` (rolled back), but a real data leak for the non-transactional concurrency test. Fixed by making the defaults lazy factory relations (`User::factory()->teacher()` / `Availability::factory()`), which Laravel discards unresolved when overridden — the same pattern used correctly elsewhere in the codebase.

## Phase 3 note — availability & slots

`availability` (singular, matching the spec table name exactly) and `appointment_slots` were implemented as designed in §2/§4 below, via a dedicated `AvailabilityService` (mirrors the not-yet-built `BookingService`'s pattern of transaction + `lockForUpdate()` for anything that must be race-safe):

- Creating an availability window validates: not in the past, `end > start`, window length is a multiple of 5 minutes, and — inside a locked transaction — no overlap with another *active* window for the same teacher/day. Slots are generated in the same transaction.
- Removing a window (`AvailabilityService::deleteAvailability`) is blocked, under a row lock, if any of its slots are `booked` — matching §11's "never destructively modify already-booked appointments." The `appointment_slots.availability_id` FK is `cascadeOnDelete`, so once the guard passes, deleting the availability row cleans up its slots automatically.
- Toggling a single slot (`available` ↔ `disabled`) is likewise locked and refuses to touch a `booked` slot.
- All enum backing values across the schema are lowercase (`available`/`booked`/`disabled`, `active`/`cancelled`) for consistency with `UserRole`/`UserStatus` from Phase 2; human-facing casing comes from each enum's `label()` method, not the stored value.

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
