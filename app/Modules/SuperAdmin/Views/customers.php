<?php /** Super Admin — all customers. Rendered inside layout.php.
 * Design ported from the TrackmeNew invoice-listing table view: soft-shadowed
 * white panels (radius 8px), a gradient hero, snapshot stat cards, an
 * uppercase-header table with light dividers + hover, and color-coded 32px
 * square action icons. */ ?>

<?php if ($np = session()->getFlashdata('new_password')): ?>
    <div class="alert alert-success alert-dismissible d-flex flex-wrap align-items-center gap-2 shadow-sm" role="alert">
        <i class="bi bi-shield-lock-fill fs-5"></i>
        <div>
            New password for <strong><?= esc(session()->getFlashdata('new_password_for')) ?></strong>:
            <code id="npValue" class="fs-6 user-select-all"><?= esc($np) ?></code>
            <button type="button" class="btn btn-sm btn-outline-success ms-1" onclick="navigator.clipboard&&navigator.clipboard.writeText(document.getElementById('npValue').innerText)">
                <i class="bi bi-clipboard"></i> Copy
            </button>
            <div class="small mt-1">
                <?= session()->getFlashdata('new_password_emailed') === '1'
                    ? '<i class="bi bi-envelope-check text-success"></i> Emailed to the customer. '
                    : '' ?>
                Share it privately — it is shown only once and the customer must change it on next login.
            </div>
        </div>
        <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<?php if ($rl = session()->getFlashdata('reset_link')): ?>
    <div class="alert alert-warning alert-dismissible d-flex flex-wrap align-items-center gap-2 shadow-sm" role="alert">
        <i class="bi bi-link-45deg fs-5"></i>
        <div>
            Reset link for <strong><?= esc(session()->getFlashdata('reset_link_for')) ?></strong>:
            <code id="rlValue" class="user-select-all"><?= esc($rl) ?></code>
            <button type="button" class="btn btn-sm btn-outline-warning ms-1" onclick="navigator.clipboard&&navigator.clipboard.writeText(document.getElementById('rlValue').innerText)">
                <i class="bi bi-clipboard"></i> Copy
            </button>
            <div class="small mt-1">Share privately — it expires in 1 hour.</div>
        </div>
        <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<?php
// Toolbar state (with safe defaults). The table + pager live in the
// _customers_table partial (also served on its own for AJAX).
$per  = $per  ?? 25;
$perOpts = [25, 35, 50, 100];
?>
<div class="cust-page">

    <!-- Hero -->
    <section class="cust-hero">
        <div>
            <h4 class="cust-title">Customers</h4>
            <p class="cust-subtitle">Manage customer accounts, subscriptions, access and passwords — all in one place.</p>
        </div>
        <div class="cust-hero-actions">
            <a href="<?= site_url('admin/activate') ?>" class="cust-btn cust-btn-primary"><i class="bi bi-gem"></i> Activate Plan</a>
        </div>
    </section>

    <!-- Snapshot stat cards -->
    <section class="cust-snap-grid">
        <div class="cust-snap"><span class="cust-snap-ic ic-blue"><i class="bi bi-people-fill"></i></span>
            <div><p class="cust-snap-label">Total Customers</p><p class="cust-snap-value"><?= number_format((int) ($stats['total'] ?? 0)) ?></p></div></div>
        <div class="cust-snap"><span class="cust-snap-ic ic-green"><i class="bi bi-check-circle-fill"></i></span>
            <div><p class="cust-snap-label">Active</p><p class="cust-snap-value"><?= number_format((int) ($stats['active'] ?? 0)) ?></p></div></div>
        <div class="cust-snap"><span class="cust-snap-ic ic-gray"><i class="bi bi-slash-circle-fill"></i></span>
            <div><p class="cust-snap-label">Inactive</p><p class="cust-snap-value"><?= number_format((int) ($stats['inactive'] ?? 0)) ?></p></div></div>
        <div class="cust-snap"><span class="cust-snap-ic ic-violet"><i class="bi bi-building-fill"></i></span>
            <div><p class="cust-snap-label">Total Firms</p><p class="cust-snap-value"><?= number_format((int) ($stats['firms'] ?? 0)) ?></p></div></div>
    </section>

    <!-- Table panel -->
    <section class="cust-panel cust-table-panel">
        <div class="cust-toolbar">
            <div>
                <h5 class="cust-table-title">Customer Records</h5>
                <p class="cust-table-note">Open a subscription, sign in as the customer, or reset their access from the actions.</p>
            </div>
            <span class="cust-total-tag"><i class="bi bi-people"></i> <?= number_format((int) ($stats['total'] ?? 0)) ?> total</span>
        </div>

        <!-- DataTables-style controls: page-size (Records) + Search -->
        <div class="cust-tabletools">
            <form method="get" class="cust-len" role="search">
                <?php if ($search !== ''): ?><input type="hidden" name="q" value="<?= esc($search, 'attr') ?>"><?php endif; ?>
                <label>Show</label>
                <select name="per" class="cust-len-select">
                    <?php foreach ($perOpts as $opt): ?>
                        <option value="<?= $opt ?>" <?= ((string) $per === (string) $opt) ? 'selected' : '' ?>><?= $opt ?></option>
                    <?php endforeach; ?>
                    <option value="all" <?= ($per === 'all') ? 'selected' : '' ?>>All</option>
                </select>
                <label>Records</label>
            </form>

            <form method="get" class="cust-find" role="search">
                <?php if ($per !== 25): ?><input type="hidden" name="per" value="<?= esc((string) $per, 'attr') ?>"><?php endif; ?>
                <label for="custSearch">Search:</label>
                <div class="cust-find-box">
                    <i class="bi bi-search"></i>
                    <input type="search" id="custSearch" name="q" value="<?= esc($search) ?>" placeholder="Name or email…" autocomplete="off">
                    <?php if ($search !== ''): ?><a href="<?= site_url('admin/customers') ?>" class="cust-find-clear" title="Clear"><i class="bi bi-x-lg"></i></a><?php endif; ?>
                </div>
            </form>
        </div>

        <!-- AJAX host: table + pager fragment. Live search / page-size / sort /
             pagination swap only this node's HTML, never the whole page. -->
        <div id="custTableHost" class="cust-host">
            <?= view('Modules\SuperAdmin\Views\_customers_table', [
                'rows' => $rows, 'per' => $per, 'sort' => $sort ?? 'id', 'dir' => $dir ?? 'desc',
                'search' => $search, 'offset' => $offset, 'pager' => $pager,
            ]) ?>
        </div>
    </section>
</div>

<!-- Set-password modal (shared; populated by the clicked row's button) -->
<div class="modal fade" id="setPwdModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form class="modal-content" id="setPwdForm" method="post" data-no-validate>
            <?= csrf_field() ?>
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-shield-lock me-1"></i> Set password for <span id="setPwdName">customer</span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p class="text-secondary small mb-3">
                    Existing passwords can't be shown — they're stored one-way (bcrypt) and are unrecoverable.
                    Set a new one instead. Leave it blank to auto-generate a strong password. The customer will be
                    asked to change it on their next login.
                </p>
                <label class="form-label">New password <span class="text-muted">(optional — blank = generate)</span></label>
                <div class="input-group">
                    <input type="text" name="new_password" id="setPwdInput" class="form-control" autocomplete="off"
                           minlength="8" placeholder="Leave blank to auto-generate">
                    <button class="btn btn-outline-secondary" type="button" id="setPwdGen" title="Generate"><i class="bi bi-magic"></i></button>
                </div>
                <div class="form-text">At least 8 characters if you type your own.</div>
                <div class="form-check mt-3">
                    <input class="form-check-input" type="checkbox" value="1" name="email_customer" id="setPwdEmail" checked>
                    <label class="form-check-label" for="setPwdEmail">Email the new password to the customer</label>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-success"><i class="bi bi-check2 me-1"></i> Set password</button>
            </div>
        </form>
    </div>
</div>

<!-- Permanent-delete modal (type-to-confirm; populated by the clicked row's button) -->
<div class="modal fade" id="purgeModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form class="modal-content" id="purgeForm" method="post" data-no-validate>
            <?= csrf_field() ?>
            <div class="modal-header" style="background:#fdecec">
                <h5 class="modal-title" style="color:#c53030"><i class="bi bi-exclamation-octagon-fill me-1"></i> Delete permanently</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p class="mb-2">You are about to <strong>permanently delete</strong> <strong id="purgeName">this customer</strong> and <strong>everything</strong> tied to the account. <span style="color:#c53030;font-weight:700">This cannot be undone.</span></p>
                <ul class="small text-secondary mb-3">
                    <li><strong id="purgeFirms">0</strong> firm(s) — with all transactions, rokad, vouchers &amp; ledgers</li>
                    <li>Subscriptions, payment orders &amp; invoices</li>
                    <li>Notes, reminders, saved passwords &amp; calculator history</li>
                    <li>Firm-user accounts, logins, activity logs &amp; devices</li>
                </ul>
                <label class="form-label">Type the account name <code id="purgeExpect" class="text-danger"></code> to confirm</label>
                <input type="text" name="confirm_name" id="purgeConfirm" class="form-control" autocomplete="off" placeholder="Exact account name">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-danger" id="purgeSubmit" disabled><i class="bi bi-trash3 me-1"></i> Delete permanently</button>
            </div>
        </form>
    </div>
</div>

<style>
/* ---- Customers listing — TrackmeNew-inspired table design ------------------ */
.cust-page{--c-primary:#1769c2;--c-primary-d:#0c5aaa;--c-ink:#18243c;--c-text:#26374f;
    --c-muted:#718096;--c-soft:#516174;--c-border:#dce6f2;--c-line:#edf2f7;--c-bg:#fbfdff;
    color:var(--c-text)}
.cust-page .cust-panel{border:1px solid var(--c-border);border-radius:8px;background:#fff;box-shadow:0 16px 38px rgba(24,36,60,.08)}

/* Hero */
.cust-hero{display:flex;align-items:center;justify-content:space-between;gap:18px;flex-wrap:wrap;
    margin-bottom:18px;padding:22px 24px;border:1px solid var(--c-border);border-radius:8px;
    background:linear-gradient(135deg,rgba(255,255,255,.98),rgba(255,255,255,.92)),
        radial-gradient(circle at 94% 0,rgba(23,105,194,.13),transparent 34%);
    box-shadow:0 16px 38px rgba(24,36,60,.08)}
.cust-title{margin:0;color:var(--c-ink);font-size:25px;font-weight:900}
.cust-subtitle{margin:6px 0 0;color:var(--c-muted);font-size:13px;font-weight:700;line-height:1.55}
.cust-hero-actions{display:flex;align-items:center;gap:10px;flex-wrap:wrap}
.cust-search{position:relative;display:flex;align-items:center}
.cust-search input{min-height:42px;width:250px;max-width:60vw;padding:8px 34px 8px 36px;border:1px solid var(--c-border);
    border-radius:8px;background:var(--c-bg);color:var(--c-ink);font-weight:700;box-shadow:none;outline:none}
.cust-search input:focus{border-color:var(--c-primary);background:#fff;box-shadow:0 0 0 4px rgba(23,105,194,.12)}
.cust-search-ic{position:absolute;left:12px;color:var(--c-muted);font-size:14px;pointer-events:none}
.cust-search-clear{position:absolute;right:11px;color:var(--c-muted);font-size:12px;text-decoration:none}
.cust-search-clear:hover{color:#c53030}
.cust-btn{display:inline-flex;align-items:center;gap:8px;min-height:42px;padding:10px 16px;border-radius:8px;
    font-weight:900;font-size:14px;text-decoration:none;transition:all .18s ease;border:0}
.cust-btn-primary{background:var(--c-primary);color:#fff;box-shadow:0 10px 22px rgba(23,105,194,.2)}
.cust-btn-primary:hover{background:var(--c-primary-d);color:#fff}

/* Snapshot cards */
.cust-snap-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:12px;margin-bottom:18px}
.cust-snap{display:flex;align-items:center;gap:14px;padding:16px;border:1px solid var(--c-border);border-radius:8px;
    background:#fff;box-shadow:0 12px 26px rgba(24,36,60,.06)}
.cust-snap-ic{display:inline-flex;align-items:center;justify-content:center;width:46px;height:46px;border-radius:12px;
    font-size:20px;flex:0 0 auto}
.cust-snap-ic.ic-blue{background:#e9f1fc;color:#1769c2}
.cust-snap-ic.ic-green{background:#e8f7ef;color:#1f9d70}
.cust-snap-ic.ic-gray{background:#eef1f6;color:#64748b}
.cust-snap-ic.ic-violet{background:#f1ecfe;color:#7c4dff}
.cust-snap-label{margin:0;color:var(--c-muted);font-size:11px;font-weight:900;text-transform:uppercase;letter-spacing:.02em}
.cust-snap-value{margin:3px 0 0;color:var(--c-ink);font-size:22px;font-weight:900;line-height:1}

/* Table panel + toolbar */
.cust-table-panel{overflow:hidden}
.cust-toolbar{display:flex;align-items:center;justify-content:space-between;gap:14px;padding:18px 20px;border-bottom:1px solid var(--c-line)}
.cust-table-title{margin:0;color:var(--c-ink);font-size:17px;font-weight:900}
.cust-table-note{margin:4px 0 0;color:var(--c-muted);font-size:12px;font-weight:700}
.cust-search-tag{display:inline-flex;align-items:center;gap:6px;padding:6px 12px;border-radius:8px;background:#eef6ff;
    color:#1769c2;font-size:12px;font-weight:800;border:1px solid #cfe3fb}
.cust-total-tag{display:inline-flex;align-items:center;gap:6px;padding:6px 12px;border-radius:8px;background:#f1f5f9;
    color:var(--c-soft);font-size:12px;font-weight:800;border:1px solid #e2e8f0;white-space:nowrap}

/* DataTables-style controls bar (page-size + search) */
.cust-tabletools{display:flex;align-items:center;justify-content:space-between;gap:14px;flex-wrap:wrap;
    padding:14px 20px;border-bottom:1px solid var(--c-line);background:#fcfdff}
.cust-len,.cust-find{display:flex;align-items:center;gap:8px;margin:0}
.cust-len label,.cust-find label{margin:0;color:var(--c-soft);font-size:12px;font-weight:900;text-transform:uppercase;letter-spacing:.02em}
.cust-len-select{min-height:38px;padding:6px 30px 6px 12px;border:1px solid var(--c-border);border-radius:8px;
    background:var(--c-bg);color:var(--c-ink);font-size:13px;font-weight:800;cursor:pointer}
.cust-len-select:focus{border-color:var(--c-primary);box-shadow:0 0 0 3px rgba(23,105,194,.12);outline:none}
.cust-find-box{position:relative;display:flex;align-items:center}
.cust-find-box>.bi{position:absolute;left:12px;color:var(--c-muted);font-size:14px;pointer-events:none}
.cust-find-box input{min-height:38px;width:240px;max-width:60vw;padding:7px 32px 7px 34px;border:1px solid var(--c-border);
    border-radius:8px;background:var(--c-bg);color:var(--c-ink);font-weight:700;outline:none}
.cust-find-box input:focus{border-color:var(--c-primary);background:#fff;box-shadow:0 0 0 4px rgba(23,105,194,.12)}
.cust-find-clear{position:absolute;right:11px;color:var(--c-muted);font-size:11px;text-decoration:none}
.cust-find-clear:hover{color:#c53030}

/* Sortable headers */
.cust-th-sort{cursor:pointer}
.cust-table th.cust-th-sort{padding:0}
.cust-sort{display:inline-flex;align-items:center;gap:6px;width:100%;padding:12px 16px;color:var(--c-soft);
    text-decoration:none;font:inherit;font-weight:900;text-transform:uppercase;letter-spacing:.02em;font-size:12px}
.cust-th-sort.text-center .cust-sort{justify-content:center}
.cust-th-sort.text-end .cust-sort{justify-content:flex-end}
.cust-sort .bi{font-size:11px;opacity:.45;transition:opacity .15s ease,color .15s ease}
.cust-sort:hover{color:var(--c-primary)}
.cust-sort:hover .bi{opacity:.9}
.cust-sort.is-sorted{color:var(--c-primary)}
.cust-sort.is-sorted .bi{opacity:1;color:var(--c-primary)}

/* Table */
.cust-table-wrap{overflow-x:auto}
.cust-table{width:100%;margin:0;border-collapse:separate;border-spacing:0}
.cust-table thead th{padding:12px 16px;background:#f7fafc;color:var(--c-soft);font-size:12px;font-weight:900;
    text-transform:uppercase;letter-spacing:.02em;white-space:nowrap;border-bottom:1px solid var(--c-border)}
.cust-table tbody td{padding:12px 16px;border-top:1px solid var(--c-line);color:var(--c-text);font-size:13px;
    font-weight:700;vertical-align:middle}
.cust-table tbody tr:first-child td{border-top:0}
.cust-table tbody tr:hover td{background:var(--c-bg)}
/* Alignment helpers scoped to the table so header + cells always match. */
.cust-table th.text-center,.cust-table td.text-center{text-align:center}
.cust-table th.text-start,.cust-table td.text-start{text-align:left}
.cust-table th.text-end,.cust-table td.text-end{text-align:right}
.cust-table .col-id{white-space:nowrap}
.cust-idchip{display:inline-block;padding:3px 9px;border-radius:6px;background:#eef2f8;border:1px solid #e0e7f1;
    color:#516174;font-size:11.5px;font-weight:800;letter-spacing:.02em;font-variant-numeric:tabular-nums}
.cust-muted{color:var(--c-soft);font-weight:600}
.cust-name{display:flex;align-items:center;gap:10px}
.cust-avatar{display:inline-flex;align-items:center;justify-content:center;width:32px;height:32px;border-radius:50%;
    background:linear-gradient(135deg,#1769c2,#3b82f6);color:#fff;font-size:13px;font-weight:900;flex:0 0 auto}
.cust-name .fw-semibold{color:var(--c-ink)}
/* ---- Compact layout: fixed columns so the narrow Subscription pill frees room
   for Name/Email, which ellipsis-truncate; the full/detailed value for every
   clipped cell lives in its title tooltip (name+firms, plan+dates, email…). ---- */
.cust-table{table-layout:fixed}
.cust-table th,.cust-table td{box-sizing:border-box}
.cust-table th:nth-child(1),.cust-table td:nth-child(1){width:130px}   /* ID (fits CUS-999999 chip, no overflow) */
.cust-table th:nth-child(2),.cust-table td:nth-child(2){width:250px}   /* Name */
.cust-table th:nth-child(3),.cust-table td:nth-child(3){width:290px}   /* Email */
.cust-table .col-sub{width:112px}                                      /* Subscription (plan pill) */
.cust-table th:nth-child(5),.cust-table td:nth-child(5){width:124px}   /* Payment */
.cust-table th:nth-child(6),.cust-table td:nth-child(6){width:112px}   /* Status */
.cust-table th:nth-child(7),.cust-table td:nth-child(7){width:264px}   /* Actions (6 icon buttons) */
/* Name (col 2) + Email (col 3) truncate within their fixed width. */
.cust-name-txt{flex:1 1 auto;min-width:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.cust-firmbadge{display:inline-flex;align-items:center;gap:3px;flex:0 0 auto;padding:2px 7px;border-radius:999px;
    background:#eef2f8;color:#5b6b7f;font-size:11px;font-weight:800;text-decoration:none}
.cust-firmbadge .bi{font-size:11px}
.cust-email .cust-muted{display:block;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.cust-planpill{display:inline-block;max-width:100%;padding:4px 11px;border-radius:999px;background:#eef4ff;
    border:1px solid #d7e6fb;color:#1769c2;font-size:12px;font-weight:800;text-decoration:none;vertical-align:middle;
    white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.cust-planpill:hover{background:#e0edff;border-color:#b9d5f7}
/* ===== Rich customer hover card (hover a name) — adapted from the TrackMe
   account preview, re-skinned to the ERP blue theme + Bootstrap Icons. ===== */
#custTip{position:fixed;z-index:12000;width:360px;max-width:94vw;pointer-events:none;opacity:0;
    transform:translateY(6px) scale(.98);transition:opacity .16s ease,transform .16s ease;font-family:inherit}
#custTip.show{opacity:1;transform:translateY(0) scale(1)}
.cust-tip-box{background:#fff;border:1px solid #e2e9f2;border-radius:16px;overflow:hidden;
    box-shadow:0 26px 70px rgba(15,30,60,.28),0 4px 12px rgba(15,30,60,.12)}
.cust-tip-head{position:relative;padding:15px 16px 13px;color:#fff;overflow:hidden;
    background:linear-gradient(135deg,#0c315f 0%,#1769c2 62%,#2f8fd6 100%)}
.cust-tip-head::after{content:'';position:absolute;right:-40px;top:-50px;width:150px;height:150px;border-radius:50%;background:rgba(255,255,255,.09)}
.cust-tip-head.is-inactive{background:linear-gradient(135deg,#4a5568 0%,#718096 100%)}
.cust-tip-eyebrow{display:flex;align-items:center;gap:7px;font-size:10.5px;font-weight:900;letter-spacing:.05em;text-transform:uppercase;opacity:.9;position:relative;z-index:1}
.cust-tip-name{margin:5px 0 0;font-size:17px;font-weight:900;line-height:1.2;position:relative;z-index:1;word-break:break-word}
.cust-tip-chips{display:flex;flex-wrap:wrap;gap:6px;margin-top:9px;position:relative;z-index:1}
.cust-tip-chip{display:inline-flex;align-items:center;gap:5px;padding:3px 9px;border-radius:20px;font-size:10.5px;font-weight:800;background:rgba(255,255,255,.16);color:#fff}
.cust-tip-chip.ok{background:rgba(255,255,255,.26)}
.cust-tip-chip .bi{font-size:11px}
.cust-tip-stats{display:grid;grid-template-columns:repeat(4,1fr);gap:1px;background:#eef2f7;border-bottom:1px solid #eef2f7}
.cust-tip-stat{background:#fff;padding:11px 6px;text-align:center;min-width:0}
.cust-tip-stat .v{font-size:14px;font-weight:900;color:#18243c;line-height:1.05;font-variant-numeric:tabular-nums;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.cust-tip-stat .l{display:block;margin-top:4px;font-size:9px;font-weight:800;text-transform:uppercase;letter-spacing:.02em;color:#8a97a8;white-space:nowrap}
.cust-tip-body{padding:13px 16px 14px}
.cust-tip-flow{margin-bottom:12px}
.cust-tip-flow-top{display:flex;justify-content:space-between;font-size:11px;font-weight:900;margin-bottom:5px}
.cust-tip-flow-top .dep{color:#1769c2}.cust-tip-flow-top .exp{color:#c0392b}
.cust-tip-bar{height:9px;border-radius:20px;overflow:hidden;display:flex;background:#eef2f7}
.cust-tip-bar .b-dep{background:linear-gradient(90deg,#1769c2,#2f8fd6)}
.cust-tip-bar .b-exp{background:linear-gradient(90deg,#ef4444,#f87171)}
.cust-tip-flow-sub{display:flex;justify-content:space-between;margin-top:5px;font-size:10px;font-weight:800;color:#8a97a8}
.cust-tip-rows{border-top:1px dashed #e6edf5;padding-top:11px}
.cust-tip-row{display:flex;align-items:flex-start;gap:9px;padding:5px 0;font-size:12px}
.cust-tip-row .ic{flex:0 0 auto;width:22px;height:22px;border-radius:7px;display:grid;place-items:center;background:#eef4fc;color:#1769c2;font-size:11px;margin-top:1px}
.cust-tip-row .ct{min-width:0;flex:1}
.cust-tip-row .rl{font-size:9.5px;font-weight:900;text-transform:uppercase;letter-spacing:.03em;color:#a0aec0}
.cust-tip-row .rv{font-weight:800;color:#26374f;word-break:break-word}
.cust-tip-row .rv .muted{color:#b0bac7;font-weight:700}
.cust-tip-foot{padding:9px 16px;background:#f8fafc;border-top:1px solid #eef2f7;font-size:10.5px;font-weight:800;color:#8a97a8;display:flex;align-items:center;gap:6px}
.cust-tip-foot b{color:#516174}
.cust-hover-name{cursor:default;border-bottom:1px dashed transparent}
.cust-hover-name:hover{border-bottom-color:#c7d7ea}
@media (max-width:480px){#custTip{width:300px}.cust-tip-stats{grid-template-columns:repeat(2,1fr)}}
.cust-pill{display:inline-flex;align-items:center;justify-content:center;min-width:30px;height:24px;padding:0 8px;
    border-radius:7px;background:#eef1f6;color:var(--c-soft);font-size:12px;font-weight:900}
.cust-sub-link{display:inline-flex;flex-direction:column;line-height:1.25;text-decoration:none}
.cust-sub-plan{color:var(--c-ink);font-weight:800;font-size:13px}
.cust-sub-status{color:var(--c-muted);font-size:11px;font-weight:700;text-transform:capitalize}
.cust-select{min-height:34px;padding:5px 26px 5px 10px;border:1px solid var(--c-border);border-radius:8px;
    background:var(--c-bg);color:var(--c-ink);font-size:12px;font-weight:800;cursor:pointer}
.cust-select:focus{border-color:var(--c-primary);box-shadow:0 0 0 3px rgba(23,105,194,.12);outline:none}
.cust-select.pay-paid{border-color:#bbe7cf;background:#f0fbf5;color:#137a4c}
.cust-select.pay-unpaid{border-color:#f6c6c9;background:#fdf4f4;color:#c53030}
.cust-select.pay-trial{border-color:#f6dcae;background:#fdf8ef;color:#b7791f}
.cust-badge{display:inline-flex;align-items:center;gap:0;padding:4px 11px 4px 6px;border-radius:20px;font-size:12px;font-weight:800}
.cust-badge .bi{font-size:18px;margin:-4px -2px -4px -4px}
.cust-badge.is-active{background:#e8f7ef;color:#1f9d70}
.cust-badge.is-inactive{background:#f1f5f9;color:#94a3b8}

/* Row action icons */
.cust-row-actions{display:inline-flex;align-items:center;justify-content:center;gap:6px;white-space:nowrap}
.cust-row-actions form{margin:0}
.cust-act{display:inline-flex;align-items:center;justify-content:center;width:32px;height:32px;border-radius:8px;
    border:1px solid transparent;font-size:13px;line-height:1;text-decoration:none;cursor:pointer;background:transparent;
    transition:transform .12s ease,box-shadow .15s ease,background .15s ease,color .15s ease,border-color .15s ease}
.cust-act:hover{transform:translateY(-1px);box-shadow:0 4px 10px rgba(16,24,40,.14)}
.cust-act.act-sub{background:#e9f1fc;color:#1769c2;border-color:#cbe0f7}
.cust-act.act-sub:hover{background:#1769c2;color:#fff;border-color:#1769c2}
.cust-act.act-login{background:#e8f7ef;color:#1f9d70;border-color:#c6ecd8}
.cust-act.act-login:hover{background:#1f9d70;color:#fff;border-color:#1f9d70}
.cust-act.act-pwd{background:#f1ecfe;color:#7c4dff;border-color:#ddd0fb}
.cust-act.act-pwd:hover{background:#7c4dff;color:#fff;border-color:#7c4dff}
.cust-act.act-mail{background:#eef1f6;color:#26374f;border-color:#d7deea}
.cust-act.act-mail:hover{background:#26374f;color:#fff;border-color:#26374f}
.cust-act.act-reset{background:#fff4ed;color:#c2410c;border-color:#fdd6bb}
.cust-act.act-reset:hover{background:#c2410c;color:#fff;border-color:#c2410c}
.cust-act.act-purge{background:#fdecec;color:#c53030;border-color:#f6c6c9}
.cust-act.act-purge:hover{background:#c53030;color:#fff;border-color:#c53030}
.cust-act.act-reset:hover{background:#c2410c;color:#fff;border-color:#c2410c}

/* Empty state */
.cust-empty{text-align:center;padding:44px 16px !important;color:var(--c-muted);font-weight:700}
.cust-empty .bi{font-size:34px;display:block;margin-bottom:8px;opacity:.6}

/* Pager bar — match the reference blue/8px, override the generic modern pager */
.cust-pager-bar{padding:14px 20px;border-top:1px solid var(--c-line)}
.cust-pager-bar .erp-pager__btn{border-radius:8px;border-color:var(--c-border)}
.cust-pager-bar .erp-pager__btn:hover{border-color:#b9d5f5;color:var(--c-primary);background:#edf6ff}
.cust-pager-bar .erp-pager__btn.is-active{background:var(--c-primary);border-color:var(--c-primary);
    box-shadow:0 6px 14px -3px rgba(23,105,194,.5)}
.cust-pager-bar .erp-pager__info b{color:var(--c-ink)}

/* AJAX loading state for the table host */
.cust-host{position:relative;transition:opacity .12s ease;min-height:120px}
.cust-host.is-loading{opacity:.5;pointer-events:none}
.cust-host.is-loading:after{content:"";position:absolute;top:26px;left:50%;width:26px;height:26px;margin-left:-13px;
    border:3px solid #cbd8ea;border-top-color:var(--c-primary);border-radius:50%;animation:custSpin .8s linear infinite;z-index:3}
@keyframes custSpin{to{transform:rotate(360deg)}}

@media (max-width:1100px){.cust-snap-grid{grid-template-columns:repeat(2,minmax(0,1fr))}}
@media (max-width:767px){
    .cust-hero{align-items:stretch;flex-direction:column}
    .cust-hero-actions,.cust-search,.cust-search input,.cust-btn{width:100%}
    .cust-snap-grid{grid-template-columns:1fr}
    .cust-toolbar{align-items:stretch;flex-direction:column}
}
</style>

<script>
(function () {
    var modal = document.getElementById('setPwdModal');
    if (!modal) return;
    modal.addEventListener('show.bs.modal', function (ev) {
        var btn = ev.relatedTarget;
        if (!btn) return;
        var id = btn.getAttribute('data-id');
        document.getElementById('setPwdName').textContent = btn.getAttribute('data-name') || ('#' + id);
        document.getElementById('setPwdForm').setAttribute('action', '<?= site_url('admin/customers/set-password') ?>/' + id);
        document.getElementById('setPwdInput').value = '';
    });
    document.getElementById('setPwdGen').addEventListener('click', function () {
        var lower = 'abcdefghijkmnpqrstuvwxyz', upper = 'ABCDEFGHJKLMNPQRSTUVWXYZ', digits = '23456789', sym = '@#%&*!?';
        var all = lower + upper + digits + sym, out = [
            lower[Math.floor(Math.random() * lower.length)],
            upper[Math.floor(Math.random() * upper.length)],
            digits[Math.floor(Math.random() * digits.length)],
            sym[Math.floor(Math.random() * sym.length)]
        ];
        while (out.length < 12) out.push(all[Math.floor(Math.random() * all.length)]);
        for (var i = out.length - 1; i > 0; i--) { var j = Math.floor(Math.random() * (i + 1)); var t = out[i]; out[i] = out[j]; out[j] = t; }
        document.getElementById('setPwdInput').value = out.join('');
    });
})();
</script>

<script>
/* Permanent-delete modal: populate from the clicked row + require typing the
   exact account name before the Delete button enables. */
(function () {
    var modal = document.getElementById('purgeModal');
    if (!modal) return;
    var form = document.getElementById('purgeForm');
    var input = document.getElementById('purgeConfirm');
    var submit = document.getElementById('purgeSubmit');
    var expected = '';

    function norm(s) { return (s || '').trim().toLowerCase(); }
    function sync() { submit.disabled = norm(input.value) === '' || norm(input.value) !== norm(expected); }

    modal.addEventListener('show.bs.modal', function (ev) {
        var btn = ev.relatedTarget;
        if (!btn) return;
        var id = btn.getAttribute('data-id');
        expected = btn.getAttribute('data-name') || ('#' + id);
        form.setAttribute('action', '<?= site_url('admin/customers/purge') ?>/' + id);
        document.getElementById('purgeName').textContent = expected;
        document.getElementById('purgeExpect').textContent = expected;
        document.getElementById('purgeFirms').textContent = btn.getAttribute('data-firms') || '0';
        input.value = '';
        sync();
    });
    modal.addEventListener('shown.bs.modal', function () { input.focus(); });
    input.addEventListener('input', sync);
    form.addEventListener('submit', function (e) {
        if (submit.disabled) { e.preventDefault(); return; }
        submit.disabled = true;
        submit.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Deleting…';
    });
})();
</script>

<script>
/* Live customers table — search / page-size / sort / pagination via AJAX.
   Only #custTableHost is swapped; the whole page never reloads. */
(function () {
    var host   = document.getElementById('custTableHost');
    if (!host) return;
    var lenSel = document.querySelector('.cust-len-select');
    var lenFrm = document.querySelector('.cust-len');
    var search = document.getElementById('custSearch');
    var findFrm = document.querySelector('.cust-find');
    var DATA_PATH = '<?= site_url('admin/customers/data') ?>';
    var PAGE_PATH = '<?= site_url('admin/customers') ?>';

    // Turn a pretty /admin/customers?… URL into its /data AJAX twin.
    function toDataUrl(pretty) {
        var u = new URL(pretty, location.origin);
        return DATA_PATH + u.search;
    }

    function loadUrl(prettyUrl, push) {
        host.classList.add('is-loading');
        fetch(toDataUrl(prettyUrl), { credentials: 'same-origin', headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(function (r) { return r.json(); })
            .then(function (d) {
                host.innerHTML = d.html;
                if (push !== false) { history.pushState({ ajax: 1 }, '', prettyUrl); }
                host.classList.remove('is-loading');
                // keep focus in the search box while typing
                if (document.activeElement !== search && push !== false && window.__custKeepFocus) {
                    search.focus();
                }
            })
            .catch(function () {
                host.classList.remove('is-loading');
                if (window.erpNotify) { erpNotify('error', 'Could not load customers.'); }
            });
    }

    // Build a new URL from the current one, applying overrides (null = remove).
    function go(overrides) {
        var p = new URLSearchParams(location.search);
        Object.keys(overrides).forEach(function (k) {
            var v = overrides[k];
            if (v === null || v === '' || v === undefined) { p.delete(k); } else { p.set(k, v); }
        });
        var qs = p.toString();
        loadUrl(PAGE_PATH + (qs ? '?' + qs : ''));
    }

    // Debounced live search across ALL columns.
    var t = null;
    if (search) {
        search.addEventListener('input', function () {
            clearTimeout(t);
            window.__custKeepFocus = true;
            t = setTimeout(function () { go({ q: search.value.trim(), page: null }); }, 300);
        });
    }
    if (findFrm) {
        findFrm.addEventListener('submit', function (e) { e.preventDefault(); clearTimeout(t); go({ q: search.value.trim(), page: null }); });
    }

    // Page-size selector.
    if (lenSel) { lenSel.addEventListener('change', function () { window.__custKeepFocus = false; go({ per: lenSel.value, page: null }); }); }
    if (lenFrm) { lenFrm.addEventListener('submit', function (e) { e.preventDefault(); }); }

    // Delegated: sort headers + pager buttons inside the (replaceable) host.
    host.addEventListener('click', function (e) {
        var a = e.target.closest('.cust-sort, .erp-pager__btn');
        if (!a || !host.contains(a)) { return; }
        if (a.classList.contains('is-active')) { e.preventDefault(); return; }
        e.preventDefault();
        window.__custKeepFocus = false;
        loadUrl(a.getAttribute('href'));
    });

    // Back / forward buttons re-sync the fragment without pushing a new entry.
    window.addEventListener('popstate', function () { loadUrl(location.href, false); });
})();
</script>

<!-- Rich customer hover card (mouse over a name) — data comes from each name
     cell's data-tip JSON; delegated so it keeps working after AJAX table swaps. -->
<div id="custTip" aria-hidden="true"></div>
<script>
(function () {
    var tip = document.getElementById('custTip');
    if (!tip) { return; }
    var hideTimer = null, curEl = null;
    function esc(s) { var d = document.createElement('div'); d.textContent = (s == null ? '' : String(s)); return d.innerHTML; }
    function cap(s) { s = String(s || ''); return s ? s.charAt(0).toUpperCase() + s.slice(1) : ''; }
    function tile(v, l) { return '<div class="cust-tip-stat"><div class="v">' + v + '</div><span class="l">' + l + '</span></div>'; }
    function row(ic, l, v) { return '<div class="cust-tip-row"><span class="ic"><i class="bi bi-' + ic + '"></i></span><div class="ct"><div class="rl">' + l + '</div><div class="rv">' + v + '</div></div></div>'; }

    function build(o) {
        var headCls = o.status === 'inactive' ? 'is-inactive' : '';
        var chips = '<span class="cust-tip-chip ok"><i class="bi bi-' + (o.status === 'active' ? 'check-circle-fill' : 'pause-circle-fill') + '"></i> ' + cap(o.status) + '</span>';
        if (o.plan) { chips += '<span class="cust-tip-chip"><i class="bi bi-gem"></i> ' + esc(o.plan) + '</span>'; }
        chips += '<span class="cust-tip-chip"><i class="bi bi-' + (o.source === 'Google' ? 'google' : 'display') + '"></i> ' + esc(o.source || 'Web') + '</span>';

        var stats = tile(o.firms, 'Firms') + tile(esc(o.plan || '—'), 'Plan')
                  + tile(cap(o.payment || '—'), 'Payment') + tile(o.last_ago ? esc(o.last_ago) : '—', 'Last seen');

        var flow = '';
        if ((o.started || o.expires) && o.valid_pct != null) {
            var pct = Math.max(0, Math.min(100, o.valid_pct));
            var expired = (o.days_left != null && o.days_left < 0);
            var right = o.days_left != null ? (expired ? 'Expired' : o.days_left + ' days left') : '';
            flow = '<div class="cust-tip-flow">'
                 +   '<div class="cust-tip-flow-top"><span class="dep">' + esc(o.plan || 'Subscription') + '</span><span class="' + (expired ? 'exp' : 'dep') + '">' + right + '</span></div>'
                 +   '<div class="cust-tip-bar"><div class="b-' + (expired ? 'exp' : 'dep') + '" style="width:' + pct + '%"></div></div>'
                 +   '<div class="cust-tip-flow-sub"><span>' + esc(o.started || '—') + '</span><span>' + esc(o.expires || '—') + '</span></div>'
                 + '</div>';
        }

        var rows = row('envelope', 'Email', esc(o.email || '—'));
        if (o.mobile) { rows += row('telephone', 'Mobile', esc(o.mobile)); }
        rows += row('gem', 'Plan', esc(o.plan || '—') + (o.plan_status ? ' <span class="muted">· ' + esc(cap(o.plan_status)) + '</span>' : ''));
        if (o.started || o.expires) { rows += row('calendar-range', 'Subscription', esc(o.started || '—') + ' <span class="muted">→</span> ' + esc(o.expires || '—')); }
        rows += row('building', 'Firms owned', o.firms + (String(o.firms) === '1' ? ' firm' : ' firms'));

        var foot = '<div class="cust-tip-foot"><i class="bi bi-clock"></i> Joined <b>' + esc(o.created || '—') + '</b>'
                 + (o.created_ago ? ' <span>(' + esc(o.created_ago) + ')</span>' : '') + '</div>';

        return '<div class="cust-tip-box">'
             +   '<div class="cust-tip-head ' + headCls + '">'
             +     '<div class="cust-tip-eyebrow"><i class="bi bi-person-badge"></i> Customer · #' + esc(o.id) + '</div>'
             +     '<div class="cust-tip-name">' + esc(o.name) + '</div>'
             +     '<div class="cust-tip-chips">' + chips + '</div>'
             +   '</div>'
             +   '<div class="cust-tip-stats">' + stats + '</div>'
             +   '<div class="cust-tip-body">' + flow + '<div class="cust-tip-rows">' + rows + '</div></div>'
             +   foot
             + '</div>';
    }

    function place(el) {
        var r = el.getBoundingClientRect(), tw = tip.offsetWidth, th = tip.offsetHeight, vw = window.innerWidth, vh = window.innerHeight, pad = 10;
        var left = r.left, top = r.bottom + 8;
        if (left + tw > vw - pad) { left = Math.max(pad, vw - tw - pad); }
        if (top + th > vh - pad) { top = r.top - th - 8; }
        if (top < pad) { top = pad; }
        tip.style.left = Math.round(left) + 'px';
        tip.style.top = Math.round(top) + 'px';
    }
    function open(el) {
        var raw = el.getAttribute('data-tip'); if (!raw) { return; }
        var o; try { o = JSON.parse(raw); } catch (e) { return; }
        curEl = el;
        tip.innerHTML = build(o);
        tip.setAttribute('aria-hidden', 'false');
        place(el); place(el); // measure then reposition with height known
        tip.classList.add('show');
    }
    function close() { tip.classList.remove('show'); tip.setAttribute('aria-hidden', 'true'); curEl = null; }

    document.addEventListener('mouseover', function (e) {
        var el = e.target.closest ? e.target.closest('.cust-hover-name') : null;
        if (!el) { return; }
        clearTimeout(hideTimer); open(el);
    });
    document.addEventListener('mouseout', function (e) {
        var el = e.target.closest ? e.target.closest('.cust-hover-name') : null;
        if (!el) { return; }
        hideTimer = setTimeout(close, 120);
    });
    window.addEventListener('scroll', function () { if (curEl) { place(curEl); } }, true);
})();
</script>
