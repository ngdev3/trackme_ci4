<?php

namespace App\Libraries;

use Config\Services;
use Dompdf\Dompdf;
use Dompdf\Options;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

/**
 * Small, reusable download builder for CSV / Excel / PDF. Mirrors the writers in
 * the Transactions ExportController so the inventory reports (Tasks 7 & 8) get
 * the same look without duplicating boilerplate in every controller.
 */
class Exporter
{
    /** CSV download (UTF-8 BOM so Excel opens it cleanly). */
    public static function csv(string $name, array $headers, array $rows)
    {
        $out = fopen('php://temp', 'r+');
        fputcsv($out, $headers);
        foreach ($rows as $r) {
            fputcsv($out, $r);
        }
        rewind($out);
        $body = stream_get_contents($out);
        fclose($out);

        return Services::response()
            ->setHeader('Content-Type', 'text/csv; charset=UTF-8')
            ->setHeader('Content-Disposition', 'attachment; filename="' . $name . '.csv"')
            ->setBody("\xEF\xBB\xBF" . $body);
    }

    /** Excel (.xlsx) download with a styled header row. */
    public static function xlsx(string $name, string $sheetTitle, array $headers, array $rows)
    {
        $book  = new Spreadsheet();
        $sheet = $book->getActiveSheet();
        $sheet->setTitle(substr(preg_replace('/[^A-Za-z0-9 ]/', '', $sheetTitle), 0, 31) ?: 'Sheet1');

        $sheet->fromArray($headers, null, 'A1');
        $lastCol = Coordinate::stringFromColumnIndex(max(1, count($headers)));
        $sheet->getStyle("A1:{$lastCol}1")->getFont()->setBold(true)->getColor()->setRGB('FFFFFF');
        $sheet->getStyle("A1:{$lastCol}1")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('2563EB');
        $sheet->getStyle("A1:{$lastCol}1")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $sheet->fromArray($rows, null, 'A2');
        foreach (range(1, max(1, count($headers))) as $c) {
            $sheet->getColumnDimensionByColumn($c)->setAutoSize(true);
        }

        $writer = new Xlsx($book);
        ob_start();
        $writer->save('php://output');
        $body = ob_get_clean();

        return Services::response()
            ->setHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet')
            ->setHeader('Content-Disposition', 'attachment; filename="' . $name . '.xlsx"')
            ->setBody($body);
    }

    /** PDF download from a rendered HTML string. */
    public static function pdf(string $name, string $html, string $orientation = 'portrait')
    {
        $options = new Options();
        $options->set('isRemoteEnabled', false);
        $options->set('defaultFont', 'DejaVu Sans');

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', $orientation);
        $dompdf->render();

        return Services::response()
            ->setHeader('Content-Type', 'application/pdf')
            ->setHeader('Content-Disposition', 'attachment; filename="' . $name . '.pdf"')
            ->setBody($dompdf->output());
    }
}
