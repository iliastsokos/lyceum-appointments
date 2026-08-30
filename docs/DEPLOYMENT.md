# Deployment Guide — Plesk / webhost.sch.gr (SQLite variant)

This is the `sqlite-migration` branch's deployment guide. It differs from
`master`'s `docs/DEPLOYMENT.md` in exactly two ways: **no database server
to create or configure** (SQLite is a single file), and the **root
`.htaccess` redirect is the primary upload method**, not a fallback — for
hosts where the document root genuinely cannot be changed. Everything else
(PHP extensions, storage permissions, SSL, the admin-creation command) is
identical to the MySQL guide.

Two paths are documented throughout: **Plesk's web UI** (no SSH needed) and
**SSH**, where SSH makes a step meaningfully easier. Neither path requires
Docker, Redis, WebSockets, or a persistent Node.js process — see
`docs/ARCHITECTURE.md` for why.

## 1. Requirements

- **PHP 8.3 or newer** (the app requires `^8.3` in `composer.json`).
- **PHP extensions**: `bcmath`, `ctype`, `curl`, `dom`, `fileinfo`, `filter`,
  `hash`, `mbstring`, `openssl`, `pcre`, `pdo_sqlite`, `session`,
  `tokenizer`, `xml`, `zip`, `intl`. All but `zip`/`intl`/`pdo_sqlite` ship
  enabled in typical PHP builds; `zip` is required by the Excel
  import/export feature (PhpSpreadsheet) and `intl` is recommended by
  Laravel. In Plesk: **Websites & Domains → PHP Settings** (or **Tools &
  Settings → PHP Settings** for the whole server) → pick the PHP 8.3
  handler → tick `zip`, `intl`, and `pdo_sqlite` under Extensions if not
  already enabled.
- **No database server needed.** SQLite ships as part of PHP's `pdo_sqlite`
  extension — there's nothing to install, create, or credential.
- **Composer** (2.x) — run locally to produce `vendor/`, or via Plesk's
  "Composer" tool if the plan includes it (see §4).
- **Node.js + npm** — **build-time only**, not needed on the server at all if
  you build assets locally/in CI and upload the compiled output (recommended
  path below).
- SSL certificate — Plesk's free Let's Encrypt integration covers this (§8).

## 2. Prepare the release locally (recommended path)

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

Also create the SQLite database file itself before packaging, and run
migrations against it locally so the server receives an already-migrated
(but empty — no rows) schema:

```bash
touch database/database.sqlite
php artisan migrate --force
```

## 3. Upload the application

**This variant uploads directly into the domain's web root** (`httpdocs/`
or `public_html/`), not to a sibling directory outside it, because a root
`.htaccess` handles routing everything through `public/` — see §5.

### Option A — Plesk File Manager / Git (no SSH)

- **Git (preferred if available on your plan)**: Plesk's **Websites &
  Domains → Git** lets you point at this branch and pull on deploy, with
  the deployment path set to `httpdocs/` (or `public_html/`) directly.
- **File Manager upload**: zip the project directory (including `vendor/`,
  `public/build/`, and `database/database.sqlite` from §2, excluding
  `.git/`, `node_modules/`, `tests/`, and your local `.env`) and
  upload/extract it via **Websites & Domains → File Manager**, directly
  into `httpdocs/`.

### Option B — SSH

```bash
cd /var/www/vhosts/yourdomain.gr/httpdocs
git clone -b sqlite-migration <your-repo-url> .
composer install --no-dev --optimize-autoloader
npm ci && npm run build
touch database/database.sqlite
php artisan migrate --force
```

## 4. Environment configuration

Copy `.env.example` to `.env` on the server and fill in real values. Never
commit a real `.env` to version control.

```bash
cp .env.example .env
php artisan key:generate
```

(No SSH? Create `.env` via File Manager's "New File" and paste the contents,
then use Plesk's **Scheduled Tasks** — §7 — to run a one-off
`php artisan key:generate` command instead.)

Key values to set:

```
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-school-domain.gr
APP_TIMEZONE=Europe/Athens
APP_LOCALE=el
APP_FALLBACK_LOCALE=en

DB_CONNECTION=sqlite
# No DB_DATABASE/HOST/USERNAME/PASSWORD needed — config/database.php
# defaults DB_DATABASE to database/database.sqlite automatically.

SESSION_SECURE_COOKIE=true  # only once HTTPS is active (§8)
```

`APP_DEBUG=false` is not optional in production — with it `true`, error pages
can expose stack traces and file paths. The app ships a custom, friendly set
of error pages (`resources/views/errors/`) that render regardless of debug
mode, so there's no functional reason to leave debug on.

If the school has SMTP details, fill in `MAIL_MAILER=smtp` and the
`MAIL_HOST`/`MAIL_PORT`/`MAIL_USERNAME`/`MAIL_PASSWORD` fields — but this is
optional. With `MAIL_MAILER=log` (the default), every core feature (booking,
cancellation, bulk import, admin-driven password reset) still works; only
the self-service "forgot password" email is skipped, and the admin can
always issue a fresh temporary password directly from the teachers/guardians
list instead.

## 5. Routing: root `.htaccess` instead of a document-root change

Laravel's front controller lives in `public/index.php`, so it normally
needs the domain's **document root** pointed at `public/`. Many
webhost.sch.gr-style Plesk plans don't allow changing it. This variant
avoids that requirement entirely: a `.htaccess` file at the project root
(already included when you clone/extract this branch) transparently routes
every request into `public/`:

```apache
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteRule ^(.*)$ public/$1 [L]
</IfModule>

<FilesMatch "^\.env">
    Require all denied
</FilesMatch>
<FilesMatch "^(composer\.(json|lock)|artisan|\.gitignore|\.gitattributes|\.htaccess)$">
    Require all denied
</FilesMatch>
```

Nothing else to configure — as long as the project sits directly in
`httpdocs`/`public_html` (§3) and `mod_rewrite` is enabled (standard on
Plesk), visiting the domain serves `public/` invisibly. `app/`, `.env`,
`database/database.sqlite`, and everything else outside `public/` are never
directly reachable, since **every** request is rewritten into `public/`
before Apache considers serving a physical file.

**If your plan *does* allow changing the document root**, that's the
cleaner alternative: place the project outside the web root entirely (e.g.
`/var/www/vhosts/yourdomain.gr/lyceum_appointments/`), point **Websites &
Domains → Hosting Settings → Document root** at
`/lyceum_appointments/public`, and skip the root `.htaccess` — either
approach is equally secure.

## 6. Storage permissions

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
database/                  # SQLite writes -wal/-shm sidecar files here
database/database.sqlite   # the database file itself
```

Via SSH:

```bash
chmod -R 775 storage bootstrap/cache database
chown -R <plesk-web-user>:psacln storage bootstrap/cache database
```

Via Plesk File Manager (no SSH): select each of the folders/files above →
**Permissions** → ensure the web server user has read/write. Plesk's File
Manager usually applies sane ownership automatically on upload, but this is
the first thing to check if you see a "permission denied" / 500 error, or
"attempt to write a readonly database", after deployment.

## 7. Run migrations and create the first administrator

If you already ran `migrate` locally before packaging (§2), the schema is
already in place on upload — you only need to create the admin account.
Otherwise, run both.

### With SSH

```bash
php artisan migrate --force   # skip if already run locally before upload
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
php /var/www/vhosts/yourdomain.gr/httpdocs/artisan app:create-admin --first-name="..." --last-name="..." --email="admin@yourschool.gr" --password="..."
```

(use the non-interactive flags here, since a scheduled task has no terminal
to prompt into). Run it once, then delete or disable the task.

## 8. SSL / HTTPS

Plesk's **SSL/TLS Certificates** tab offers free Let's Encrypt certificates —
issue one for the domain and enable **"Redirect HTTP to HTTPS"**. Once HTTPS
is confirmed working, set `SESSION_SECURE_COOKIE=true` in `.env` (already
noted in §4) so session cookies are only ever sent over HTTPS — Laravel does
**not** set this automatically from `APP_URL`'s scheme.

## 9. Production configuration & caching

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

## 10. Cron / scheduler (optional, but recommended if available)

Nothing in this app **requires** a cron job to function — it runs correctly
on a host with no Scheduled Tasks at all (e.g. some teacher-level Plesk
subscriptions don't offer them; see §7's SSH-less admin-creation path and
the admin dashboard's "Εκτέλεση Ενημερώσεων Βάσης Δεδομένων" button, both
built specifically for that case). But if your host **does** offer Scheduled
Tasks, one task unlocks two automatic housekeeping jobs at once:

```
php /var/www/vhosts/yourdomain.gr/lyceum_appointments/artisan schedule:run
```

Add this as a Scheduled Task running **every minute** (Laravel's own
scheduler decides internally what's actually due — most minutes it does
nothing). This single task is enough to drive `routes/console.php`'s
`Schedule::command(...)` entries, both currently set to run daily:

- **`db:backup`** — snapshots the SQLite database file into
  `storage/app/backups/` (outside the public webroot) and keeps the 7 most
  recent snapshots, deleting older ones automatically. See §12.
- **`imports:clean-pending`** — deletes abandoned bulk-import upload files
  (an admin started an import preview but never confirmed it) older than 24
  hours. Pure data hygiene, not required for correctness — if this never
  runs, worst case a few small `.xlsx` files accumulate in
  `storage/app/private/imports/pending`, which never affects functionality.

Adding a future scheduled job later never needs a new Scheduled Task — just
add another `Schedule::command(...)` line in `routes/console.php` and the
same one-task-per-minute cron picks it up.

## 11. Production checklist

Run through this before announcing the system live:

- [ ] `.env`: `APP_ENV=production`, `APP_DEBUG=false`, real `APP_KEY` generated
- [ ] `APP_URL` matches the real domain (used for generating links)
- [ ] `APP_TIMEZONE=Europe/Athens`, `APP_LOCALE=el`
- [ ] Visiting `https://yourdomain.gr/.env` returns 403/404, never the file's contents
- [ ] `php artisan migrate --force` has been run (or was already run before packaging) and completed without errors
- [ ] First admin account created via `app:create-admin` (verify login works)
- [ ] SSL certificate active, HTTP→HTTPS redirect on, `SESSION_SECURE_COOKIE=true`
- [ ] `storage/`, `bootstrap/cache/`, `database/`, and `database/database.sqlite` are writable by the web server
- [ ] `storage/app/private` is confirmed **not** web-accessible (visit
      `https://yourdomain.gr/storage/app/private/imports/pending/` in a
      browser — it must 404, not list files)
- [ ] `public/build/` exists and pages render with styling (if it's missing,
      the build step in §2 wasn't uploaded — a page still renders, just
      unstyled, so this is easy to miss)
- [ ] Run the automated test suite one final time before going live: `php artisan test`
- [ ] Log in as the admin, a manually-created guardian, and a
      manually-created teacher account and walk the golden path once each:
      admin creates a teacher and a guardian → teacher sets availability →
      guardian adds a child, books an appointment → guardian cancels it
      (guardians and teachers are both admin-provisioned only — there is no
      public self-registration path for either role)
- [ ] Decide and document who has SSH/Plesk access, and confirm they know
      where `docs/ARCHITECTURE.md` and this file live

## 12. Backup recommendations

- **Database, automatic (recommended)**: if Scheduled Tasks are available
  (§10), `db:backup` runs daily and writes a snapshot to
  `storage/app/backups/database-YYYY-MM-DD_HHMMSS.sqlite`, keeping the 7
  most recent (`--keep=N` to change that). It uses SQLite's own
  `VACUUM INTO`, which produces a clean, consistent copy in one step —
  correct even mid-WAL-checkpoint, unlike a plain file copy, so there's no
  manual `PRAGMA wal_checkpoint` step to remember. `storage/app/` is not
  web-accessible, so these snapshots aren't publicly downloadable.
  Periodically copy the `storage/app/backups/` directory itself somewhere
  off-server (e.g. download via Plesk File Manager occasionally) — backups
  that live only on the same disk as the database don't protect against
  losing the whole hosting account.
- **Database, without a scheduler**: run `php artisan db:backup` by hand
  now and then (Plesk Scheduled Tasks §7's "Run PHP script" method works
  fine for a one-off run too), or rely on Plesk's own **Backup Manager**,
  which can include arbitrary files in its scheduled backups — point it at
  `database/database.sqlite` directly if `db:backup` isn't running
  automatically.
- **Uploaded files**: the app does not retain uploaded Excel files after a
  successful import (deleted immediately on commit — see
  `docs/ARCHITECTURE.md`'s Phase 6 notes) and never stores anything in
  `storage/app/public`, so there is no user-uploaded media library to back
  up beyond the database file itself.
- **`.env`**: keep a secure, offline copy of the production `.env` (e.g. in
  a password manager) — it is never committed to git and losing it means
  losing `APP_KEY` (which would invalidate all existing sessions and any
  encrypted data).
- Test the restore path at least once before relying on it — an untested
  backup is not a backup. Restoring is just copying a saved
  `database-*.sqlite` file back to `database/database.sqlite`.

## 13. Troubleshooting

**White page / 500 error, nothing else**
Check `storage/logs/laravel.log` first. If that file itself doesn't exist or
isn't writable, revisit §6 (storage permissions) — Laravel can't log the
real error if it can't write to `storage/logs`.

**"attempt to write a readonly database" / "unable to open database file"**
`database/` (the directory, not just the file) isn't writable by the web
server user — SQLite needs to create `-wal`/`-shm`/`-journal` sidecar files
alongside `database.sqlite`. Re-check §6.

**"The stream or file ... could not be opened" / permission errors in the log**
Storage or `bootstrap/cache` permissions (§6). Re-check ownership matches
the web server's user, not just your SSH/FTP user.

**Styling is missing but pages load (unstyled HTML)**
`public/build/` wasn't uploaded, or was uploaded to the wrong path. Re-run
§2 locally and re-upload `public/build/` — check
`public/build/manifest.json` exists on the server.

**Visiting the domain shows a directory listing or Plesk's default page instead of the app**
The root `.htaccess` from §5 is missing, not being read (`AllowOverride`
disabled — contact the host), or the project wasn't extracted directly into
`httpdocs`/`public_html`. Confirm `httpdocs/.htaccess` and
`httpdocs/public/index.php` both exist at those exact paths.

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
check `storage/logs/laravel.log` for the underlying database error and
report it rather than retrying repeatedly.

**Emails are not being sent**
Expected if `MAIL_MAILER=log` (the default, §4) — check
`storage/logs/laravel.log` to confirm the email content, then configure real
SMTP credentials once the school provides them. Every core feature works
without email configured, including password recovery (admin resets it
directly from the teachers/guardians list).
