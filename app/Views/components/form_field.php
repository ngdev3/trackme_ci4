<?php
/**
 * Reusable form field component (Bootstrap 5.3).
 *
 * @var string      $name
 * @var string      $label
 * @var string      $type      text|email|password|number|select|textarea|checkbox|file
 * @var mixed       $value
 * @var array       $options   for select: [value => label]
 * @var bool        $required
 * @var string      $help
 * @var array       $errors    validation errors keyed by field
 * @var string      $icon
 * @var array       $attrs     extra html attributes [k => v]
 */
$name     = $name     ?? '';
$label    = $label    ?? ucfirst(str_replace('_', ' ', $name));
$type     = $type     ?? 'text';
$value    = $value    ?? '';
$options  = $options  ?? [];
$required = $required ?? false;
$help     = $help     ?? '';
$errors   = $errors   ?? [];
$icon     = $icon     ?? '';
$attrs    = $attrs    ?? [];
$err      = $errors[$name] ?? '';
$invalid  = $err ? ' is-invalid' : '';

$attrStr = '';
foreach ($attrs as $k => $v) {
    $attrStr .= ' ' . $k . '="' . esc($v, 'attr') . '"';
}
?>
<div class="mb-3">
    <?php if ($type !== 'checkbox'): ?>
        <label for="f_<?= esc($name) ?>" class="form-label">
            <?= esc($label) ?><?php if ($required): ?> <span class="text-danger">*</span><?php endif; ?>
        </label>
    <?php endif; ?>

    <?php if ($type === 'select'): ?>
        <select id="f_<?= esc($name) ?>" name="<?= esc($name) ?>" class="form-select<?= $invalid ?>"<?= $attrStr ?>>
            <?php foreach ($options as $val => $text): ?>
                <option value="<?= esc($val, 'attr') ?>" <?= (string) $value === (string) $val ? 'selected' : '' ?>>
                    <?= esc($text) ?>
                </option>
            <?php endforeach; ?>
        </select>

    <?php elseif ($type === 'textarea'): ?>
        <textarea id="f_<?= esc($name) ?>" name="<?= esc($name) ?>" class="form-control<?= $invalid ?>" rows="3"<?= $attrStr ?>><?= esc($value) ?></textarea>

    <?php elseif ($type === 'checkbox'): ?>
        <div class="form-check form-switch">
            <input type="hidden" name="<?= esc($name) ?>" value="0">
            <input type="checkbox" id="f_<?= esc($name) ?>" name="<?= esc($name) ?>" value="1"
                   class="form-check-input<?= $invalid ?>" <?= (int) $value === 1 ? 'checked' : '' ?><?= $attrStr ?>>
            <label class="form-check-label" for="f_<?= esc($name) ?>"><?= esc($label) ?></label>
        </div>

    <?php elseif ($type === 'file'): ?>
        <input type="file" id="f_<?= esc($name) ?>" name="<?= esc($name) ?>" class="form-control<?= $invalid ?>"<?= $attrStr ?>>

    <?php else: ?>
        <?php if ($icon): ?><div class="input-group"><span class="input-group-text"><i class="<?= esc($icon) ?>"></i></span><?php endif; ?>
        <input type="<?= esc($type) ?>" id="f_<?= esc($name) ?>" name="<?= esc($name) ?>"
               value="<?= esc($value, 'attr') ?>" class="form-control<?= $invalid ?>"<?= $attrStr ?>>
        <?php if ($icon): ?></div><?php endif; ?>
    <?php endif; ?>

    <?php if ($err): ?><div class="invalid-feedback d-block"><?= esc($err) ?></div><?php endif; ?>
    <?php if ($help): ?><div class="form-text"><?= esc($help) ?></div><?php endif; ?>
</div>
