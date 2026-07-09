<?php

if (! function_exists('attachable_modules')) {
    /**
     * Project modules a note or reminder can be attached to. Values are stored
     * in `attach_module`; the free-text `attach_ref` holds a record id/label.
     *
     * @return array<string, string>  value => label
     */
    function attachable_modules(): array
    {
        return [
            ''          => '— None —',
            'customers' => 'Customers',
            'invoices'  => 'Invoices',
            'sales'     => 'Sales',
            'purchase'  => 'Purchase',
            'tasks'     => 'Tasks',
            'employees' => 'Employees',
        ];
    }
}

if (! function_exists('priority_badge')) {
    /** Coloured badge for a reminder priority. */
    function priority_badge(string $priority): string
    {
        $map = ['high' => 'text-bg-danger', 'medium' => 'text-bg-warning', 'low' => 'text-bg-secondary'];
        $cls = $map[$priority] ?? 'text-bg-secondary';
        return '<span class="badge ' . $cls . '">' . ucfirst($priority) . '</span>';
    }
}

if (! function_exists('reminder_status_badge')) {
    /** Coloured badge for a reminder's derived status. */
    function reminder_status_badge(string $status): string
    {
        $map = [
            'completed' => ['text-bg-success', 'Completed'],
            'overdue'   => ['text-bg-danger', 'Overdue'],
            'pending'   => ['text-bg-info', 'Pending'],
        ];
        [$cls, $label] = $map[$status] ?? ['text-bg-secondary', ucfirst($status)];
        return '<span class="badge ' . $cls . '">' . $label . '</span>';
    }
}
