<?php
/** Party Accounts — editable directory with balance-sheet fields. In layout.php. */
$rows       = $rows ?? [];
$partyTypes = $partyTypes ?? [];
$canEdit    = $canEdit ?? false;
$search     = $search ?? '';
$money      = static fn ($n) => '₹' . number_format((float) $n, 2);
$signed     = static function (float $n): string {
    return ($n < 0 ? '−' : '') . '₹' . number_format(abs($n), 2);
};
$roleMeta   = ['customer' => ['Customer', 'green'], 'supplier' => ['Supplier', 'amber'], 'both' => ['Both', '']];
?>
<div class="card">
    <div class="card-header d-flex flex-wrap gap-2 justify-content-between align-items-center">
        <h3 class="card-title mb-0"><i class="bi bi-people me-1"></i> Party Accounts <span class="erp-pill gray ms-1"><?= count($rows) ?></span></h3>
        <form class="d-flex gap-2" method="get">
            <input type="search" name="q" value="<?= esc($search) ?>" class="form-control form-control-sm" placeholder="Search party…">
            <button class="btn btn-sm btn-outline-secondary"><i class="bi bi-search"></i></button>
        </form>
    </div>
    <div class="card-body p-0">
        <div class="erp-tbl-wrap">
            <table class="erp-tbl auto">
                <thead><tr>
                    <th class="text-start">Party</th>
                    <th class="text-start">Role / Type</th>
                    <th class="text-start">Mobile</th>
                    <th class="text-center">Entries</th>
                    <th class="text-end">Opening</th>
                    <th class="text-end">Net (Jama−Naam)</th>
                    <th class="text-end">Balance</th>
                    <?php if ($canEdit): ?><th class="text-end">Edit</th><?php endif; ?>
                </tr></thead>
                <tbody>
                <?php if (empty($rows)): ?>
                    <tr><td colspan="<?= $canEdit ? 8 : 7 ?>" class="erp-empty"><i class="bi bi-people"></i><div>No parties found<?= $search !== '' ? ' for “' . esc($search) . '”' : '' ?>.</div></td></tr>
                <?php else: foreach ($rows as $r):
                    $openLabel = $signed(($r['opening_type'] === 'cr' ? -1 : 1) * (float) $r['opening_balance']);
                    $tip = [
                        'type'   => 'Party',
                        'icon'   => 'person-vcard',
                        'name'   => $r['name'],
                        'accent' => $r['balance'] < 0 ? 'red' : 'green',
                        'chips'  => array_values(array_filter([
                            ! empty($r['party_role']) && isset($roleMeta[$r['party_role']]) ? ['t' => $roleMeta[$r['party_role']][0], 'ic' => 'person-badge', 'ok' => true] : null,
                            $r['party_type'] !== '' ? ['t' => $r['party_type'], 'ic' => 'tag'] : null,
                            ['t' => $r['count'] . ' entries', 'ic' => 'card-list'],
                        ])),
                        'stats'  => [
                            ['v' => $money($r['jama']), 'l' => 'Jama'],
                            ['v' => $money($r['naam']), 'l' => 'Naam'],
                            ['v' => $signed((float) $r['balance']), 'l' => 'Balance'],
                        ],
                        'rows'   => array_values(array_filter([
                            $r['mobile']     ? ['ic' => 'telephone', 'l' => 'Mobile', 'v' => $r['mobile']] : null,
                            $r['email']      ? ['ic' => 'envelope', 'l' => 'Email', 'v' => $r['email']] : null,
                            $r['gst_number'] ? ['ic' => 'upc-scan', 'l' => 'GST', 'v' => $r['gst_number']] : null,
                            $r['address']    ? ['ic' => 'geo-alt', 'l' => 'Address', 'v' => $r['address']] : null,
                            ['ic' => 'wallet2', 'l' => 'Opening balance', 'v' => $openLabel],
                            $r['last_date']  ? ['ic' => 'calendar-event', 'l' => 'Last entry', 'v' => date('d M Y', strtotime((string) $r['last_date']))] : null,
                        ])),
                        'foot'   => $r['count'] . ' transaction' . ($r['count'] === 1 ? '' : 's'),
                    ];
                ?>
                    <tr>
                        <td class="text-start"><?= erp_cell_name((string) $r['name'], $tip) ?></td>
                        <td class="text-start">
                            <?php if (! empty($r['party_role']) && isset($roleMeta[$r['party_role']])): [$rl, $rc] = $roleMeta[$r['party_role']]; ?>
                                <span class="erp-pill <?= $rc ?>"><?= esc($rl) ?></span>
                            <?php endif; ?>
                            <?php if ($r['party_type'] !== ''): ?><span class="erp-pill gray"><?= esc($r['party_type']) ?></span><?php endif; ?>
                            <?php if (empty($r['party_role']) && $r['party_type'] === ''): ?><span class="erp-muted">—</span><?php endif; ?>
                        </td>
                        <td class="text-start"><span class="erp-muted"><?= esc($r['mobile'] ?: '—') ?></span></td>
                        <td class="text-center"><span class="erp-badge"><?= (int) $r['count'] ?></span></td>
                        <td class="text-end"><span class="erp-muted"><?= $openLabel ?></span></td>
                        <td class="text-end"><?= $signed((float) $r['net']) ?></td>
                        <td class="text-end fw-bold" style="color:<?= $r['balance'] < 0 ? '#c53030' : '#137a4c' ?>"><?= $signed((float) $r['balance']) ?></td>
                        <?php if ($canEdit): ?>
                            <td class="text-end">
                                <div class="erp-actions">
                                    <button type="button" class="erp-act" title="Edit party"
                                            data-bs-toggle="modal" data-bs-target="#partyEditModal"
                                            data-name="<?= esc($r['name'], 'attr') ?>"
                                            data-type="<?= esc($r['party_type'], 'attr') ?>"
                                            data-role="<?= esc($r['party_role'], 'attr') ?>"
                                            data-mobile="<?= esc($r['mobile'], 'attr') ?>"
                                            data-email="<?= esc($r['email'], 'attr') ?>"
                                            data-address="<?= esc($r['address'], 'attr') ?>"
                                            data-gst="<?= esc($r['gst_number'], 'attr') ?>"
                                            data-open-bal="<?= esc((string) $r['opening_balance'], 'attr') ?>"
                                            data-open-type="<?= esc($r['opening_type'], 'attr') ?>"
                                            data-notes="<?= esc($r['notes'], 'attr') ?>"
                                            data-count="<?= (int) $r['count'] ?>"
                                            data-net="<?= esc((string) $r['net'], 'attr') ?>"
                                            data-balance="<?= esc((string) $r['balance'], 'attr') ?>">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                </div>
                            </td>
                        <?php endif; ?>
                    </tr>
                <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php if ($canEdit): ?>
<div class="modal fade" id="partyEditModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form class="modal-content party-edit" method="post" action="<?= site_url('transactions/parties/update') ?>" data-no-validate>
            <?= csrf_field() ?>
            <input type="hidden" name="old_name" id="pe-old">

            <!-- Gradient hero with live avatar + balance -->
            <div class="pe-hero">
                <button type="button" class="pe-close" data-bs-dismiss="modal" aria-label="Close"><i class="bi bi-x-lg"></i></button>
                <div class="pe-avatar" id="pe-avatar">?</div>
                <div class="pe-hero-main">
                    <span class="pe-eyebrow"><i class="bi bi-person-vcard"></i> Edit Party</span>
                    <span class="pe-hero-name" id="pe-heroname">Party</span>
                    <span class="pe-hero-sub"><i class="bi bi-card-list"></i> <span id="pe-count">0</span> entries</span>
                </div>
                <div class="pe-hero-bal">
                    <span class="pe-bal-cap">Balance</span>
                    <span class="pe-bal-val" id="pe-herobal">₹0.00</span>
                </div>
            </div>

            <div class="pe-body">
                <!-- Identity -->
                <section class="pe-sec">
                    <div class="pe-sec-head"><span class="pe-sec-ic pe-ic-blue"><i class="bi bi-person-badge"></i></span> Identity</div>
                    <div class="pe-field">
                        <input type="text" name="new_name" id="pe-name" class="pe-input" maxlength="191" required placeholder=" ">
                        <label>Party name</label>
                    </div>

                    <span class="pe-sublabel">Role</span>
                    <div class="pe-rolepick">
                        <label class="pe-role"><input type="radio" name="party_role" value="customer"><span class="pe-role-card cust"><i class="bi bi-person-check-fill"></i>Customer</span></label>
                        <label class="pe-role"><input type="radio" name="party_role" value="supplier"><span class="pe-role-card supp"><i class="bi bi-truck"></i>Supplier</span></label>
                        <label class="pe-role"><input type="radio" name="party_role" value="both"><span class="pe-role-card both"><i class="bi bi-arrow-left-right"></i>Both</span></label>
                        <label class="pe-role"><input type="radio" name="party_role" value=""><span class="pe-role-card none"><i class="bi bi-dash-circle"></i>None</span></label>
                    </div>

                    <div class="pe-field pe-select">
                        <select name="party_type" id="pe-type" class="pe-input">
                            <option value=""></option>
                            <?php foreach ($partyTypes as $t): ?>
                                <option value="<?= esc($t, 'attr') ?>"><?= esc($t) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <label>Party type</label>
                    </div>
                </section>

                <!-- Contact -->
                <section class="pe-sec">
                    <div class="pe-sec-head"><span class="pe-sec-ic pe-ic-green"><i class="bi bi-telephone-fill"></i></span> Contact</div>
                    <div class="pe-grid">
                        <div class="pe-field"><input type="text" name="mobile" id="pe-mobile" class="pe-input" maxlength="20" placeholder=" "><label>Mobile</label></div>
                        <div class="pe-field"><input type="email" name="email" id="pe-email" class="pe-input" maxlength="191" placeholder=" "><label>Email</label></div>
                        <div class="pe-field pe-col2"><input type="text" name="address" id="pe-address" class="pe-input" maxlength="255" placeholder=" "><label>Address</label></div>
                        <div class="pe-field pe-col2"><input type="text" name="gst_number" id="pe-gst" class="pe-input text-uppercase" maxlength="20" placeholder=" "><label>GST number</label></div>
                    </div>
                </section>

                <!-- Opening balance -->
                <section class="pe-sec">
                    <div class="pe-sec-head"><span class="pe-sec-ic pe-ic-amber"><i class="bi bi-wallet2"></i></span> Opening Balance</div>
                    <div class="pe-openrow">
                        <div class="pe-field pe-money"><span class="pe-rupee">₹</span><input type="number" step="0.01" min="0" name="opening_balance" id="pe-openbal" class="pe-input" value="0" placeholder=" "><label>Amount</label></div>
                        <div class="pe-drcr">
                            <label class="pe-tg"><input type="radio" name="opening_type" value="dr"><span><i class="bi bi-arrow-down-left"></i>To receive · Dr</span></label>
                            <label class="pe-tg"><input type="radio" name="opening_type" value="cr"><span><i class="bi bi-arrow-up-right"></i>To pay · Cr</span></label>
                        </div>
                    </div>
                    <div class="pe-field"><textarea name="notes" id="pe-notes" class="pe-input" rows="2" maxlength="500" placeholder=" "></textarea><label>Notes</label></div>
                </section>

                <p class="pe-merge"><i class="bi bi-shuffle"></i> Renaming to an existing party merges them — entries &amp; details combine.</p>
            </div>

            <div class="pe-footer">
                <button type="button" class="pe-btn pe-btn-ghost" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="pe-btn pe-btn-save"><i class="bi bi-check2-circle"></i> Save changes</button>
            </div>
        </form>
    </div>
</div>

<style nonce="{csp-style-nonce}">
.party-edit { border: 0; border-radius: 20px; overflow: hidden; box-shadow: 0 30px 80px rgba(15,30,60,.32); }
#partyEditModal .modal-dialog { max-width: 560px; }
/* Hero */
.pe-hero { position: relative; display: flex; align-items: center; gap: 14px; padding: 20px 22px;
    background: linear-gradient(135deg, #0c315f 0%, #1769c2 60%, #2f8fd6 100%); color: #fff; overflow: hidden; }
.pe-hero::after { content: ''; position: absolute; right: -50px; top: -60px; width: 180px; height: 180px; border-radius: 50%; background: rgba(255,255,255,.09); }
.pe-close { position: absolute; top: 12px; right: 12px; width: 32px; height: 32px; border: 0; border-radius: 50%; background: rgba(255,255,255,.16); color: #fff; display: grid; place-items: center; cursor: pointer; z-index: 2; transition: background .15s; }
.pe-close:hover { background: rgba(255,255,255,.3); }
.pe-avatar { flex: 0 0 auto; width: 56px; height: 56px; border-radius: 16px; display: grid; place-items: center; font-size: 24px; font-weight: 900; color: #0c315f; background: linear-gradient(135deg, #ffd23f, #ffb347); box-shadow: 0 8px 20px rgba(255,178,71,.45); position: relative; z-index: 1; }
.pe-hero-main { display: flex; flex-direction: column; min-width: 0; flex: 1; position: relative; z-index: 1; }
.pe-eyebrow { font-size: 10.5px; font-weight: 800; letter-spacing: .08em; text-transform: uppercase; opacity: .85; display: inline-flex; align-items: center; gap: 5px; }
.pe-hero-name { font-size: 20px; font-weight: 900; line-height: 1.15; margin-top: 2px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.pe-hero-sub { font-size: 11.5px; opacity: .85; margin-top: 3px; display: inline-flex; align-items: center; gap: 5px; }
.pe-hero-bal { text-align: right; position: relative; z-index: 1; flex: 0 0 auto; }
.pe-bal-cap { display: block; font-size: 9.5px; font-weight: 800; letter-spacing: .06em; text-transform: uppercase; opacity: .8; }
.pe-bal-val { font-size: 17px; font-weight: 900; font-variant-numeric: tabular-nums; }
/* Body */
.pe-body { padding: 20px 22px 6px; max-height: 62vh; overflow-y: auto; background: #f8fafc; }
.pe-sec { background: #fff; border: 1px solid #eef2f7; border-radius: 16px; padding: 16px; margin-bottom: 14px; }
.pe-sec-head { display: flex; align-items: center; gap: 9px; font-size: 12px; font-weight: 900; text-transform: uppercase; letter-spacing: .04em; color: #64748b; margin-bottom: 14px; }
.pe-sec-ic { width: 28px; height: 28px; border-radius: 9px; display: grid; place-items: center; font-size: 14px; }
.pe-ic-blue { background: #e9f1fc; color: #1769c2; } .pe-ic-green { background: #e8f7ef; color: #1f9d70; } .pe-ic-amber { background: #fff4ed; color: #c2410c; }
/* Floating-label inputs */
.pe-field { position: relative; margin-bottom: 12px; }
.pe-field:last-child { margin-bottom: 0; }
.pe-input { width: 100%; padding: 16px 12px 7px; border: 1.5px solid #e2e9f2; border-radius: 11px; font-size: 14px; font-weight: 600; color: #18243c; background: #fff; outline: none; transition: border-color .15s, box-shadow .15s; }
textarea.pe-input { resize: vertical; min-height: 62px; padding-top: 20px; }
.pe-input:focus { border-color: #1769c2; box-shadow: 0 0 0 3px rgba(23,105,194,.12); }
.pe-field label { position: absolute; left: 12px; top: 13px; font-size: 13px; font-weight: 600; color: #97a3b4; pointer-events: none; transition: all .14s ease; }
.pe-field .pe-input:focus + label,
.pe-field .pe-input:not(:placeholder-shown) + label { top: 5px; font-size: 10px; font-weight: 800; letter-spacing: .02em; color: #1769c2; text-transform: uppercase; }
.pe-select select.pe-input { appearance: none; background: url("data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' width='14' height='14' fill='%2397a3b4' viewBox='0 0 16 16'><path d='M8 11L3 6h10z'/></svg>") no-repeat right 12px center; }
.pe-select label { top: 5px; font-size: 10px; font-weight: 800; letter-spacing: .02em; color: #1769c2; text-transform: uppercase; }
.pe-money .pe-rupee { position: absolute; left: 12px; top: 15px; font-weight: 800; color: #64748b; z-index: 1; }
.pe-money .pe-input { padding-left: 26px; }
.pe-money label { left: 26px; }
/* Role picker */
.pe-sublabel { display: block; font-size: 10px; font-weight: 800; letter-spacing: .03em; text-transform: uppercase; color: #97a3b4; margin: 2px 0 8px; }
.pe-rolepick { display: grid; grid-template-columns: repeat(4, 1fr); gap: 8px; margin-bottom: 14px; }
.pe-role input { position: absolute; opacity: 0; pointer-events: none; }
.pe-role-card { display: flex; flex-direction: column; align-items: center; gap: 5px; padding: 12px 4px; border: 1.5px solid #e2e9f2; border-radius: 12px; cursor: pointer; font-size: 12px; font-weight: 700; color: #64748b; background: #fff; transition: all .14s ease; text-align: center; }
.pe-role-card i { font-size: 18px; }
.pe-role-card:hover { border-color: #cbd8e8; transform: translateY(-1px); }
.pe-role input:checked + .pe-role-card.cust { border-color: #1f9d70; background: #e8f7ef; color: #137a4c; box-shadow: 0 4px 12px rgba(31,157,112,.18); }
.pe-role input:checked + .pe-role-card.supp { border-color: #c2410c; background: #fff4ed; color: #c2410c; box-shadow: 0 4px 12px rgba(194,65,12,.18); }
.pe-role input:checked + .pe-role-card.both { border-color: #1769c2; background: #e9f1fc; color: #1769c2; box-shadow: 0 4px 12px rgba(23,105,194,.18); }
.pe-role input:checked + .pe-role-card.none { border-color: #94a3b8; background: #f1f5f9; color: #475569; }
/* Dr/Cr toggle */
.pe-openrow { display: grid; grid-template-columns: 1fr 1.2fr; gap: 12px; margin-bottom: 12px; align-items: start; }
.pe-drcr { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; }
.pe-tg input { position: absolute; opacity: 0; pointer-events: none; }
.pe-tg span { display: flex; flex-direction: column; align-items: center; gap: 3px; padding: 11px 6px; border: 1.5px solid #e2e9f2; border-radius: 11px; cursor: pointer; font-size: 11.5px; font-weight: 700; color: #64748b; background: #fff; transition: all .14s; text-align: center; }
.pe-tg span i { font-size: 15px; }
.pe-tg input:checked + span { border-color: #1769c2; background: #e9f1fc; color: #1769c2; box-shadow: 0 4px 12px rgba(23,105,194,.16); }
.pe-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
.pe-grid .pe-col2 { grid-column: span 2; }
.pe-merge { display: flex; align-items: center; gap: 7px; font-size: 12px; color: #8a97a8; margin: 4px 2px 12px; }
/* Footer */
.pe-footer { display: flex; gap: 10px; padding: 14px 22px 18px; background: #f8fafc; border-top: 1px solid #eef2f7; }
.pe-btn { flex: 1; padding: 13px; border: 0; border-radius: 12px; font-size: 14px; font-weight: 800; cursor: pointer; display: inline-flex; align-items: center; justify-content: center; gap: 7px; transition: transform .12s, box-shadow .15s, filter .15s; }
.pe-btn-ghost { flex: 0 0 auto; padding: 13px 22px; background: #eef2f7; color: #516174; }
.pe-btn-ghost:hover { background: #e2e9f2; }
.pe-btn-save { background: linear-gradient(135deg, #1769c2, #2f8fd6); color: #fff; box-shadow: 0 8px 20px rgba(23,105,194,.32); }
.pe-btn-save:hover { transform: translateY(-2px); box-shadow: 0 12px 26px rgba(23,105,194,.4); filter: brightness(1.05); }
@media (max-width: 480px) { .pe-rolepick { grid-template-columns: 1fr 1fr; } .pe-openrow { grid-template-columns: 1fr; } .pe-grid .pe-col2 { grid-column: auto; } .pe-grid { grid-template-columns: 1fr; } }
</style>
<script nonce="{csp-script-nonce}">
(function () {
    var modal = document.getElementById('partyEditModal');
    if (!modal) return;
    var money = function (n) { n = Number(n) || 0; return (n < 0 ? '−₹' : '₹') + Math.abs(n).toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 }); };
    function set(id, v) { var el = document.getElementById(id); if (el) el.value = v || ''; }
    function checkRadio(name, val) {
        modal.querySelectorAll('input[name="' + name + '"]').forEach(function (r) { r.checked = (r.value === (val || '')); });
    }
    var net = 0;
    function recalcBal() {
        var open = parseFloat(document.getElementById('pe-openbal').value) || 0;
        var type = (modal.querySelector('input[name="opening_type"]:checked') || {}).value || 'dr';
        var bal = net + (type === 'cr' ? -open : open);
        var el = document.getElementById('pe-herobal'); if (el) el.textContent = money(bal);
    }
    modal.addEventListener('show.bs.modal', function (ev) {
        var b = ev.relatedTarget; if (!b) return;
        var name = b.getAttribute('data-name') || '';
        net = parseFloat(b.getAttribute('data-net')) || 0;
        set('pe-old', name); set('pe-name', name);
        set('pe-type', b.getAttribute('data-type'));
        set('pe-mobile', b.getAttribute('data-mobile'));
        set('pe-email', b.getAttribute('data-email'));
        set('pe-address', b.getAttribute('data-address'));
        set('pe-gst', b.getAttribute('data-gst'));
        set('pe-openbal', b.getAttribute('data-open-bal') || '0');
        set('pe-notes', b.getAttribute('data-notes'));
        checkRadio('party_role', b.getAttribute('data-role'));
        checkRadio('opening_type', b.getAttribute('data-open-type') || 'dr');
        document.getElementById('pe-count').textContent = b.getAttribute('data-count') || '0';
        document.getElementById('pe-heroname').textContent = name || 'Party';
        document.getElementById('pe-avatar').textContent = (name.trim().charAt(0) || '?').toUpperCase();
        document.getElementById('pe-herobal').textContent = money(parseFloat(b.getAttribute('data-balance')) || 0);
    });
    // Live hero updates
    modal.addEventListener('input', function (e) {
        if (e.target.id === 'pe-name') {
            var v = e.target.value.trim();
            document.getElementById('pe-heroname').textContent = v || 'Party';
            document.getElementById('pe-avatar').textContent = (v.charAt(0) || '?').toUpperCase();
        }
        if (e.target.id === 'pe-openbal') { recalcBal(); }
    });
    modal.addEventListener('change', function (e) { if (e.target.name === 'opening_type') { recalcBal(); } });
})();
</script>
<?php endif; ?>
