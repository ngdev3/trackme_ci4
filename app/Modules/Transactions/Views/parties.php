<?php
/** Party Accounts — editable directory of parties. Rendered in layout.php. */
$rows       = $rows ?? [];
$partyTypes = $partyTypes ?? [];
$canEdit    = $canEdit ?? false;
$search     = $search ?? '';
$money      = static fn ($n) => '₹' . number_format((float) $n, 2);
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
                    <th class="text-start">Type</th>
                    <th class="text-center">Entries</th>
                    <th class="text-end">Jama (In)</th>
                    <th class="text-end">Naam (Out)</th>
                    <th class="text-end">Net</th>
                    <th class="text-start">Last Entry</th>
                    <?php if ($canEdit): ?><th class="text-end">Edit</th><?php endif; ?>
                </tr></thead>
                <tbody>
                <?php if (empty($rows)): ?>
                    <tr><td colspan="<?= $canEdit ? 8 : 7 ?>" class="erp-empty"><i class="bi bi-people"></i><div>No parties found<?= $search !== '' ? ' for “' . esc($search) . '”' : '' ?>.</div></td></tr>
                <?php else: foreach ($rows as $r):
                    $tip = [
                        'type'   => 'Party',
                        'icon'   => 'person-vcard',
                        'name'   => $r['name'],
                        'accent' => $r['net'] < 0 ? 'red' : 'green',
                        'chips'  => array_values(array_filter([
                            $r['party_type'] !== '' ? ['t' => $r['party_type'], 'ic' => 'tag'] : null,
                            ['t' => $r['count'] . ' entries', 'ic' => 'card-list', 'ok' => true],
                        ])),
                        'stats'  => [
                            ['v' => $money($r['jama']), 'l' => 'Jama'],
                            ['v' => $money($r['naam']), 'l' => 'Naam'],
                            ['v' => $money($r['net']), 'l' => 'Net'],
                        ],
                        'rows'   => array_values(array_filter([
                            $r['last_date'] ? ['ic' => 'calendar-event', 'l' => 'Last entry', 'v' => date('d M Y', strtotime((string) $r['last_date']))] : null,
                        ])),
                        'foot'   => $r['count'] . ' transaction' . ($r['count'] === 1 ? '' : 's'),
                    ];
                ?>
                    <tr>
                        <td class="text-start"><?= erp_cell_name((string) $r['name'], $tip) ?></td>
                        <td class="text-start"><?= $r['party_type'] !== '' ? '<span class="erp-pill">' . esc($r['party_type']) . '</span>' : '<span class="erp-muted">—</span>' ?></td>
                        <td class="text-center"><span class="erp-badge"><?= (int) $r['count'] ?></span></td>
                        <td class="text-end text-success fw-semibold"><?= $money($r['jama']) ?></td>
                        <td class="text-end text-danger fw-semibold"><?= $money($r['naam']) ?></td>
                        <td class="text-end fw-bold" style="color:<?= $r['net'] < 0 ? '#c53030' : '#137a4c' ?>"><?= ($r['net'] < 0 ? '−' : '') . '₹' . number_format(abs($r['net']), 2) ?></td>
                        <td class="text-start"><span class="erp-muted"><?= $r['last_date'] ? esc(date('d M Y', strtotime((string) $r['last_date']))) : '—' ?></span></td>
                        <?php if ($canEdit): ?>
                            <td class="text-end">
                                <div class="erp-actions">
                                    <button type="button" class="erp-act" title="Edit party"
                                            data-bs-toggle="modal" data-bs-target="#partyEditModal"
                                            data-name="<?= esc($r['name'], 'attr') ?>" data-type="<?= esc($r['party_type'], 'attr') ?>" data-count="<?= (int) $r['count'] ?>">
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
        <form class="modal-content" method="post" action="<?= site_url('transactions/parties/update') ?>" data-no-validate>
            <?= csrf_field() ?>
            <input type="hidden" name="old_name" id="pe-old">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-person-vcard me-1"></i> Edit party</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <label class="form-label">Party name</label>
                <input type="text" name="new_name" id="pe-name" class="form-control mb-3" maxlength="191" required>
                <label class="form-label">Party type <span class="text-muted">(optional)</span></label>
                <select name="party_type" id="pe-type" class="form-select">
                    <option value="">— None —</option>
                    <?php foreach ($partyTypes as $t): ?>
                        <option value="<?= esc($t, 'attr') ?>"><?= esc($t) ?></option>
                    <?php endforeach; ?>
                </select>
                <div class="form-text mt-2"><i class="bi bi-info-circle me-1"></i>The change applies to <strong id="pe-count">0</strong> entries. Renaming to an existing party merges them.</div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary"><i class="bi bi-check2 me-1"></i> Save changes</button>
            </div>
        </form>
    </div>
</div>
<script>
(function () {
    var modal = document.getElementById('partyEditModal');
    if (!modal) return;
    modal.addEventListener('show.bs.modal', function (ev) {
        var b = ev.relatedTarget; if (!b) return;
        document.getElementById('pe-old').value  = b.getAttribute('data-name') || '';
        document.getElementById('pe-name').value = b.getAttribute('data-name') || '';
        document.getElementById('pe-type').value = b.getAttribute('data-type') || '';
        document.getElementById('pe-count').textContent = b.getAttribute('data-count') || '0';
    });
})();
</script>
<?php endif; ?>
