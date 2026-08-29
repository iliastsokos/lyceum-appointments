# Deployment Guide — Plesk / webhost.sch.gr

This guide covers deploying the Lyceum Appointments application to a Plesk-managed
shared hosting account (the common setup for `webhost.sch.gr` and similar Greek
school hosting). Two paths are documented throughout: **Plesk's web UI** (no SSH
needed) and **SSH**, where SSH makes a step meaningfully easier. Neither path
requires Docker, Redis, WebSockets, or a persistent Node.js process — see
`docs/ARCHITECTURE.md` for why.

## 1. Requirements

- **PHP 8.3 or newer** (the app requires `^8.3` in `composer.json`).
- **PHP extensions**: `bcmath`, `ctype`, `curl`, `dom`, `fileinfo`, `filter`,
  `hash`, `mbstring`, `openssl`, `pcre`, `pdo_mysql`, `session`, `tokenizer`,
  `xml`, `zip`, `intl`. All but `zip`/`intl` ship enabled in typical PHP builds;
  `zip` is required by the Excel import/export feature (PhpSpreadsheet) and
  `intl` is recommended by Laravel. In Plesk: **Websites & Domains → PHP
  Settings** (or **Tools & Settings → PHP Settings** for the whole server) →
  pick the PHP 8.3 handler → tick `zip` and `intl` under Extensions if not
  already enabled.
- **MySQL or MariaDB** (5.7+/10.3+), provided by Plesk.
- **Composer** (2.x) — run locally to produce `vendor/`, or via Plesk's
  "Composer" tool if the plan includes it (see §5).
- **Node.js + npm** — **build-time only**, not needed on the server at all if
  you build assets locally/in CI and upload the compiled output (recommended
  path below).
- SSL certificate — Plesk's free Let's Encrypt integration covers this (§9).

## 2. Create the database in Plesk

1. **Websites & Domains** → your domain → **Databases** → **Add Database**.
2. Name it (e.g. `lyceum_appointments`), select **MySQL/MariaDB**.
3. Under **Database users**, create a dedicated user (e.g. `lyceum_app`) with a
   strong, generated password. Grant it access only to this database — never
   reuse the Plesk admin/root database account for the application.
4. Note the database name, username, password, and host (Plesk databases are
   usually reachable at `localhost` or `127.0.0.1` from the same server;
   confirm the exact host Plesk shows you — it varies by hosting setup).

## 3. Prepare the release locally (recommended path)

Building the frontend assets and installing Composer dependencies **locally**
before upload avoids needing Composer/Node available on the server at all,
which is the most portable option across different Plesk/shared-hosting
configurations.

```bash
composer install --no-dev --optimize-autoloader
npm ci
npm run build
```

This produces `vendor/` (PHP dependencies) and `public/build/` (compiled
CSS/JS — Vite's build output, referenced by the `@vite(...)` directive in the
Blade layouts). `node_modules/` is **not** needed on the server and should not
be uploaded.

## 4. Upload the application

### Option A — Plesk File Manager / Git (no SSH)

- **Git (preferred if available on your plan)**: Plesk's **Websites &
  Domains → Git** lets you point at a repository and pull on deploy. Set the
  deployment/document root to a subdirectory (see §6) since Laravel's public
  entry point is `public/`, not the project root.
- **File Manager upload**: zip the project directory (including `vendor/` and
  `public/build/` from §3, excluding `.git/`, `node_modules/`, and your local
  `.env`) and upload/extract it via **Websites & Domains → File Manager**.

Place the project **outside** the domain's public web root — e.g. at
`/var/www/vhosts/yourdomain.gr/lyceum_appointments/` — with only `public/`
exposed to the web (§6). This keeps `app/`, `.env`, `storage/`, and the
SQLite/MySQL credentials unreachable from a browser even if `.htaccess`
handling is ever misconfigured.

### Option B — SSH

```bash
cd /var/www/vhosts/yourdomain.gr
git clone <your-repo-url> lyceum_appointments
cd lyceum_appointments
composer install --no-dev --optimize-autoloader
npm ci && npm run build
```

## 5. Environment configuration

Copy `.env.example` to `.env` on the server and fill in real values. Never
commit a real `.env` to version control.

```bash
cp .env.example .env
php artisan key:generate
```

(No SSH? Create `.env` via File Manager's "New File" and paste the contents,
then use Plesk's **Scheduled Tasks** — §8 — to run a one-off
`php artisan key:generate` command instead.)

Key values to set:

```
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-school-domain.gr
APP_TIMEZONE=Europe/Athens

DB_CONNECTION=mysql
DB_HOST=127.0.0.1          # confirm the exact host Plesk assigned in §2
DB_DATABASE=lyceum_appointments
DB_USERNAME=lyceum_app
DB_PASSWORD=<the password generated in §2>

SESSION_SECURE_COOKIE=true  # only once HTTPS is active (§9)
```

`APP_DEBUG=false` is not optional in production — with it `true`, error pages
can expose stack traces and file paths. The app ships a custom, friendly set
of error pages (`resources/views/errors/`) that render regardless of debug
mode, so there's no functional reason to leave debug on.

If the school has SMTP details, fill in `MAIL_MAILER=smtp` and the
`MAIL_HOST`/`MAIL_PORT`/`MAIL_USERNAME`/`MAIL_PASSWORD` fields — but this is
optional. With `MAIL_MAILER=log` (the default), every core feature (booking,
cancellation, bulk import) still works; only outbound email is skipped.

## 6. Document root

Point the domain's **document root** at the project's `public/` directory —
**not** the project root. In Plesk: **Websites & Domains → Hosting Settings →
Document root** → set it to (for example)
`/lyceum_appointments/public` relative to the vhost root.

If your plan doesn't allow changing the document root (rare, but some
budget shared-hosting panels lock it to the vhost root), the fallback is to
upload the whole app to the vhost root and use a `.htaccess` redirect/rewrite
so all requests are served from `public/` — but changing the document root is
strongly preferred and avoids this workaround entirely.

## 7. Storage permissions

The web server user needs write access to:

```
storage/
storage/app
storage/app/private        # uploaded Excel imports land here — never public
storage/framework
storage/framework/cache
storage/framework/sessions
storage/framework/views
storage/logs
bootstrap/cache
```

Via SSH:

```bash
chmod -R 775 storage bootstrap/cache
chown -R <plesk-web-user>:psacln storage bootstrap/cache
```

Via Plesk File Manager (no SSH): select each of the folders above →
**Permissions** → ensure the web server user has read/write. Plesk's File
Manager usually applies sane ownership automatically on upload, but this is
the first thing to check if you see a "permission denied" / 500 error after
deployment.

## 8. Run migrations and create the first administrator

### With SSH

```bash
php artisan migrate --force
php artisan app:create-admin
```

`app:create-admin` prompts interactively for the admin's name, email, and
password (or pass them non-interactively: `--first-name=`, `--last-name=`,
`--email=`, `--password=`) — this is the **only** way to create an admin
account; there is deliberately no public registration path to the admin
role (spec §7).

### Without SSH — Plesk Scheduled Tasks

Plesk's **Websites & Domains → Scheduled Tasks** (cron) can run one-off PHP
commands even without an interactive shell. Add a task running:

```
php /var/www/vhosts/yourdomain.gr/lyceum_appointments/artisan migrate --force
```

Run it once, then either delete the task or leave it disabled after — it's
idempotent (`migrate` skips already-run migrations), but there's no need to
run it repeatedly. Repeat with:

```
php /var/www/vhosts/yourdomain.gr/lyceum_appointments/artisan app:create-admin --first-name="..." --last-name="..." --email="admin@yourschool.gr" --password="..."
```

for the admin account (use the non-interactive flags here, since a scheduled
task has no terminal to prompt into).

## 9. SSL / HTTPS

Plesk's **SSL/TLS Certificates** tab offers free Let's Encrypt certificates —
issue one for the domain and enable **"Redirect HTTP to HTTPS"**. Once HTTPS
is confirmed working, set `SESSION_SECURE_COOKIE=true` in `.env` (already
noted in §5) so session cookies are only ever sent over HTTPS — Laravel does
**not** set this automatically from `APP_URL`'s scheme.

## 10. Production configuration & caching

After the first successful deploy (and after every subsequent code deploy),
cache the framework's config/routes/views for a real performance win on
shared hosting:

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

**Important**: `config:cache` freezes the current `.env` values into a cached
file — if you change `.env` afterward, run `php artisan config:clear` (or
re-run `config:cache`) or the app will keep using the old values. If you
don't have SSH, skip these three commands — the app runs correctly without
them, just slightly slower per request; they are a pure optimization, not a
requirement.

## 11. Cron / scheduler (optional)

Nothing in this app **requires** a cron job to function — no queued jobs,
no scheduled reminders exist yet (see `docs/ARCHITECTURE.md`'s extensibility
notes). One optional housekeeping task exists:

```
php artisan imports:clean-pending
```

This deletes abandoned bulk-import upload files (an admin started an import
preview but never confirmed it) older than 24 hours — pure data hygiene, not
required for correctness. If Plesk's Scheduled Tasks are available, add it
to run daily; if not, it's safe to simply never run it (worst case, a few
small `.xlsx` files accumulate in `storage/app/private/imports/pending`,
which never affects functionality since real imports always clean up after
themselves).

## 12. Production checklist

Run through this before announcing the system live:

- [ ] `.env`: `APP_ENV=production`, `APP_DEBUG=false`, real `APP_KEY` generated
- [ ] `APP_URL` matches the real domain (used for generating links, e.g. in
      future email notifications)
- [ ] `APP_TIMEZONE=Europe/Athens`
- [ ] Database credentials are a dedicated, least-privilege user — not the
      Plesk admin account
- [ ] `php artisan migrate --force` has been run and completed without errors
- [ ] First admin account created via `app:create-admin` (verify login works)
- [ ] SSL certificate active, HTTP→HTTPS redirect on, `SESSION_SECURE_COOKIE=true`
- [ ] `storage/` and `bootstrap/cache/` are writable by the web server
- [ ] `storage/app/private` is confirmed **not** web-accessible (visit
      `https://yourdomain.gr/storage/app/private/imports/pending/` in a
      browser — it must 404, not list files)
- [ ] `public/build/` exists and pages render with styling (if it's missing,
      the build step in §3 wasn't uploaded — a page still renders, just
      unstyled, so this is easy to miss)
- [ ] Run the automated test suite one final time against a disposable
      database before going live: `php artisan test`
- [ ] Log in as the admin, guardian (via self-registration), and a
      manually-created teacher account and walk the golden path once each:
      admin creates a teacher → teacher sets availability → guardian
      registers, adds a child, books an appointment → guardian cancels it
- [ ] Decide and document who has SSH/Plesk access, and confirm they know
      where `docs/ARCHITECTURE.md` and this file live

## 13. Backup recommendations

- **Database**: Plesk's **Backup Manager** can schedule automatic MySQL
  dumps — enable at least a daily backup with a retention window your host's
  disk quota allows. This is the single most important backup: it holds
  every guardian, child, appointment, and availability record.
- **Uploaded files**: the app does not retain uploaded Excel files after a
  successful import (deleted immediately on commit — see
  `docs/ARCHITECTURE.md`'s Phase 6 notes) and never stores anything in
  `storage/app/public`, so there is no user-uploaded media library to back
  up beyond the database itself.
- **`.env`**: keep a secure, offline copy of the production `.env` (e.g. in
  a password manager) — it is never committed to git and losing it means
  losing `APP_KEY` (which would invalidate all existing sessions and any
  encrypted data) along with the database password.
- Test the restore path at least once before relying on it — an untested
  backup is not a backup.

## 14. Troubleshooting

**White page / 500 error, nothing else**
Check `storage/logs/laravel.log` first. If that file itself doesn't exist or
isn't writable, revisit §7 (storage permissions) — Laravel can't log the
real error if it can't write to `storage/logs`.

**"The stream or file ... could not be opened" / permission errors in the log**
Storage or `bootstrap/cache` permissions (§7). Re-check ownership matches
the web server's user, not just your SSH/FTP user.

**Styling is missing but pages load (unstyled HTML)**
`public/build/` wasn't uploaded, or was uploaded to the wrong path. Re-run
§3 locally and re-upload `public/build/` — check
`public/build/manifest.json` exists on the server.

**"SQLSTATE[HY000] [1045] Access denied for user"**
Database credentials in `.env` don't match what Plesk created in §2, or
`config:cache` (§10) is serving stale cached credentials after you edited
`.env` — run `php artisan config:clear`.

**Migrations fail with a foreign key error**
This should not happen on a fresh database (migrations run in dependency
order), but if migrations were ever run out of order or a table was
manually altered, restoring from a backup and re-running
`migrate --force` on a clean database is safer than hand-patching schema.

**Import upload fails with "This file could not be read"**
This is the app's own friendly message for a corrupted or non-spreadsheet
file (see `docs/ARCHITECTURE.md`'s Phase 8 notes) — ask the admin to
re-export the file from Excel/LibreOffice as `.xlsx` and retry. If genuine
`.xlsx` files consistently fail, check that the `zip` PHP extension (§1) is
actually enabled — `php -m | grep zip` via SSH, or check **PHP Settings →
Extensions** in Plesk.

**Booking says "this slot was just booked by another user" even when testing alone**
This is the intended behavior of the double-booking protection working
correctly if two tabs/requests raced for the same slot — not a bug. If it
happens on a *single, uncontended* booking attempt, that's a real issue:
check `storage/logs/laravel.log` for the underlying database error (most
likely a locking/timeout issue if the database connection is unusually
slow) and report it rather than retrying repeatedly.

**Emails are not being sent**
Expected if `MAIL_MAILER=log` (the default, §5) — check
`storage/logs/laravel.log` to confirm the email content, then configure real
SMTP credentials once the school provides them. Every core feature works
without email configured.
