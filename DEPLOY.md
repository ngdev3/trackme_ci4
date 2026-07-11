# Production Deployment - challan.org (Hostinger)

Code is deployed via GitHub to the server auto-pull. Two things are not stored
in the repo and must be set up on the server once: the `.env` file and the
database contents.

Secrets (`.env`) and DB dumps belong in the local, git-ignored `deploy/`
folder. Never commit them.

## 1. Environment File

Create `.env` on the server in `public_html`.

Key production values:

- `CI_ENVIRONMENT = production`
- `app.baseURL = 'https://challan.org/'`
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

1. Visit `https://challan.org/`.
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
