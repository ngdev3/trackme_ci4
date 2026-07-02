<?php

/**
 * Small presentation helpers for reusable AdminLTE / Bootstrap 5 components.
 */

if (! function_exists('status_badge')) {
    /**
     * Render an Active/Inactive badge from a 0/1 status.
     */
    function status_badge($status): string
    {
        $active = (int) $status === 1;
        $class  = $active ? 'text-bg-success' : 'text-bg-secondary';
        $label  = $active ? 'Active' : 'Inactive';
        return '<span class="badge ' . $class . '">' . $label . '</span>';
    }
}

if (! function_exists('bool_badge')) {
    function bool_badge($value, string $yes = 'Yes', string $no = 'No'): string
    {
        return (int) $value === 1
            ? '<span class="badge text-bg-primary">' . esc($yes) . '</span>'
            : '<span class="badge text-bg-light border">' . esc($no) . '</span>';
    }
}

if (! function_exists('action_buttons')) {
    /**
     * Standard edit / delete / status-toggle button group for a table row.
     *
     * @param string $moduleCode used for permission gating
     * @param string $baseUrl    e.g. "users"
     * @param int    $id         row id
     */
    function action_buttons(string $moduleCode, string $baseUrl, int $id, array $opts = []): string
    {
        $html = '<div class="btn-group btn-group-sm" role="group">';

        if (can($moduleCode, 'edit') && ($opts['edit'] ?? true)) {
            $html .= '<a href="' . site_url("{$baseUrl}/edit/{$id}") . '" class="btn btn-outline-primary" title="Edit"><i class="bi bi-pencil-square"></i></a>';
        }
        if (can($moduleCode, 'delete') && ($opts['delete'] ?? true)) {
            $html .= '<button type="button" class="btn btn-outline-danger btn-delete" '
                . 'data-url="' . site_url("{$baseUrl}/delete/{$id}") . '" title="Delete"><i class="bi bi-trash"></i></button>';
        }
        $html .= '</div>';
        return $html;
    }
}

if (! function_exists('flash_alerts')) {
    /**
     * Render queued session flash messages as Bootstrap alerts.
     * (Also mirrored into JS toasts by the layout.)
     */
    function flash_alerts(): string
    {
        $session = session();
        $out     = '';
        $map     = ['success' => 'success', 'error' => 'danger', 'warning' => 'warning', 'info' => 'info'];
        foreach ($map as $key => $class) {
            if ($msg = $session->getFlashdata($key)) {
                $out .= '<div class="alert alert-' . $class . ' alert-dismissible fade show" role="alert">'
                    . esc($msg)
                    . '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>';
            }
        }
        return $out;
    }
}

if (! function_exists('old_value')) {
    /**
     * Repopulate a form field: old input, else the model value, else default.
     */
    function old_value(string $field, $row = null, $default = '')
    {
        $old = old($field);
        if ($old !== null) {
            return $old;
        }
        if (is_array($row) && array_key_exists($field, $row)) {
            return $row[$field];
        }
        return $default;
    }
}
