# DEEP WEB TESTING REPORT — TrackmeNew (CI4)

**Target:** `http://localhost:8077/trackme_ci4/public` (CodeIgniter 4.7.4, PHP 8.3.30, Laragon)
**Scope:** Authorized full-project security + functional + QA audit of the owner's own application.
**Method:** Source-code map → live runtime testing with two authenticated sessions (Super Admin + low-privilege user) via curl, plus browser verification. Non-destructive PoC only — no data wiped, no DoS, no attacks outside this local instance.
**Date:** 2026-09-03

Test accounts used:
- **Super Admin** — `admin@yopmail.com` (user id 2, `isSuperAdmin=1`, firm 27→22).
- **Low-priv** — `shobhithemant@gmail.com` (user id 1020, regular user, firm 20, 33 module perms).

---

## 1. Executive summary

| Severity | Count | Fixed | Open |
|---|---|---|---|
| 🔴 CRITICAL | 0 | 0 | 0 |
| 🟠 HIGH | 4 | 1 | 3 |
| 🟡 MEDIUM | 4 | 1 | 3 |
| 🔵 LOW | 2 | 0 | 2 |
| 🟢 PASSED | 11 | — | — |

No critical (data-loss / full-takeover) issues were found. The most important **open** items are deployment-hardening concerns (dev mode, disabled CSRF, md5 passwords) that stem from the ongoing CI3→CI4 migration and are documented with remediation paths. Two concrete gaps were **fixed and retested** this session.

---

## 2. Fixes applied this session (verified)

### 🟠 SEC-001 — Traffic log accessible to any authenticated user  → FIXED
- **Module / URL:** `admin/traffic`, `admin/traffic/view_all` (POST, DataTables JSON)
- **Severity:** HIGH (broken access control + information disclosure)
- **Problem:** The page-traffic viewer exposes **every** user's activity — username, visited URL, timestamp, and **internal IP** — for all 172 logged visits. `traffic` is not a key in `erp_module_registry()`, and `RbacFilter` **fails open** for unknown keys, while the `Traffic` controller had no self-gate. Any logged-in user (incl. a 33-perm regular user) could read it.
- **Steps to reproduce:** As low-priv user → `POST /admin/traffic/view_all {draw,start,length}` → returned `recordsTotal:172` with all users' IPs/URLs.
- **Expected:** Super-Admin only (in CI3 this data lives inside the Super-Admin-only Monitor).
- **Impact:** Staff activity + internal network IP disclosure to unprivileged users.
- **Root cause:** No server-side authorization on a module the RBAC filter doesn't gate.
- **Fix applied:** Added a `guard()` in `Traffic` that requires `erp_is_super_admin()`; returns `permission_denied` for page requests and a `403 {status:denied}` for AJAX.
- **Files changed:** `app/Modules/Admin/Controllers/Traffic.php`
- **Retest:** low-priv → **302 → /permission_denied** (page) and **403 "Super Admin only."** (AJAX); Super-Admin → **200** (unchanged). ✅ PASS

### 🟡 SEC-002 — Missing HTTP security headers  → FIXED
- **URL:** all responses
- **Severity:** MEDIUM (clickjacking / MIME-sniffing exposure)
- **Problem:** No `X-Frame-Options`, `X-Content-Type-Options`, `Referrer-Policy`, etc. (`secureheaders` filter was commented out).
- **Fix applied:** Enabled the framework `secureheaders` after-filter globally.
- **Files changed:** `app/Config/Filters.php`
- **Retest:** responses now carry `X-Frame-Options: SAMEORIGIN`, `X-Content-Type-Options: nosniff`, `X-Download-Options: noopen`, `X-Permitted-Cross-Domain-Policies: none`, `Referrer-Policy: same-origin`. ✅ PASS

---

## 3. Open findings (documented — not auto-fixed, with reasons)

### 🟠 SEC-003 — CSRF protection disabled globally
- **Evidence:** `app/Config/Filters.php` → `csrf` commented out in `$globals['before']`. All state-changing POSTs (login, add/edit/delete, `change_fy`, `save_entry`, retention, etc.) accept cross-site forged requests. `SameSite=Lax` on the session cookie gives partial mitigation (blocks cross-site POST from a top-level navigation in modern browsers, but not all vectors).
- **Why not auto-fixed:** Every ported CI3 form/AJAX call omits a CSRF token; turning CSRF on globally would break **all** POST flows at once. This needs a coordinated pass (add `csrf_field()` / a JS header to every form + AJAX). 
- **Recommendation:** Enable `csrf` and inject the token app-wide (a single `$.ajaxSetup` header + a hidden field in the shared form partials), then retest each module. Track as a dedicated migration task.

### 🟠 SEC-004 — Application running in `development` environment
- **Evidence:** `.env` → `CI_ENVIRONMENT = development`. Unmatched routes and exceptions render CI4's **debug page** (framework version, file paths, stack traces); the debug toolbar is emitted. Confirmed on bad routes.
- **Impact:** Verbose error / path / stack-trace disclosure if shipped as-is.
- **Recommendation:** Set `CI_ENVIRONMENT = production` on the live host (Hostinger). This alone suppresses stack traces and the toolbar. **Local dev stays development.**

### 🟠 SEC-005 — Passwords hashed with MD5
- **Evidence:** `AuthModel::loginAuthorize()` compares `md5($password)`; forgot-flow writes `md5()`.
- **Why not changed:** The CI3 app is still live against the **same shared database** and compares md5 — rehashing here would lock users out of CI3 (documented in `AuthModel`'s header, roadmap risk R-7).
- **Recommendation:** Migrate to `password_hash()` with a transparent upgrade-on-login only **after** CI3 is retired.

### 🟡 SEC-006 — RBAC filter fails open for unregistered modules
- **Evidence:** `RbacFilter::before()` → `if (! isset($registry[$module])) return;` (unknown segment ⇒ allowed).
- **Impact:** Any `admin/<module>` not in `erp_module_registry()` is reachable by every authenticated user. Today the sensitive unregistered controllers **self-gate** (`Entry_trace`, `Gst_setting` → `permission_denied`; `Traffic` now gated by SEC-001; `app_setting` is per-user personalisation; `web_push/config` is intentionally public). So no *current* leak remains, but the design is fragile — a future unregistered admin controller would be open by default.
- **Recommendation:** Flip to **default-deny** for `admin/*` after auditing the handful of legitimately-open keys (`profile`, `app_setting`, `web_push/config`, `help`), or require every admin controller to be registry-listed (CI3 golden rule #6).

### 🟡 SEC-007 — No login rate-limiting / brute-force protection
- **Evidence:** `Auth::login()` has no attempt counter or lockout/throttle; repeated wrong passwords are accepted indefinitely.
- **Impact:** Online password guessing.
- **Recommendation:** Add per-IP + per-email throttling (e.g., CI4 Throttler) and optional lockout after N failures.

### 🟡 SEC-008 — Session cookie missing `Secure`; object IDs are reversible obfuscation
- **Evidence:** `Set-Cookie: ci_session=…; HttpOnly; SameSite=Lax` — no `Secure` (acceptable on http-localhost, **required** on HTTPS prod). `ID_encode()` = `rand4 . (id+19) . rand4`, trivially reversible by `ID_decode`; it is obfuscation, **not** an authorization control.
- **Recommendation:** Set `Config\Cookie::$secure = true` in production. Never rely on encoded IDs for access control — always scope queries by owner/firm server-side (see §4).

### 🔵 SEC-009 — Hardcoded admin credentials in the login view (dev-only)
- **Evidence:** `app/Modules/Auth/Views/login.php` autofills `admin@yopmail.com / Sunrise@5853` when `$_SERVER['SERVER_NAME'] == 'localhost'`. This is gated to localhost and mirrors CI3, but the literal is in source.
- **Recommendation:** Move the dev convenience behind an env flag, or remove before production.

### 🔵 SEC-010 — Letter Pad shows all firms' letters / view-by-id unscoped (pre-existing, by design)
- **Evidence:** `LetterPadModel::view($id)` filters by `id` + `status`, no `template_id`; the listing joins all firms (firm filter is optional). **This matches the CI3 original exactly** — Letter Pad is a cross-firm admin tool with a per-letter firm-picker, gated by the `letter_pad` RBAC permission.
- **Recommendation:** Accepted as designed. If per-firm isolation is later required, scope `view()`/`getData()` by `fy()->template_id` in **both** CI3 and CI4 together.

---

## 4. Data isolation / multi-tenancy (CRITICAL focus) — result: PASS (with notes)

- **`aa_attendance`** (template_id+FY): scoped to current firm in `Attendance::viewAll()`. ✅
- **Monitor entry-audit** (`aa_entry_trace`, template_id+FY): now scoped to the selected firm (added earlier this session; verified Aug 1,338 all-firms → 92 for firm 22). ✅
- **`daily_traffic`, `aa_login_detail`, `aa_login_attempts`**: no firm column — global by design (page views / logins are not per-firm). ℹ️
- **`aa_employees`, `aa_account_name`**: no firm column — global/shared by design in the old project (per-firm data lives in `aa_ledger`). ℹ️
- **No per-record cross-firm read surface reachable:** the ported `Account` controller exposes only the combined-entry add page (no `edit/<id>` yet), so the rokad IDOR surface does not exist in CI4 yet. When per-record rokad edit is ported, its model query **must** include `template_id`+`FY` (golden rule #4) — flagged for that future work.

---

## 5. Authentication — result: PASS

| Test | Result |
|---|---|
| Unauthenticated `admin/*` | 302 → `admin/auth?redirect=…` ✅ |
| Wrong password | 303 → back to `admin/auth`; session stays unauthenticated (dashboard→302) ✅ |
| Unknown user / empty / invalid email | rejected, no auth ✅ |
| Disabled/Inactive account | rejected server-side (`AuthModel` status gate, code-verified) ✅ |
| Logout → protected URL via old cookie | 302 (session destroyed server-side) ✅ |
| Session expiry (`session_expires_at`) | enforced in `AdminAuthFilter` ✅ |
| Open redirect via `?redirect=` | safe — `site_url()` prefixes, `evil.example.com` landed on dashboard ✅ |

---

## 6. Authorization / RBAC — result: PASS (after SEC-001 fix)

- Registered modules gate correctly: low-priv → `monitor`, `letter_pad`, `entry_trace`, `gst_setting` all **302 → /permission_denied**. ✅
- AJAX denials return `403 {status:denied}` (no privileged data). ✅
- Super-admin-only controllers self-gate server-side (`Entry_trace`, `Gst_setting`, `Traffic`). ✅
- Fail-open design weakness tracked as SEC-006.

---

## 7. Injection / input — result: PASS

- **SQLi:** `search[value] = ' OR '1'='1` and `… UNION SELECT password FROM users-- -` on `attendance/employee_view_all` → normal JSON, `recordsFiltered:0`, **no** SQL error. CI4 Query Builder `like()`/`where()` parameterize. ✅
- **Mass assignment:** controllers whitelist columns explicitly (e.g. `Account_name::add`, `Attendance::employee_add`) — no blind `$_POST` insert. ✅
- **HTTP method manipulation:** `autoRoute=false`; POST-only routes don't match GET (fall to graceful Fallback), and mutations read `getPost()` so a GET is a no-op. ✅

---

## 8. Functional / QA (spot-checked this session)

- Public **landing** (`/` ricemill), **login** (space theme + weather + permission gate), **forgot** render correctly (browser-verified). ✅
- **Monitor overview**: KPIs, charts, online-now, recent activity, 10-tab nav + date/user filter render with real data. ✅
- **Attendance employee master**: DataTables server-side **search**, add/edit, and the shared ⋮ action-menu (Mark inactive / Edit / Delete) work (browser-verified). ✅
- **account_name/add**: full form (registration/GST/PAN, ledger group, State/City with "+ New", GST verify) renders without PHP errors. ✅

---

## 9. Untested / out-of-scope areas (and why)

- **File-upload security (deep):** entry-media upload exists (`Account::_save_uploaded_entry_file`) with extension allow-lists and randomized names; a full malicious-upload / double-extension / traversal battery was **not** executed this pass — recommended next.
- **Full CRUD across all 60 registry modules:** only the recently-touched modules + security-relevant endpoints were exercised live; a module-by-module functional sweep remains.
- **Mobile REST API (`webservices`)**: the `apiAuth` filter is deferred/commented in CI4 (`// 'apiAuth'`); the mobile API is not yet wired here, so it was not tested.
- **Payment / subscription logic:** none present in this app.
- **Performance profiling / large-dataset load:** not run (out of scope for this security-first pass; no destructive load testing permitted).
- **Full responsive/UI matrix:** desktop verified in-browser; tablet/mobile breakpoints not exhaustively walked.

---

## 10. Recommended next actions (priority order)

1. **Prod deploy hardening (SEC-004, SEC-008):** `CI_ENVIRONMENT=production` + `Cookie::$secure=true` on the live host. *(config only)*
2. **CSRF rollout (SEC-003):** enable `csrf` + app-wide token injection, retest POST flows. *(migration task)*
3. **RBAC default-deny (SEC-006):** register every admin controller or flip the filter to default-deny after auditing open keys.
4. **Login throttling (SEC-007).**
5. **Password migration to `password_hash()` (SEC-005)** — after CI3 retirement.
6. Deep file-upload and full-module functional sweeps.

---

### Files changed in this audit
- `app/Modules/Admin/Controllers/Traffic.php` — Super-Admin guard (SEC-001)
- `app/Config/Filters.php` — enabled `secureheaders` (SEC-002)

*All changed files pass `php -l` on PHP 8.3. No database schema was altered. No destructive testing performed.*
