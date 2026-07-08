<?php

namespace Modules\Transactions\Controllers;

use App\Controllers\BaseController;
use App\Models\TransactionModel;
use Config\Services;
use Dompdf\Dompdf;
use Dompdf\Options;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

/**
 * CSV / Excel / PDF exports for the Jama-Naam ledger and the Rokad Parcha
 * report. Honours the same filters/period as the on-screen views.
 */
class ExportController extends BaseController
{
    protected $helpers = ['url', 'form', 'auth', 'menu', 'ui', 'text', 'settings', 'company'];

    protected TransactionModel $txns;

    public function __construct()
    {
        $this->txns = new TransactionModel();
    }

    private function scope(): ?int
    {
        return Services::acl()->isSuperAdmin() ? null : (int) company_id();
    }

    private function filters(): array
    {
        return [
            'q'      => trim((string) $this->request->getGet('q')),
            'from'   => (string) $this->request->getGet('from'),
            'to'     => (string) $this->request->getGet('to'),
            'status' => (string) $this->request->getGet('status'),
            'type'   => (string) $this->request->getGet('type'),
            'mode'   => (string) $this->request->getGet('mode'),
        ];
    }

    // =================================================================
    // Ledger export
    // =================================================================
    public function ledger(string $format = 'csv')
    {
        $rows = $this->txns->allFiltered($this->scope(), $this->filters());

        $headers = ['Txn No', 'Date', 'Party', 'Type', 'Mode', 'Amount', 'Status', 'Remarks'];
        $matrix  = array_map(static fn ($r) => [
            $r['txn_no'],
            date('d-m-Y', strtotime($r['txn_date'])),
            $r['name'],
            ucfirst($r['type']),
            ucfirst((string) $r['payment_mode']),
            number_format((float) $r['amount'], 2, '.', ''),
            ucfirst($r['status']),
            $r['notes'],
        ], $rows);

        $name = 'transactions_' . date('Ymd_His');

        return match ($format) {
            'xlsx' => $this->xlsx($name, 'Transactions', $headers, $matrix),
            'pdf'  => $this->pdf($name, view('Modules\Transactions\Views\export_ledger_pdf', [
                'rows'    => $rows,
                'summary' => $this->txns->summary($this->scope(), $this->filters()),
                'firm'    => function_exists('current_company') ? current_company() : null,
            ])),
            default => $this->csv($name, $headers, $matrix),
        };
    }

    // =================================================================
    // Rokad Parcha report export
    // =================================================================
    public function report(string $format = 'csv')
    {
        $rc   = new ReportController();
        $data = $rc->build([
            'period'  => (string) $this->request->getGet('period'),
            'date'    => (string) $this->request->getGet('date'),
            'month'   => (string) $this->request->getGet('month'),
            'year'    => (string) $this->request->getGet('year'),
            'quarter' => (string) $this->request->getGet('quarter'),
            'fy'      => (string) $this->request->getGet('fy'),
            'from'    => (string) $this->request->getGet('from'),
            'to'      => (string) $this->request->getGet('to'),
        ]);

        $headers = ['#', 'Date', 'Txn No', 'Party', 'Mode', 'Jama', 'Naam', 'Balance'];
        $matrix  = [];
        $matrix[] = ['', '', '', 'Opening Balance', '', '', '', number_format($data['opening'], 2, '.', '')];
        $i = 1;
        foreach ($data['rows'] as $r) {
            $matrix[] = [
                $i++,
                date('d-m-Y', strtotime($r['txn_date'])),
                $r['txn_no'],
                $r['name'],
                ucfirst((string) $r['payment_mode']),
                $r['type'] === 'jama' ? number_format((float) $r['amount'], 2, '.', '') : '',
                $r['type'] === 'naam' ? number_format((float) $r['amount'], 2, '.', '') : '',
                number_format((float) $r['balance'], 2, '.', ''),
            ];
        }
        $matrix[] = ['', '', '', 'Closing Balance', '', number_format($data['totalJama'], 2, '.', ''), number_format($data['totalNaam'], 2, '.', ''), number_format($data['closing'], 2, '.', '')];

        $name = 'rokadh_parcha_' . date('Ymd_His');

        return match ($format) {
            'xlsx' => $this->xlsx($name, 'Rokadh Parcha', $headers, $matrix),
            'pdf'  => $this->pdf($name, view('Modules\Transactions\Views\report_print', $data + [
                'firm'    => function_exists('current_company') ? current_company() : null,
                'noPrint' => true,
            ])),
            default => $this->csv($name, $headers, $matrix),
        };
    }

    // =================================================================
    // Writers
    // =================================================================
    private function csv(string $name, array $headers, array $rows)
    {
        $out = fopen('php://temp', 'r+');
        fputcsv($out, $headers);
        foreach ($rows as $r) {
            fputcsv($out, $r);
        }
        rewind($out);
        $body = stream_get_contents($out);
        fclose($out);

        return $this->response
            ->setHeader('Content-Type', 'text/csv; charset=UTF-8')
            ->setHeader('Content-Disposition', 'attachment; filename="' . $name . '.csv"')
            ->setBody("\xEF\xBB\xBF" . $body); // UTF-8 BOM for Excel
    }

    private function xlsx(string $name, string $sheetTitle, array $headers, array $rows)
    {
        $book = new Spreadsheet();
        $sheet = $book->getActiveSheet();
        $sheet->setTitle(substr($sheetTitle, 0, 31));

        // Header row.
        $sheet->fromArray($headers, null, 'A1');
        $lastCol = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(count($headers));
        $sheet->getStyle("A1:{$lastCol}1")->getFont()->setBold(true)->getColor()->setRGB('FFFFFF');
        $sheet->getStyle("A1:{$lastCol}1")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('2563EB');
        $sheet->getStyle("A1:{$lastCol}1")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // Data.
        $sheet->fromArray($rows, null, 'A2');

        foreach (range(1, count($headers)) as $c) {
            $sheet->getColumnDimensionByColumn($c)->setAutoSize(true);
        }

        $writer = new Xlsx($book);
        ob_start();
        $writer->save('php://output');
        $body = ob_get_clean();

        return $this->response
            ->setHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet')
            ->setHeader('Content-Disposition', 'attachment; filename="' . $name . '.xlsx"')
            ->setBody($body);
    }

    private function pdf(string $name, string $html)
    {
        $options = new Options();
        $options->set('isRemoteEnabled', false);
        $options->set('defaultFont', 'DejaVu Sans');

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        return $this->response
            ->setHeader('Content-Type', 'application/pdf')
            ->setHeader('Content-Disposition', 'attachment; filename="' . $name . '.pdf"')
            ->setBody($dompdf->output());
    }
}
