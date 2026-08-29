# Production Deployment - hissabkitaab.com (Hostinger)

Code is deployed via GitHub to the server auto-pull. Two things are not stored
in the repo and must be set up on the server once: the `.env` file and the
database contents.

Secrets (`.env`) and DB dumps belong in the local, git-ignored `deploy/`
folder. Never commit them.

## 0. If the whole site shows "Whoops! We seem to have hit a snag"

That is CodeIgniter's production error page. The #1 cause on a fresh deploy is a
**missing or wrong `.env` database block**: `app/Config/Database.php` ships with
blank credentials, so with no `.env` every page (login included) throws
`Unable to connect to the database` → HTTP 500 site-wide.

Fix = create `.env` in `public_html` with the correct Hostinger MySQL creds
(section 1). You do **not** need to run migrations by hand: the `autosetup`
before-filter auto-runs pending migrations and seeds the baseline super-admin on
the first request once the DB connects. To see the real error while debugging,
temporarily set `CI_ENVIRONMENT = development`, reload, then set it back to
`production`. The exact exception is also in `writable/logs/log-YYYY-MM-DD.php`.

## 1. Environment File

Create `.env` on the server in `public_html`.

Key production values:

- `CI_ENVIRONMENT = production`
- `app.baseURL = 'https://hissabkitaab.com/'`
- `app.forceGlobalSecureRequests = true`
- `database.default.hostname = localhost`
- `database.default.database = u930296518_erp_admin`
- `database.default.username = u930296518_erpadmin`
- `database.default.password = <your DB password>`
- `encryption.key = <unique generated key>`

Before running `DatabaseSeeder` in production, also set:

```
seed.superAdmin.name = 'Super Administrator'
seed.superAdmin.email = 'owner@example.com'
seed.superAdmin.username = 'superadmin'
seed.superAdmin.mobile = '9000000001'
seed.superAdmin.password = '<strong unique password>'
```

## 2. Database Import

Import the production-clean schema and data:

1. Open hPanel, then Databases, then phpMyAdmin.
2. Select `u930296518_erp_admin`.
3. Use Import and choose your local `deploy/database.sql`.

This creates the schema, seeds user types, roles, modules, permissions, and
creates only the initial super-admin account. Demo user accounts are not seeded.

With SSH, you can instead run:

```
php spark migrate
php spark db:seed DatabaseSeeder
```

For an existing local or staging database, keep only the production owner before
exporting:

```
php spark data:prepare-production --keep-user <username-or-email>
php spark data:prepare-production --keep-user <username-or-email> --force
```

## 3. Document Root

CodeIgniter's front controller is `public/index.php`. The root `.htaccess`
forwards requests into `public/`, so deploying the whole project into
`public_html` works.

The more secure option is to point the domain document root to
`public_html/public`, so only the public folder is web-exposed.

Ensure the server can write to `writable/`:

```
chmod -R 775 writable
```

## 4. First Login

1. Visit `https://hissabkitaab.com/`.
2. Sign in with the seeded super-admin account.
3. Change the super-admin password from My Profile if it was shared during setup.
4. Confirm there are no demo/test users in the Users module.
5. Confirm `CI_ENVIRONMENT=production` so detailed errors and debug tools are off.

## 5. Redeploying Code

The server auto-pulls from GitHub, so shipping code changes is:

```
git add -A
git commit -m "..."
git push origin main
```

`.env`, `writable/*`, and `deploy/` are gitignored, so deploys do not touch
server secrets, uploads, logs, or DB dumps.

For schema changes, add a migration and either import an updated
`deploy/database.sql` or run `php spark migrate` on the server.

> **After this deploy, run migrations** so the invoice-number unique key lands:
> `php spark migrate` (adds `uq_invoice_no` on `invoices`). It is guarded/idempotent.

## 6. Production Security Hardening

Findings from `SECURITY-REVIEW.md`. The **code-level** ones are already fixed in the
repo and apply automatically in production — no action needed:

- **F-1 (High)** stored-XSS via attachment preview — fixed (upload allowlist + safe,
  `nosniff` serving).
- **F-2 (Medium)** `Secure` session/CSRF cookies — auto-on because `Cookie::$secure`
  is now `(ENVIRONMENT === 'production')`. Requires the site to actually be served
  over HTTPS (it is).
- **F-4 (Low)** CSRF token randomization — on globally.

The remaining items are **`.env` / config settings on the production host** — set them
once, per environment:

1. **F-3 — environment.** `CI_ENVIRONMENT = production` (already in the checklist above).
   This hides stack traces, the debug toolbar, and detailed DB errors.
2. **F-6 — force HTTPS.** In the production `.env`:
   ```
   app.forceGlobalSecureRequests = true
   ```
   Redirects HTTP→HTTPS and enables HSTS. **Keep this `false` in local dev** (plain HTTP).
3. **F-5 — Content-Security-Policy** (defense-in-depth). Roll out in **report-only**
   first so nothing breaks while you find violations:
   - `app/Config/App.php` → `$CSPEnabled = true`
   - `app/Config/ContentSecurityPolicy.php` → `$reportOnly = true`, then allow the app's
     real sources (`'self'`, Google Fonts, Google Translate, inline styles/scripts the
     views rely on).
   - Watch the browser console / report endpoint for violations across the main pages,
     tighten the directives, and only then set `$reportOnly = false` to enforce.

**Verify after deploy:** a bad route shows a generic error page (no stack trace);
`Set-Cookie` for `ci_session` carries `Secure; HttpOnly; SameSite=Lax`; an `http://`
request 301-redirects to `https://`.
