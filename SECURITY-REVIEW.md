# ERP / HissabKitaab — Defensive Security Review

**Scope:** local/staging ERP application (own system). Static code review + read-only
local checks only. No brute-forcing, no destructive payloads, no third-party targets.
**Method:** source analysis of auth, authorization, CSRF, SQLi, XSS, uploads, IDOR,
exposed files, prod config, and public-dir leakage, plus read-only HTTP status probes
against the local server.

**Overall posture: good.** Access control is consistently enforced (ownership checks on
every company-scoped resource), queries use the query builder, the public webroot is
clean, and secrets are not web-served. The findings below are mostly hardening. The
**one High** (stored XSS via web attachment preview, F-1) has since been **fixed** — see
its section for the resolution.

---

## Summary table

| # | Risk | Finding | Area |
|---|------|---------|------|
| F-1 | **Fixed** | ~~Stored XSS via web attachment preview (no upload allowlist + inline client MIME, no `nosniff`)~~ — remediated (see below) | XSS / Upload |
| F-2 | **Fixed** | ~~Session/CSRF cookies not marked `Secure`~~ — now `Secure` in production (env-gated) | Session |
| F-3 | **Medium** | `CI_ENVIRONMENT=development` on this box (fine locally; dangerous if ever public) | Prod config |
| F-4 | **Fixed** | ~~CSRF token not randomized~~ — `tokenRandomize=true` set (safe on HTTP+HTTPS) | CSRF |
| F-5 | **In progress** | Content-Security-Policy enabled **report-only** (enforcement pending inline-code tuning) | Prod config |
| F-6 | **Low** | `forceGlobalSecureRequests=false` — verify it is `true` in prod | Prod config |
| — | Fixed | Reset-token log leak, auth rate-limiting, plaintext long-lived tokens (remediated earlier this session) | Auth |

**Confirmed NOT vulnerable** (evidence in the "Good posture" section): SQL injection,
IDOR/broken object access, API file uploads, `.env`/`.git`/source exposure, directory
listing.

---

## F-1 — Stored XSS via attachment preview  **(High → FIXED)**

> **Resolved (2026-08-29).** Both halves of the recommended fix landed in
> `TransactionController.php`:
> - **Serve path** — `stream()` now derives `Content-Type` from the server-stored
>   extension (never the client MIME), sets `X-Content-Type-Options: nosniff`, and
>   serves only image/audio/pdf inline (everything else is a forced download).
> - **Upload path** — `saveUploads()` and `replaceAttachment()` enforce an extension
>   allowlist (`ALLOWED_ATTACH_EXT`, mirroring the API's `ATTACH_EXT`), so `.html` /
>   `.svg` / `.js` / `.php` are rejected before storage.
>
> Verified live: an `evil.html` upload is rejected (not stored) while a real PNG is
> accepted; a stored file with a spoofed `text/html` client MIME is served back as
> `application/octet-stream` + `attachment` + `nosniff` (not `text/html`, not inline).
> The `InventoryController` paths referenced below no longer exist (that module was
> refactored to 204 lines with no attachment handling; product images now come only
> via the type-restricted API `storeImage`, png/jpg/webp only), so the only live
> instance was the Transactions one now fixed.

**Originally affected:**
- `app/Modules/Transactions/Controllers/TransactionController.php` — `saveUploads()` and `stream()`/`preview()`
- `app/Modules/Inventory/Controllers/InventoryController.php` — upload and `attachment()` *(no longer present)*

**Root cause (two gaps that combine):**
1. The **web** upload path validates only file size — **no extension allowlist**. `getRandomName()` preserves the original extension, so `evil.html` / `evil.svg` are accepted and stored. (The **API** path, `TransactionApiController::attachment`, does have an allowlist — this is a web-only regression.)
2. `preview()` / `attachment()` serve the file **inline** with `Content-Type` taken from the **stored client MIME** (attacker-controllable at upload) and **without `X-Content-Type-Options: nosniff`**.

**Safe reproduction (non-destructive, use a benign marker — not a real payload):**
1. Sign in as a local test user in a test company.
2. On a test transaction, upload a file `poc.html` whose body is a harmless marker such as `<script>document.title='xss-poc'</script>`, sending it with `Content-Type: text/html`.
3. Open the attachment **preview** URL for that file.
4. Observe the browser renders it as HTML on the app's own origin (the tab title changes). A title change is sufficient proof — do not run a real exfiltration payload.

**Evidence (no secrets):** `saveUploads()` gates only `getSizeByUnit('mb') > 25`; stores `'mime' => $file->getClientMimeType()`. `stream()` emits `setHeader('Content-Type', $att['mime'])` + `Content-Disposition: inline` with no `nosniff`.

**Impact:** Same-origin script execution when any user in the same company — or an admin
reviewing attachments — opens the preview. Leads to session/CSRF-token theft and actions
performed as the victim. Files live under `writable/` (not directly executable via URL),
so this is reflected-through-preview, not RCE.

**Recommended fix:** mirror the already-correct API path in both web controllers:
- Enforce an **extension allowlist** on upload (reuse the API's `ATTACH_EXT`; reject `html`, `svg`, `xhtml`, `php`, `js`, etc.).
- On serve, derive `Content-Type` from the **stored extension** (not client MIME), add `->setHeader('X-Content-Type-Options','nosniff')`, and force anything not in a small media allowlist (image/pdf/audio) to `Content-Disposition: attachment`.

**Regression test:** (a) uploading `poc.html` is rejected; (b) uploading a real PNG but with request `Content-Type: text/html` is served back as `image/png` with `nosniff` and never as `text/html`; (c) a `.svg` upload is rejected or served as `attachment`.

---

## F-2 — Session & CSRF cookies not `Secure`  **(Medium → FIXED)**

> **Resolved (2026-08-29).** `app/Config/Cookie.php` → `$secure = (ENVIRONMENT === 'production')`.
> Production (HTTPS) now marks `ci_session` + the CSRF cookie `Secure`, so they are
> never sent over cleartext HTTP; local `development` over plain HTTP is unchanged
> (evaluates to `false`). Verified: the local session stays valid after the change.

**Affected:** `app/Config/Cookie.php` → `public bool $secure = false;` (applies to `ci_session` and the CSRF cookie).

**Reproduction:** In prod (HTTPS), inspect `Set-Cookie` for `ci_session` — the `Secure`
attribute is absent, so the browser will also send it over any `http://` request.

**Impact:** Session/CSRF cookie can be transmitted over cleartext HTTP (downgrade,
mixed-content, or an accidental `http://` link), enabling interception/session hijack.

**Fix:** Set `cookie.secure = true` in production (via `.env`, or gate on
`ENVIRONMENT === 'production'` so localhost HTTP still works). `httponly=true` and
`samesite=Lax` are already correct.

**Regression test:** In a prod-like (HTTPS) run, assert every `Set-Cookie` includes
`Secure; HttpOnly; SameSite=Lax`.

---

## F-3 — `CI_ENVIRONMENT=development` on this box  **(Medium, context-dependent)**

**Affected:** `.env` → `CI_ENVIRONMENT = development` (local/staging box; production Hostinger was separately confirmed `production`).

**Impact:** In `development`, CI4 shows full stack traces, the debug toolbar, and detailed
DB errors — path/schema/query disclosure — to anyone who can reach the site. Harmless on a
firewalled laptop; a serious leak if this staging box is ever internet-reachable.

**Fix:** Any host reachable off your machine must run `CI_ENVIRONMENT=production`. Keep
`development` only for truly local, non-routable use.

**Regression test:** Hitting a deliberately bad route on staging returns a generic error
page (no stack trace, no toolbar).

---

## F-4 — CSRF token not randomized  **(Low → FIXED)**

> **Resolved (2026-08-29).** `app/Config/Security.php` → `tokenRandomize = true`.
> Applied globally (safe on both HTTP and HTTPS). Verified live: the app's own
> forms still submit (a real Rokadh entry saved with the randomized token) — token
> masking does not break validation.

**Affected:** `app/Config/Security.php` → `tokenRandomize = false`.

**Impact:** Static per-session token is marginally more exposed to BREACH-style
compression side-channel extraction. CSRF protection itself is present and correctly
scoped (enabled for web; excepted only for the stateless bearer-token API and the
signed webhook).

**Fix:** Set `tokenRandomize = true`. (Optionally rename the default `csrf_test_name` /
`csrf_cookie_name` — cosmetic.)

**Regression test:** Two loads of the same form yield different masked token values; POST
still validates.

---

## F-5 — Content-Security-Policy  **(Low → REPORT-ONLY enabled)**

> **In progress (2026-08-29).** A CSP is now **enabled in report-only mode**
> (`App::$CSPEnabled = true`, `ContentSecurityPolicy::$reportOnly = true`), with
> host allowlists for the app's real resources (Cashfree SDK, Google Fonts/Translate,
> open-meteo/geojs/razorpay-IFSC/qrserver APIs). Report-only **cannot block anything**
> — verified the dashboard (charts, Translate widget) and other pages render normally
> and the `Content-Security-Policy-Report-Only` header is sent.
>
> **Before it can be ENFORCED** (`reportOnly = false`), the app's inline code needs
> tuning — the console currently reports inline `<script>`, inline `style="…"`, and
> inline `onclick=` violations, plus Google Translate's dynamically-injected scripts.
> Note the CI4 gotcha: it auto-adds a `nonce-…` to `script-src-elem`, and per spec a
> nonce **negates `'unsafe-inline'`**. So enforcement means one of:
> - set `$autoNonce = true` (CI4 rewrites each inline tag with the nonce) **and**
>   move inline `onclick=` handlers to addEventListener (attributes can't be nonced), or
> - compute per-script hashes.
>
> This is app-code refactoring best done page-by-page against the report-only logs,
> so it is left in report-only until then.
>
> **Progress:** the **Transactions** module's views are now free of inline `on*`
> handlers (the one class of violation that *cannot* be nonced) — they were moved to
> delegated listeners on `app.js` and to nonce-able inline `<script>` blocks. Remaining
> app-wide steps before enforcing: repeat this for the other modules' inline handlers
> (`SuperAdmin`, `Rokad`, `Notes`, …), set `$autoNonce = true` so inline `<script>`
> elements get nonced, and resolve the Google Translate widget (it injects inline
> scripts dynamically that a nonce can't reach).

**Impact (when unmitigated):** No second line of defense if an XSS sink (e.g. F-1) is
ever introduced.

---

## F-6 — Verify HTTPS is forced in production  **(Low)**

**Affected:** `.env` (local) → `app.forceGlobalSecureRequests = false`.

**Impact:** If also false in prod, no HTTP→HTTPS redirect / HSTS. (Prod already serves over
HTTPS; this is a config-verify item.)

**Fix:** Ensure prod `.env` sets `app.forceGlobalSecureRequests = true`.

**Regression test:** `http://` request to prod 301-redirects to `https://` with HSTS.

---

## Good posture — confirmed during this review

- **SQL injection — none found.** Query builder used throughout. The only raw
  interpolated SQL (`MonitorModel::overviewKpis`) interpolates an **`int`-typed**
  `$userId` and `$this->db->escape()`-d date bounds; other raw `query()` calls are static
  migration DDL. No user string reaches SQL unescaped.
- **IDOR / broken object access — none found.** Every company-scoped resource validates
  ownership: API via `CompanyUserModel::isMember()` and `findScoped`; web downloads via
  `findAttachment()` → `findScoped(transaction_id, scope())`; inventory/attachment serves
  check `company_id === cid()`. Changing an `id`/`company_id` in a request cannot cross
  tenants.
- **API file upload — hardened.** Extension allowlist, `random_bytes` server names,
  storage under `writable/` (outside webroot), `nosniff`, and server-derived MIME. (F-1 is
  to bring the web/inventory paths up to this same bar.)
- **No secret / source exposure.** Local probes: `/ERP/.env` → 404, `/ERP/.git/config` →
  404, `/ERP/app/Config/App.php` → 403, `/ERP/writable/logs/` → 403. Only `public/` is
  served. `public/.htaccess` has `Options -Indexes`; webroot contains no SQL/zip/backup
  dumps.
- **CSRF** enabled globally for web forms, correctly excepted for `api/*` (bearer-token,
  no ambient cookie auth) and the signed `subscription/webhook`.
- **Cookies** are `HttpOnly` + `SameSite=Lax` (only `Secure` missing — F-2).
- **Credential handling** (post-remediation this session): passwords `password_hash`ed,
  vault secrets encrypted and excluded from listings, bearer tokens SHA-256 at rest with a
  sliding 180-day TTL, and auth endpoints rate-limited.

---

## Remediated earlier this session (for completeness)

- **Password-reset token written to logs in plaintext** (API + web) — removed; `/health?logs=` output now scrubs tokens/keys/passwords.
- **No app-level auth rate limiting** — added per-IP + per-account throttles on `login`, `forgot-password`, `change-password` (verified returning `429`).
- **Bearer tokens: plaintext at rest, 10-year TTL, survived password change** — now hashed, 180-day sliding TTL, revoked on password change, legacy tokens upgraded lazily.

---

## Suggested priority order

1. ~~**F-1** (High) — close the stored-XSS~~ — **done** (see F-1 resolution note).
2. ~~**F-2** (Medium) — `Secure` cookies in prod~~ — **done** (env-gated).
3. **F-3** (Medium) — confirm no public host runs `development`.
4. ~~**F-4**~~ (done — `tokenRandomize=true`) / **F-5 / F-6** (Low) — CSP, HTTPS-forced verification.

**Applied this pass:** F-1, F-2, F-4. The remaining items (F-3, F-5, F-6) are **deploy-time**
decisions on the production host, not application-code bugs:
- **F-3** — set `CI_ENVIRONMENT=production` on any internet-reachable host (a `.env` value,
  per-host; production was separately confirmed correct).
- **F-5** — a Content-Security-Policy needs tuning against the app's inline scripts/styles,
  so it should be rolled out report-only first rather than flipped blind.
- **F-6** — verify `app.forceGlobalSecureRequests = true` in the production `.env` (a
  per-environment value; must stay `false` locally for plain-HTTP dev).
