# Production Deployment — challan.org (Hostinger)

Code is deployed via GitHub → server auto-pull. Two things are **not** in the
repo (by design) and must be set up on the server **once**: the `.env` file and
the database contents. Follow the steps below.

> Secrets (`.env`) and the DB dump live in the local, git-ignored `deploy/`
> folder. They are **never** committed, so they never become web-accessible.

---

## 1. Environment file (`.env`)

The repo does not include `.env` (it is gitignored). Create it on the server.

1. Open **hPanel → File Manager** → `public_html`.
2. Create a new file named `.env`.
3. Paste the contents of your local `deploy/.env.production` into it and save.

Key production values it sets:
- `CI_ENVIRONMENT = production` (hides detailed errors)
- `app.baseURL = 'https://challan.org/'`
- `database.default.hostname = localhost`
- `database.default.database = u930296518_erp_admin`
- `database.default.username = u930296518_erpadmin`
- `database.default.password = <your DB password>`
- a unique `encryption.key`

---

## 2. Database import

The `erp_admin` DB is empty. Import the schema + seed data:

1. hPanel → **Databases → phpMyAdmin** → open `u930296518_erp_admin`.
2. **Import** tab → choose your local `deploy/database.sql` → **Go**.

This creates all 11 tables, seeds user types / roles / modules / permissions,
and creates the login accounts. The import also sets a **strong unique password
for `superadmin`** (see the value handed to you separately — not stored in git).

> If you have SSH instead, you can skip the import and run:
> `php spark migrate && php spark db:seed DatabaseSeeder`

---

## 3. Document root / public folder

CodeIgniter's front controller is `public/index.php`. The repo's root
`.htaccess` transparently forwards all requests into `public/`, so deploying the
whole project into `public_html` works out of the box at `https://challan.org/`.

**More secure alternative** (recommended if hPanel lets you set the domain's
document root): point challan.org's root to `public_html/public` so only the
`public/` folder is web-exposed.

Ensure the web server can write to `writable/`:
```
chmod -R 775 writable
```

---

## 4. First login & hardening

1. Visit `https://challan.org/` → redirects to the login page.
2. Sign in as **superadmin** with the strong password provided separately.
3. **Immediately**:
   - Change the superadmin password from **My Profile**.
   - Delete or deactivate the demo accounts `admin`, `manager`, `staff`,
     `viewer` (they ship with a known demo password) — **Users** module.
4. Confirm `CI_ENVIRONMENT=production` (no debug toolbar, generic error pages).

---

## 5. Redeploying code later

Because the server auto-pulls from GitHub `main`, shipping code changes is just:
```
git add -A && git commit -m "..." && git push origin main
```
`.env`, `writable/*`, and `deploy/` are gitignored, so deploys never touch your
server secrets, uploads, logs, or the DB dump.

For a schema change, add a migration and either re-import an updated
`deploy/database.sql` or run `php spark migrate` on the server (SSH).
