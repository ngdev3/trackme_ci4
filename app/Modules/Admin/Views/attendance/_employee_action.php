<?php
$enc      = ID_encode($row['employee_id']);
$isActive = ($row['status'] == 'Active');
$actions = array(
    array('label' => $isActive ? 'Mark inactive' : 'Mark active', 'href' => base_url('admin/attendance/employee_toggle_status/' . $enc),
        'icon' => $isActive ? 'fa-toggle-on' : 'fa-toggle-off', 'color' => $isActive ? 'off' : 'on'),
    array('label' => 'Edit employee', 'href' => base_url('admin/attendance/employee_edit/' . $enc), 'icon' => 'fa-edit', 'color' => 'edit'),
    array('sep' => true),
    array('label' => 'Delete employee', 'href' => base_url('admin/attendance/employee_delete/' . $enc), 'icon' => 'fa-trash', 'danger' => true,
        'onclick' => "return crConfirmNav(this, 'Delete this employee?');"),
);
echo view('\App\Modules\Admin\Views\elements\action_menu', array('actions' => $actions));
