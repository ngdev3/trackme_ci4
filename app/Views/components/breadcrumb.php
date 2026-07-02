<?php
/**
 * Breadcrumb component.
 * @var array $items  list of ['label' => string, 'url' => ?string]
 */
$items = $items ?? [];
?>
<ol class="breadcrumb float-sm-end">
    <li class="breadcrumb-item"><a href="<?= site_url('dashboard') ?>">Home</a></li>
    <?php foreach ($items as $i => $item): ?>
        <?php $isLast = $i === array_key_last($items); ?>
        <?php if ($isLast || empty($item['url'])): ?>
            <li class="breadcrumb-item active" aria-current="page"><?= esc($item['label']) ?></li>
        <?php else: ?>
            <li class="breadcrumb-item"><a href="<?= esc($item['url']) ?>"><?= esc($item['label']) ?></a></li>
        <?php endif; ?>
    <?php endforeach; ?>
</ol>
