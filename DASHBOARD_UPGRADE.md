# Dashboard Redesign & Upgrade — Change Log

Upgrades the ERP dashboard with a modern responsive UI, live theme
customization, smooth animations, a global toast/alert system, and Chart.js
analytics. **No existing routes, controllers, models, DB columns, or business
logic were removed** — the original `index()` logic is preserved and extended.

URL: **http://localhost:8077/ERP/dashboard**

---

## 1. Files Changed / Added

### Added
| File | Purpose |
|------|---------|
| `app/Models/DashboardModel.php` | Read-only analytics aggregator (real queries + finance placeholders) |
| `public/assets/js/theme.js` | Live theme engine (CSS-variable colours, localStorage, instant apply) |
| `public/assets/js/notify.js` | Global toast (Toastr) + confirmation (SweetAlert2) system |
| `public/assets/js/dashboard.js` | jQuery AJAX loader + Chart.js renderers + KPI count-up |
| `public/assets/css/dashboard.css` | KPI cards, widgets, skeleton loaders, chart boxes |
| `app/Views/partials/theme_panel.php` | Reusable offcanvas theme customizer |
| `public/assets/vendor/jquery/jquery.min.js` | jQuery 3.7.1 (new library) |
| `public/assets/vendor/toastr/toastr.min.js` + `.css` | Toastr 2.1.4 (new library) |

### Modified
| File | Change |
|------|--------|
| `app/Modules/Dashboard/Controllers/DashboardController.php` | Kept `index()`; **added** `analytics()` AJAX JSON endpoint; added `kpis` to view data |
| `app/Modules/Dashboard/Config/Routes.php` | **Added** `GET dashboard/analytics` (same `permission:dashboard,view` filter) |
| `app/Modules/Dashboard/Views/index.php` | Full responsive redesign (KPI cards, quick actions, charts, skeletons, finance block, recent-logins table kept) |
| `app/Views/layouts/main.php` | Theme boot script, page-loader bar, jQuery/Toastr/theme/notify includes, theme-panel include |
| `app/Views/partials/navbar.php` | Added theme-customizer trigger button |
| `public/assets/css/app.css` | Global transitions, theme-colour override hooks, page-loader, reduced-motion support |
| `public/assets/js/app.js` | Appearance light/dark set from panel, page-loader wiring, delete-confirm now uses `erpConfirm()` |

---

## 2. Controller Changes
- `DashboardController::index()` — **unchanged logic**, now also passes
  `kpis` (from `DashboardModel::kpis()`) for the redesigned cards.
- `DashboardController::analytics()` — **new**. `GET`, AJAX, JSON. Guarded by
  `permission:dashboard,view`. Supports `?block=` to load widgets independently
  (`logins|usersByType|usersByRole|activity|growth|topUsers|finance|kpis|all`).

## 3. Model Changes
- **New** `App\Models\DashboardModel` (read-only, no schema changes). Methods:
  `kpis()`, `loginTrend()`, `usersByType()`, `usersByRole()`,
  `activityByAction()`, `userGrowth()`, `topActiveUsers()` — all from existing
  tables. Plus `financeKpis()` / `financeSeries()` **placeholders** (return
  zeros; search `CONNECT-DATA` to plug real queries).
- No existing model was modified.

## 4. View Changes
- KPI cards (6) with gradient styling + count-up animation.
- Quick-action buttons (permission-gated with `can()`).
- Charts: **Line** (logins success/failed), **Bar** (user growth), **Horizontal bar**
  (activity by action), **Doughnut** (users by type), **Pie** (users by role),
  **Progress bars** (user health), **rank list** (top active users), finance
  **Bar + Doughnut** (placeholder).
- Skeleton loaders shown until AJAX data arrives.

## 5. JS / CSS Changes
- **Theme system** (`theme.js` + `theme_panel.php`): primary/secondary/font/background
  colour pickers, light/dark, reset-to-default, saved in `localStorage`
  (`erp-custom-theme`), applied instantly via CSS variables — no reload.
- **Notifications** (`notify.js`): `erpNotify(type,msg)`, `erpConfirm({...})`,
  `erpRequired(msg)`, backward-compatible `erpToast()`. Success/error/warning/info
  toasts with auto-close, progress bar, close button, smooth fade.
- **Animations** (`app.css` + `dashboard.css`): card/button/dropdown/sidebar
  transitions, widget staggered fade-in, theme transition, top page-loading bar,
  skeleton shimmer. Honors `prefers-reduced-motion`.

## 6. New Libraries Added
- **jQuery 3.7.1** — required by Toastr and used for dashboard AJAX/effects.
- **Toastr 2.1.4** — toast notifications.
- (Already present, reused: Bootstrap 5.3, AdminLTE 4, Chart.js 4, SweetAlert2 11.)

All hosted locally under `public/assets/vendor/` — no external CDN at runtime.

---

## 7. SQL Queries (only if you add Finance analytics)

**No SQL is required for this upgrade** — it uses existing tables only.

If/when you want the Financial Analytics widgets to show real numbers, create
accounting tables and connect them in `DashboardModel` (search `CONNECT-DATA`).
Suggested minimal schema:

```sql
-- Chart of accounts / parties
CREATE TABLE `accounts` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(150) NOT NULL,
  `type` ENUM('debtor','creditor','bank','cash','income','expense') NOT NULL,
  `firm_id` INT UNSIGNED NULL,
  `opening_balance` DECIMAL(15,2) DEFAULT 0,
  `created_at` DATETIME NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Transactions ledger
CREATE TABLE `transactions` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `account_id` INT UNSIGNED NOT NULL,
  `firm_id` INT UNSIGNED NULL,
  `type` ENUM('credit','debit') NOT NULL,     -- credit = income/received, debit = expense/paid
  `amount` DECIMAL(15,2) NOT NULL,
  `txn_date` DATE NOT NULL,
  `note` VARCHAR(255) NULL,
  `created_at` DATETIME NULL,
  INDEX (`account_id`), INDEX (`firm_id`), INDEX (`txn_date`),
  FOREIGN KEY (`account_id`) REFERENCES `accounts`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

Then, examples to replace the placeholders:
```sql
-- Total income / expense
SELECT SUM(amount) FROM transactions WHERE type='credit';
SELECT SUM(amount) FROM transactions WHERE type='debit';
-- Monthly income vs expense (last 6 months)
SELECT DATE_FORMAT(txn_date,'%Y-%m') ym,
       SUM(type='credit')*0 + SUM(CASE WHEN type='credit' THEN amount END) income,
       SUM(CASE WHEN type='debit' THEN amount END) expense
FROM transactions WHERE txn_date >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
GROUP BY ym ORDER BY ym;
-- Top 10 parties by balance
SELECT a.name, SUM(CASE WHEN t.type='credit' THEN t.amount ELSE -t.amount END) balance
FROM accounts a JOIN transactions t ON t.account_id=a.id
GROUP BY a.id ORDER BY balance DESC LIMIT 10;
```

---

## 8. Testing Checklist

- [x] `/ERP/dashboard` renders with no PHP errors (verified).
- [x] All new JS/CSS assets return HTTP 200 (verified).
- [x] `GET /ERP/dashboard/analytics` returns valid JSON with all blocks & correct keys (verified).
- [x] Analytics endpoint respects `permission:dashboard,view` (guarded).
- [x] JS files pass `node --check` syntax validation (verified).
- [ ] KPI cards count up on load; charts render (line/bar/pie/doughnut).
- [ ] Skeleton loaders appear then disappear when data arrives.
- [ ] Theme panel: change primary/secondary → updates instantly; Save persists across reload; Reset restores default.
- [ ] Dark/Light toggle still works alongside custom colours.
- [ ] Toasts appear on save/delete; delete shows SweetAlert confirmation.
- [ ] Responsive: cards/charts reflow on tablet (768px) and mobile (375px).
- [ ] Refresh button reloads analytics with skeletons + toast.

> The last group needs a browser (the Chrome automation extension was offline
> during automated verification). Everything server-side and the data contract
> is verified.

---

## 9. Notes for Future Improvement
- Persist theme **per-user** in a `user_settings` table (currently localStorage,
  per-browser). `theme.js` already centralises apply/save/reset for an easy swap.
- Cache `analytics()` output (e.g. 60s) for large datasets; add date-range filters.
- Lazy-load finance block via `?block=finance` only when the section scrolls into view.
- Add CSV/PDF export of analytics (fits the existing `export`/`print` permissions).
- Replace finance placeholders once accounting tables exist (see §7).
