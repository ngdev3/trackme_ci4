<?php

use CodeIgniter\Pager\PagerRenderer;

/**
 * Modern ERP pager template — Bootstrap-classed (page-item/page-link) with
 * icon arrows, styled by .tm-pager in users.css. Compact surround for large
 * page counts.
 *
 * @var PagerRenderer $pager
 */
$pager->setSurroundCount(1);
?>
<ul class="pagination">
    <?php if ($pager->hasPrevious()): ?>
        <li class="page-item"><a class="page-link" href="<?= $pager->getFirst() ?>" aria-label="First"><i class="bi bi-chevron-double-left"></i></a></li>
        <li class="page-item"><a class="page-link" href="<?= $pager->getPrevious() ?>" aria-label="Previous"><i class="bi bi-chevron-left"></i></a></li>
    <?php endif ?>

    <?php foreach ($pager->links() as $link): ?>
        <li class="page-item <?= $link['active'] ? 'active' : '' ?>">
            <a class="page-link" href="<?= $link['uri'] ?>"><?= $link['title'] ?></a>
        </li>
    <?php endforeach ?>

    <?php if ($pager->hasNext()): ?>
        <li class="page-item"><a class="page-link" href="<?= $pager->getNext() ?>" aria-label="Next"><i class="bi bi-chevron-right"></i></a></li>
        <li class="page-item"><a class="page-link" href="<?= $pager->getLast() ?>" aria-label="Last"><i class="bi bi-chevron-double-right"></i></a></li>
    <?php endif ?>
</ul>
