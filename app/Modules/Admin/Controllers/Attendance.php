<?php

namespace App\Modules\Admin\Controllers;

use App\Controllers\BaseController;
use Config\Database;

/** Attendance — CI4 port, full CRUD (aa_attendance). Gated rbac('attendance'). */
class Attendance extends BaseController
{
    protected $helpers = ['url', 'app', 'form'];

    private function db()
    {
        return Database::connect();
    }

    public function listing()
    {
        return _layout('\App\Modules\Admin\Views\attendance\listing', ['title' => 'Attendance · C R Industries ERP']);
    }

    public function viewAll()
    {
        $post  = $this->request->getPost();
        $start = (int) ($post['start'] ?? 0);
        $b = $this->db()->table('aa_attendance')
            ->where('template_id', fy()->template_id)->where('FY', fy()->FY)
            ->where("COALESCE(status,'') != 'Delete'", null, false)->orderBy('attendance_id', 'desc');
        $total = (clone $b)->countAllResults(false);
        if (! empty($post['length']) && $post['length'] != '-1') {
            $b->limit((int) $post['length'], $start);
        }
        $rows = $b->get()->getResult();
        $data = [];
        $j = $start;
        foreach ($rows as $row) {
            $j++;
            $st = strtolower((string) ($row->attendance_status ?? ''));
            $cls = $st === 'present' ? 'success' : ($st === 'absent' ? 'danger' : 'warning');
            $data[] = [
                $j, esc($row->person_name ?? ''), esc($row->attendance_date ?? ''),
                '<span class="label label-' . $cls . '">' . esc($row->attendance_status ?? '') . '</span>',
                esc($row->check_in ?? ''), esc($row->check_out ?? ''),
                '<button class="btn btn-xs btn-primary at-edit" data-id="' . (int) $row->attendance_id . '"><i class="fa fa-edit"></i></button> '
                . '<button class="btn btn-xs btn-danger at-del" data-id="' . (int) $row->attendance_id . '"><i class="fa fa-trash"></i></button>',
            ];
        }
        return $this->response->setJSON(['draw' => (int) ($post['draw'] ?? 0), 'recordsTotal' => $total, 'recordsFiltered' => $total, 'data' => $data]);
    }

    public function save()
    {
        if (strtoupper($this->request->getMethod()) !== 'POST') {
            return $this->response->setStatusCode(405)->setJSON(['status' => 'error', 'message' => 'POST required.']);
        }
        $id     = (int) $this->request->getPost('id');
        $person = trim((string) $this->request->getPost('person_name'));
        $date   = trim((string) $this->request->getPost('attendance_date'));
        $astat  = $this->request->getPost('attendance_status') ?: 'Present';
        $cin    = trim((string) $this->request->getPost('check_in'));
        $cout   = trim((string) $this->request->getPost('check_out'));

        if ($person === '' || $date === '') {
            return $this->json('error', 'Person name and date are required.');
        }
        $now = date('Y-m-d H:i:s');
        $payload = ['person_name' => $person, 'attendance_date' => $date, 'attendance_status' => $astat, 'check_in' => $cin, 'check_out' => $cout, 'updated_date' => $now];
        if ($id > 0) {
            $this->db()->table('aa_attendance')->where('attendance_id', $id)->update($payload);
            return $this->json('success', 'Attendance updated.', ['id' => $id]);
        }
        $this->db()->table('aa_attendance')->insert(array_merge($payload, [
            'status' => 'Active', 'FY' => fy()->FY, 'template_id' => fy()->template_id,
            'added_by' => (int) (currentuserinfo()->id ?? 0), 'created_date' => $now,
        ]));
        return $this->json('success', 'Attendance added.', ['id' => (int) $this->db()->insertID()]);
    }

    public function delete()
    {
        $id = (int) $this->request->getPost('id');
        $this->db()->table('aa_attendance')->where('attendance_id', $id)->update(['status' => 'Delete']);
        return $this->json('success', 'Attendance deleted.');
    }

    public function row($id = 0)
    {
        $r = $this->db()->table('aa_attendance')->where('attendance_id', (int) $id)->get()->getRow();
        return $r ? $this->response->setJSON(['status' => 'success', 'data' => $r]) : $this->json('error', 'Not found.');
    }

    private function json(string $s, string $m, array $x = [])
    {
        return $this->response->setJSON(array_merge(['status' => $s, 'message' => $m], $x));
    }

    /* ==================================================================== */
    /*  Employee master (aa_employees) — CI3 Attendance::employee_* parity.  */
    /*  Global table (no template_id/FY). Search + kebab action menu.        */
    /* ==================================================================== */

    private const EMP_TABLE = 'aa_employees';

    public function employee_listing()
    {
        return _layout('\App\Modules\Admin\Views\attendance\employee_listing', [
            'title' => 'Track (The Rest Accounting Key) || Employee Listing',
        ]);
    }

    public function employee_add()
    {
        if (strtoupper($this->request->getMethod()) === 'POST') {
            $err = $this->employeeValidate();
            if ($err === '') {
                $this->db()->table(self::EMP_TABLE)->insert([
                    'employee_code' => trim((string) $this->request->getPost('employee_code')),
                    'employee_name' => trim((string) $this->request->getPost('employee_name')),
                    'mobile'        => trim((string) $this->request->getPost('mobile')),
                    'designation'   => trim((string) $this->request->getPost('designation')),
                    'joining_date'  => $this->request->getPost('joining_date') ? correct_date($this->request->getPost('joining_date')) : null,
                    'salary'        => $this->request->getPost('salary') ?: null,
                    'address'       => trim((string) $this->request->getPost('address')),
                    'added_by'      => (int) (currentuserinfo()->id ?? 0),
                    'status'        => $this->request->getPost('status') ?: 'Active',
                    'created_date'  => date('Y-m-d H:i:s'),
                    'updated_date'  => date('Y-m-d'),
                ]);
                $name = trim((string) $this->request->getPost('employee_name'));
                if (function_exists('notify')) {
                    notify('New employee <b>' . esc($name) . '</b> added', base_url('admin/attendance/employee_listing'), ['event' => 'added']);
                }
                if (function_exists('flash_toast')) { flash_toast('Employee "' . $name . '" was added.', 'success', 'Employee added'); }
                return redirect()->to(base_url('admin/attendance/employee_listing'))->with('success', 'Employee added successfully');
            }
            session()->setFlashdata('error', $err);
        }
        return _layout('\App\Modules\Admin\Views\attendance\employee_add', [
            'title'  => 'Track (The Rest Accounting Key) || Add Employee',
            'result' => null,
        ]);
    }

    public function employee_edit($id = null)
    {
        $eid = (int) ID_decode($id);
        $emp = $this->db()->table(self::EMP_TABLE)->where('employee_id', $eid)->get()->getRow();
        if (! $emp) {
            return redirect()->to(base_url('admin/attendance/employee_listing'))->with('error', 'Employee not found.');
        }

        if (strtoupper($this->request->getMethod()) === 'POST') {
            $err = $this->employeeValidate();
            if ($err === '') {
                $this->db()->table(self::EMP_TABLE)->where('employee_id', $eid)->update([
                    'employee_code' => trim((string) $this->request->getPost('employee_code')),
                    'employee_name' => trim((string) $this->request->getPost('employee_name')),
                    'mobile'        => trim((string) $this->request->getPost('mobile')),
                    'designation'   => trim((string) $this->request->getPost('designation')),
                    'joining_date'  => $this->request->getPost('joining_date') ? correct_date($this->request->getPost('joining_date')) : null,
                    'salary'        => $this->request->getPost('salary') ?: null,
                    'address'       => trim((string) $this->request->getPost('address')),
                    'status'        => $this->request->getPost('status') ?: 'Active',
                    'updated_date'  => date('Y-m-d'),
                ]);
                $name = trim((string) $this->request->getPost('employee_name'));
                if (function_exists('notify')) {
                    notify('Employee <b>' . esc($name) . '</b> updated', base_url('admin/attendance/employee_listing'), ['event' => 'updated']);
                }
                if (function_exists('flash_toast')) { flash_toast('Employee "' . $name . '" was updated.', 'success', 'Employee updated'); }
                return redirect()->to(base_url('admin/attendance/employee_listing'))->with('success', 'Employee updated successfully');
            }
            session()->setFlashdata('error', $err);
        }
        return _layout('\App\Modules\Admin\Views\attendance\employee_add', [
            'title'  => 'Track (The Rest Accounting Key) || Edit Employee',
            'result' => $emp,
        ]);
    }

    public function employee_view_all()
    {
        $post   = $this->request->getPost();
        $search = trim((string) ($post['search']['value'] ?? ''));
        $start  = (int) ($post['start'] ?? 0);
        $length = $post['length'] ?? 25;

        $b = $this->db()->table(self::EMP_TABLE)->where('status !=', 'Delete');
        if ($search !== '') {
            $b->like("CONCAT(employee_name,' ',employee_code,' ',mobile,' ',designation)", $search);
        }
        $total = (clone $b)->countAllResults(false);

        $cols = [1 => 'employee_code', 2 => 'employee_name', 3 => 'mobile', 4 => 'designation', 5 => 'joining_date'];
        $oc   = (int) ($post['order'][0]['column'] ?? -1);
        $od   = strtolower((string) ($post['order'][0]['dir'] ?? '')) === 'asc' ? 'asc' : 'desc';
        if (isset($cols[$oc])) { $b->orderBy($cols[$oc], $od); } else { $b->orderBy('employee_id', 'desc'); }
        if ($length != '-1') { $b->limit((int) $length, $start); }

        $rows = $b->get()->getResult();
        $data = [];
        $j = $start;
        foreach ($rows as $item) {
            $j++;
            $row = (array) $item;
            $data[] = [
                $j,
                esc($row['employee_code'] ?? ''),
                esc($row['employee_name'] ?? ''),
                esc($row['mobile'] ?? ''),
                esc($row['designation'] ?? ''),
                ! empty($row['joining_date']) && $row['joining_date'] !== '0000-00-00' ? date('Y-m-d', strtotime($row['joining_date'])) : '',
                esc($row['status'] ?? ''),
                view('\App\Modules\Admin\Views\attendance\_employee_action', ['row' => $row]),
            ];
        }
        return $this->response->setJSON([
            'draw'            => (int) ($post['draw'] ?? 0),
            'recordsTotal'    => $total,
            'recordsFiltered' => $total,
            'data'            => $data,
        ]);
    }

    public function employee_delete($id = null)
    {
        $eid = (int) ID_decode($id);
        $emp = $this->db()->table(self::EMP_TABLE)->where('employee_id', $eid)->get()->getRow();
        $ename = $emp->employee_name ?? 'Employee';
        $this->db()->table(self::EMP_TABLE)->where('employee_id', $eid)->update(['status' => 'Delete']);
        if (function_exists('notify')) {
            notify('Employee <b>' . esc($ename) . '</b> deleted', base_url('admin/attendance/employee_listing'), ['event' => 'deleted']);
        }
        if (function_exists('flash_toast')) { flash_toast('Employee "' . $ename . '" was deleted.', 'warning', 'Employee deleted'); }
        return redirect()->to(base_url('admin/attendance/employee_listing'))->with('success', 'Employee deleted successfully');
    }

    public function employee_toggle_status($id = null)
    {
        $eid = (int) ID_decode($id);
        $emp = $this->db()->table(self::EMP_TABLE)->where('employee_id', $eid)->get()->getRow();
        if (! $emp || $emp->status === 'Delete') {
            return redirect()->to(base_url('admin/attendance/employee_listing'))->with('error', 'Employee status not changed');
        }
        $next = ($emp->status === 'Active') ? 'Inactive' : 'Active';
        $this->db()->table(self::EMP_TABLE)->where('employee_id', $eid)->update(['status' => $next, 'updated_date' => date('Y-m-d')]);
        return redirect()->to(base_url('admin/attendance/employee_listing'))->with('success', 'Employee marked ' . $next);
    }

    private function employeeValidate(): string
    {
        if (trim((string) $this->request->getPost('employee_name')) === '') { return 'Employee Name is required.'; }
        if (trim((string) $this->request->getPost('status')) === '') { return 'Status is required.'; }
        return '';
    }

    /* ======================= Attendance Report (CI3 parity) ======================= */

    public function report()
    {
        $filters = $this->reportFilters();
        $rows    = $this->reportData($filters);
        return _layout('\App\Modules\Admin\Views\attendance\report', [
            'title'     => 'Track (The Rest Accounting Key) || Attendance Report',
            'employees' => $this->activeEmployees(),
            'rows'      => $rows,
            'summary'   => $this->reportSummary($rows),
            'filters'   => $filters,
        ]);
    }

    public function report_csv()
    {
        $filters = $this->reportFilters();
        $rows    = $this->reportData($filters);
        $summary = $this->reportSummary($rows);

        $out = fopen('php://temp', 'r+');
        fwrite($out, "\xEF\xBB\xBF"); // UTF-8 BOM for Excel
        fputcsv($out, ['Attendance Report']);
        fputcsv($out, ['Period', ucfirst($filters['period']), 'From', $filters['from_date'], 'To', $filters['to_date']]);
        fputcsv($out, ['Total', $summary['total'], 'Present', $summary['present'], 'Absent', $summary['absent'], 'Half Day', $summary['half_day'], 'Leave', $summary['leave']]);
        fputcsv($out, []);
        fputcsv($out, ['S.No.', 'Date', 'Employee Code', 'Employee Name', 'Designation', 'Status', 'Check In', 'Check Out', 'Remark']);
        $i = 1;
        foreach ($rows as $row) {
            fputcsv($out, [
                $i++, date('Y-m-d', strtotime($row->attendance_date)),
                $row->employee_code ?? '', ! empty($row->employee_name) ? $row->employee_name : ($row->person_name ?? ''),
                $row->designation ?? '', $row->attendance_status ?? '', $row->check_in ?? '', $row->check_out ?? '', $row->remark ?? '',
            ]);
        }
        rewind($out);
        $csv = stream_get_contents($out);
        fclose($out);

        return $this->response
            ->setContentType('text/csv; charset=utf-8')
            ->setHeader('Content-Disposition', 'attachment; filename="attendance_report_' . date('Ymd_His') . '.csv"')
            ->setBody($csv);
    }

    public function report_pdf()
    {
        $data = $this->reportExportData();
        $html = view('\App\Modules\Admin\Views\attendance\report_export', $data);

        $dompdf = new \Dompdf\Dompdf();
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();

        return $this->response
            ->setContentType('application/pdf')
            ->setHeader('Content-Disposition', 'attachment; filename="attendance_report_' . date('Ymd_His') . '.pdf"')
            ->setBody($dompdf->output());
    }

    public function report_print()
    {
        $data = $this->reportExportData();
        $data['auto_print'] = true;
        return view('\App\Modules\Admin\Views\attendance\report_export', $data);
    }

    private function reportExportData(): array
    {
        $filters = $this->reportFilters();
        $rows    = $this->reportData($filters);
        return [
            'rows'       => $rows,
            'summary'    => $this->reportSummary($rows),
            'filters'    => $filters,
            'auto_print' => false,
        ];
    }

    /** Resolve report filters from GET (period/date range). Dates stored Y-m-d. */
    private function reportFilters(): array
    {
        $period    = $this->request->getGet('period') ?: 'monthly';
        $from_date = $this->request->getGet('from_date');
        $to_date   = $this->request->getGet('to_date');

        if (empty($from_date) || empty($to_date)) {
            $range     = $this->reportRange($period);
            $from_date = $range['from_date'];
            $to_date   = $range['to_date'];
        } else {
            $from_date = correct_date($from_date);
            $to_date   = correct_date($to_date);
        }

        return [
            'employee_id' => $this->request->getGet('employee_id'),
            'from_date'   => $from_date,
            'to_date'     => $to_date,
            'period'      => $period,
        ];
    }

    private function reportRange(string $period): array
    {
        if ($period === 'weekly') { return ['from_date' => date('Y-m-d', strtotime('monday this week')), 'to_date' => date('Y-m-d', strtotime('sunday this week'))]; }
        if ($period === 'yearly') { return ['from_date' => date('Y-01-01'), 'to_date' => date('Y-12-31')]; }
        if ($period === 'today')  { return ['from_date' => date('Y-m-d'), 'to_date' => date('Y-m-d')]; }
        return ['from_date' => date('Y-m-01'), 'to_date' => date('Y-m-t')];
    }

    /** Attendance rows joined with employee, scoped to current firm + FY. */
    private function reportData(array $filters): array
    {
        $b = $this->db()->table('aa_attendance')
            ->select('aa_attendance.*, aa_employees.employee_code, aa_employees.employee_name, aa_employees.mobile, aa_employees.designation')
            ->join('aa_employees', 'aa_employees.employee_id = aa_attendance.employee_id', 'left')
            ->where('aa_attendance.status !=', 'Delete')
            ->where('aa_attendance.template_id', fy()->template_id)
            ->where('aa_attendance.FY', fy()->FY);

        if (! empty($filters['employee_id'])) { $b->where('aa_attendance.employee_id', $filters['employee_id']); }
        if (! empty($filters['from_date']))   { $b->where('aa_attendance.attendance_date >=', $filters['from_date']); }
        if (! empty($filters['to_date']))     { $b->where('aa_attendance.attendance_date <=', $filters['to_date']); }

        return $b->orderBy('aa_attendance.attendance_date', 'desc')->get()->getResult();
    }

    private function reportSummary(array $rows): array
    {
        $s = ['total' => 0, 'present' => 0, 'absent' => 0, 'half_day' => 0, 'leave' => 0];
        foreach ($rows as $row) {
            $s['total']++;
            switch ($row->attendance_status ?? '') {
                case 'Present':  $s['present']++; break;
                case 'Absent':   $s['absent']++; break;
                case 'Half Day': $s['half_day']++; break;
                case 'Leave':    $s['leave']++; break;
            }
        }
        return $s;
    }

    private function activeEmployees(): array
    {
        return $this->db()->table('aa_employees')
            ->where('status', 'Active')->orderBy('employee_name', 'asc')
            ->get()->getResult();
    }
}
