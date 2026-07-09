<?php
/** Stock Position — opening / purchase / sale / closing per product, by day/month/year. */
$fmt   = static fn ($n) => number_format((float) $n, 0);
$money = static fn ($n) => (float) $n != 0 ? money($n) : '—';
$periods = ['day' => 'Day', 'month' => 'Month', 'year' => 'Year'];
?>
<div class="sinv">
    <?= view('Modules\Inventory\Views\_nav', ['active' => 'position']) ?>

    <div class="sinv-pos-head">
        <div class="sinv-period">
            <?php foreach ($periods as $k => $lbl): ?>
                <a href="<?= site_url('inventory/position?period=' . $k) ?>" class="sinv-period-btn<?= $period === $k ? ' on' : '' ?>"><?= $lbl ?></a>
            <?php endforeach; ?>
        </div>
        <form method="get" action="<?= site_url('inventory/position') ?>" class="sinv-picker">
            <input type="hidden" name="period" value="<?= esc($period, 'attr') ?>">
            <?php if ($period === 'day'): ?>
                <input type="date" name="date" value="<?= esc($pickerValue, 'attr') ?>" max="<?= date('Y-m-d') ?>" class="form-control" onchange="this.form.submit()">
            <?php elseif ($period === 'year'): ?>
                <input type="number" name="year" value="<?= esc($pickerValue, 'attr') ?>" min="2000" max="<?= date('Y') ?>" class="form-control" onchange="this.form.submit()">
            <?php else: ?>
                <input type="month" name="month" value="<?= esc($pickerValue, 'attr') ?>" max="<?= date('Y-m') ?>" class="form-control" onchange="this.form.submit()">
            <?php endif; ?>
        </form>
    </div>

    <div class="sinv-card">
        <h3 class="sinv-h"><i class="bi bi-clipboard-data me-1"></i>Stock Position — <?= esc($rangeLabel) ?></h3>

        <?php if (empty($rows)): ?>
            <div class="inv-empty-mini"><i class="bi bi-inbox"></i>No products yet. <a href="<?= site_url('inventory/products') ?>">Add products</a> and record stock.</div>
        <?php else: ?>
            <div class="sinv-table-wrap">
                <table class="sinv-table">
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th class="num">Opening <span class="unit-tag">Qtl</span></th>
                            <th class="num in">Purchase (IN) <span class="unit-tag">Qtl</span></th>
                            <th class="num out">Sale (OUT) <span class="unit-tag">Qtl</span></th>
                            <th class="num">Closing <span class="unit-tag">Qtl</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($rows as $r): ?>
                            <tr>
                                <td><strong><?= esc($r['name']) ?></strong> <small class="text-secondary"><?= esc($r['unit']) ?></small></td>
                                <td class="num"><?= $fmt($r['opening']) ?></td>
                                <td class="num in"><?= $r['in_qty'] > 0 ? '+' . $fmt($r['in_qty']) : '0' ?><small><?= $money($r['in_amt']) ?></small></td>
                                <td class="num out"><?= $r['out_qty'] > 0 ? '−' . $fmt($r['out_qty']) : '0' ?><small><?= $money($r['out_amt']) ?></small></td>
                                <td class="num"><strong><?= $fmt($r['closing']) ?></strong></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr>
                            <td>Total</td>
                            <td class="num"><?= $fmt($totals['opening']) ?></td>
                            <td class="num in">+<?= $fmt($totals['in_qty']) ?><small><?= $money($totals['in_amt']) ?></small></td>
                            <td class="num out">−<?= $fmt($totals['out_qty']) ?><small><?= $money($totals['out_amt']) ?></small></td>
                            <td class="num"><?= $fmt($totals['closing']) ?></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>
