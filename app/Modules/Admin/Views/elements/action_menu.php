<?php
/**
 * Shared modern row-action menu (kebab ⋮) for every DataTables listing.
 *
 * Usage from a module's _action.php (echo with return=true so the nested
 * view is captured into the parent buffer, not appended globally):
 *
 *   $actions = array(
 *     array('label' => 'View',   'href' => base_url('admin/x/view/' . $enc), 'icon' => 'fa-eye',    'color' => 'view', 'target' => '_blank'),
 *     array('label' => 'Edit',   'href' => base_url('admin/x/edit/' . $enc), 'icon' => 'fa-pencil', 'color' => 'edit'),
 *     array('sep'   => true),
 *     array('label' => 'Delete', 'icon' => 'fa-trash', 'danger' => true, 'onclick' => "deleteRecord(" . $id . ",'Delete')"),
 *   );
 *   echo $this->load->view('elements/action_menu', array('actions' => $actions), true);
 *
 * Per-action keys:
 *   label   (string, required for items)   sep    (bool: render a divider)
 *   href    (string, default javascript:;) icon   (fa-* class, default fa-circle-o)
 *   color   (view|edit|perm|imp|on|off|dl|pdf|copy|cancel|print|info) – icon tint
 *   danger  (bool: red styling)            disabled (bool: render inert span)
 *   target  (e.g. _blank)                  onclick (raw JS, single-quoted inside)
 *   xclass  (extra classes for JS hooks)   attr   (raw extra attributes, e.g. data-*)
 *
 * The CSS + crToggleActions() JS live once in application/views/layout.php.
 */
$actions = isset($actions) ? $actions : array();
$actions = array_filter($actions, function ($a) { return !empty($a); });
?>
<div class="cr-act">
    <button type="button" class="cr-act-btn" title="Actions" onclick="crToggleActions(this)"><i class="fa fa-ellipsis-v"></i></button>
    <div class="cr-act-pop">
        <?php foreach ($actions as $a): ?>
            <?php if (!empty($a['sep'])): ?>
                <div class="cr-sep"></div>
            <?php else:
                $icon   = isset($a['icon']) ? $a['icon'] : 'fa-circle-o';
                $color  = (!empty($a['color']) && empty($a['danger'])) ? ' i-' . $a['color'] : '';
                $xclass = !empty($a['xclass']) ? ' ' . $a['xclass'] : '';
                $attr   = isset($a['attr']) ? ' ' . $a['attr'] : '';
                $label  = isset($a['label']) ? $a['label'] : '';
                if (!empty($a['disabled'])):
                    $cls = 'cr-item disabled' . $xclass;
            ?>
                <span class="<?php echo $cls; ?>"<?php echo $attr; ?>><i class="fa <?php echo $icon; ?>"></i> <?php echo $label; ?></span>
            <?php
                else:
                    $cls     = 'cr-item' . (!empty($a['danger']) ? ' danger' : '') . $xclass;
                    $target  = !empty($a['target']) ? ' target="' . $a['target'] . '"' : '';
                    $href    = isset($a['href']) && $a['href'] !== '' ? $a['href'] : 'javascript:void(0);';
                    $onclick = isset($a['onclick']) && $a['onclick'] !== '' ? ' onclick="' . $a['onclick'] . '"' : '';
            ?>
                <a class="<?php echo $cls; ?>" href="<?php echo $href; ?>"<?php echo $target . $onclick . $attr; ?>>
                    <i class="fa <?php echo $icon . $color; ?>"></i> <?php echo $label; ?>
                </a>
            <?php endif; ?>
            <?php endif; ?>
        <?php endforeach; ?>
    </div>
</div>
