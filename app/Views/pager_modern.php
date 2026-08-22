<?php

use CodeIgniter\Pager\PagerRenderer;

/**
 * Modern pager template.
 *
 * Renders a full-width footer bar: a "Showing X–Y of Z" results summary on the
 * left and a rounded page-button group on the right (First / Prev icons, a
 * windowed run of page numbers with … gaps, then Next / Last icons). Fully
 * self-contained — its CSS is printed once per request and is theme-aware.
 *
 * Use from a view:  <?= $pager->links('default', 'modern') ?>
 *
 * @var PagerRenderer $pager
 */
$pager->setSurroundCount(2);

$links     = $pager->links();                 // windowed [{uri,title,active}]
// Overall bounds (NOT getFirst/LastPageNumber — those return the WINDOW's ends).
$firstPage = 1;
$lastPage  = max(1, $pager->getPageCount());
$total     = (int) ($pager->getTotal() ?? 0);
$start     = (int) ($pager->getPerPageStart() ?? 0);
$end       = (int) ($pager->getPerPageEnd() ?? 0);

// Page numbers currently in the window (titles are the page numbers).
$windowNums = array_map(static fn ($l) => (int) $l['title'], $links);
$winFirst   = $windowNums[0] ?? $firstPage;
$winLast    = end($windowNums) ?: $lastPage;

// Print the CSS only once even if several pagers appear on one page.
static $cssDone = false;
?>
<?php if (! $cssDone) { $cssDone = true; ?>
<style>
.erp-pager{display:flex;flex-wrap:wrap;align-items:center;justify-content:space-between;gap:.75rem 1rem;width:100%}
.erp-pager__info{font-size:.82rem;color:var(--bs-secondary-color,#6b7280)}
.erp-pager__info b{color:var(--bs-body-color,#111827);font-weight:600}
.erp-pager__nav{display:flex;align-items:center;gap:.35rem;flex-wrap:wrap}
.erp-pager__btn{
    --sz:40px;
    display:inline-flex;align-items:center;justify-content:center;
    min-width:var(--sz);height:var(--sz);padding:0 .6rem;
    border:1px solid var(--bs-border-color,#e5e7eb);border-radius:12px;
    background:var(--bs-body-bg,#fff);color:var(--bs-body-color,#374151);
    font-size:.9rem;font-weight:600;line-height:1;text-decoration:none;
    transition:all .15s ease;user-select:none;
}
.erp-pager__btn:hover{border-color:var(--bs-primary,#0d6efd);color:var(--bs-primary,#0d6efd);
    background:color-mix(in srgb, var(--bs-primary,#0d6efd) 8%, transparent);transform:translateY(-1px)}
.erp-pager__btn:active{transform:translateY(0)}
.erp-pager__btn.is-active{
    background:var(--bs-primary,#0d6efd);border-color:var(--bs-primary,#0d6efd);color:#fff;
    box-shadow:0 4px 12px -2px color-mix(in srgb, var(--bs-primary,#0d6efd) 55%, transparent);
    cursor:default;pointer-events:none;
}
.erp-pager__btn.is-icon{padding:0}
.erp-pager__gap{display:inline-flex;align-items:flex-end;justify-content:center;
    min-width:26px;height:40px;color:var(--bs-secondary-color,#9ca3af);padding-bottom:.4rem;letter-spacing:1px}
@media (max-width:575.98px){
    .erp-pager{justify-content:center}
    .erp-pager__btn{--sz:36px;border-radius:10px;font-size:.82rem}
    /* keep the run compact on phones: hide non-adjacent numbers, keep arrows + active */
    .erp-pager__num{display:none}
    .erp-pager__num.is-active,.erp-pager__num.is-edge{display:inline-flex}
}
</style>
<?php } ?>

<nav class="erp-pager" aria-label="Pagination">
    <div class="erp-pager__info">
        <?php if ($total > 0): ?>
            Showing <b><?= number_format($start) ?></b>–<b><?= number_format($end) ?></b> of <b><?= number_format($total) ?></b>
        <?php else: ?>
            No results
        <?php endif ?>
    </div>

    <div class="erp-pager__nav">
        <?php if ($pager->hasPrevious()): ?>
            <a class="erp-pager__btn is-icon" href="<?= $pager->getFirst() ?>" aria-label="First page"><i class="bi bi-chevron-double-left"></i></a>
            <a class="erp-pager__btn is-icon" href="<?= $pager->getPrevious() ?>" aria-label="Previous page"><i class="bi bi-chevron-left"></i></a>
        <?php endif ?>

        <?php // Leading edge: first page + gap when the window starts past page 1. ?>
        <?php if ($winFirst > $firstPage): ?>
            <a class="erp-pager__btn erp-pager__num is-edge" href="<?= $pager->getFirst() ?>"><?= number_format($firstPage) ?></a>
            <?php if ($winFirst > $firstPage + 1): ?><span class="erp-pager__gap">…</span><?php endif ?>
        <?php endif ?>

        <?php foreach ($links as $link): ?>
            <a class="erp-pager__btn erp-pager__num <?= $link['active'] ? 'is-active' : '' ?>"
               href="<?= $link['uri'] ?>" <?= $link['active'] ? 'aria-current="page"' : '' ?>><?= number_format((int) $link['title']) ?></a>
        <?php endforeach ?>

        <?php // Trailing edge: gap + last page when the window ends before the end. ?>
        <?php if ($winLast < $lastPage): ?>
            <?php if ($winLast < $lastPage - 1): ?><span class="erp-pager__gap">…</span><?php endif ?>
            <a class="erp-pager__btn erp-pager__num is-edge" href="<?= $pager->getLast() ?>"><?= number_format($lastPage) ?></a>
        <?php endif ?>

        <?php if ($pager->hasNext()): ?>
            <a class="erp-pager__btn is-icon" href="<?= $pager->getNext() ?>" aria-label="Next page"><i class="bi bi-chevron-right"></i></a>
            <a class="erp-pager__btn is-icon" href="<?= $pager->getLast() ?>" aria-label="Last page"><i class="bi bi-chevron-double-right"></i></a>
        <?php endif ?>
    </div>
</nav>
