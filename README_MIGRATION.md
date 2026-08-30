# TrackmeNew — CI4 app (migration target)

CodeIgniter **4.7.4** on **PHP 8.3.30**. This is the CI4 destination for the
strangler migration (roadmaps in the CI3 repo at
`D:\xampp\htdocs\TrackmeNew\AI\`). It runs on the **same MySQL** as the live CI3
app — no data copy.

**Location:** `D:\laragon\www\trackme_ci4\` (in Laragon's www, served by PHP 8.3).
**Live URL:** http://localhost:8077/trackme_ci4/public/ (Apache :8077).
This is **outside** the CI3 git repo — version it separately (its own repo or a
subtree) when you start committing CI4 work.

## Status: P0 foundation — DONE ✅

Verified working (visit `/health`):
- Framework 4.7.4 boots on PHP 8.3.30.
- **default** DB group connects to `u930296518_mykisandata` (QB read OK — `invoice_system` = 1,625 rows).
- `fyContext` service + `app` / `cr_cache` helpers load.
- `old` / `challan` groups are **production-only** — expected to FAIL on local XAMPP (those DBs don't exist here).

## What's scaffolded

| Piece | File |
|---|---|
| DB config (3 groups) | `app/Config/Database.php` + `.env` |
| Multi-tenant context (CI3 `fy()`/`currentuserinfo()`) | `app/Libraries/FyContext.php` + service in `app/Config/Services.php` |
| Guard filters (replace `MY_Controller`) | `app/Filters/{AdminAuth,FyContext,Traffic,Rbac,SalaryCron,ApiAuth}Filter.php` (aliases in `app/Config/Filters.php`) |
| Ported helpers | `app/Helpers/app_helper.php`, `app/Helpers/cr_cache_helper.php` |
| BaseController (helpers + `$this->fy`) | `app/Controllers/BaseController.php` |
| Module discovery (replaces HMVC) | `App\Modules` PSR-4 in `app/Config/Autoload.php` |
| Admin layout skeleton | `app/Views/layouts/admin.php` (Metronic theme retained — T-017) |
| Self-test | `app/Controllers/Health.php` + `app/Views/health.php` → `/health` |

## Run

**Via Laragon Apache (default, no server to start):**
open http://localhost:8077/trackme_ci4/public/health

**Via the dev server (PHP 8.3, NOT the XAMPP 5.6 CLI):**
```bash
D:/laragon/bin/php/php-8.3.30-Win32-vs16-x64/php.exe spark serve --port 8091
```
`spark` / Composer / PHPUnit must always run with the **8.3** binary above.

## Important TODOs before anything goes live

- **Filters are OFF** (`app/Config/Filters.php $filters` is commented) so the
  health page runs before Auth exists. Turn on `adminAuth/fyContext/traffic/
  rbac/salaryCron` for `admin/*` (and `apiAuth` for `api_services/*`) once **P2
  Auth + RBAC** are ported. The `RbacFilter` currently PASSES THROUGH — it must
  be completed (port `permission_helper.php`) before exposing admin modules.
- Confirm the session keys `FyContext` reads (`userinfo`, `template_id`, `FY`,
  `product_type`) against the CI3 login flow, and wire the **shared session**
  store so `:80` (CI3) ↔ `:8077` (CI4) logins interoperate (P0-T011).
- Generate an encryption key: `spark key:generate`.
- Fill real secrets in `.env` (FCM, API keys); never commit `.env`.
- Remove the `Health` controller/view + `/health` routes before go-live.

## Next: T-017 + P1

- **T-017**: copy the Metronic `assets/` tree from the CI3 app into `public/assets/`
  (same relative paths); port `layout.php` → `layouts/admin.php` head/JS includes
  and the `elements/*` partials (dual menu, header, footer, notification).
- **P1**: migrate the public modules (`seo`, `letter_verify`, `invoice_verify`,
  `pages`, …) as the first real vertical slice, then flip their routes on `:8077`.
