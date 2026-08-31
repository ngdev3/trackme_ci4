<?php

namespace App\Libraries;

/**
 * Register_export — CI4 port of libraries/Register_export.
 *
 * Streams a titled register as CSV, Excel (SpreadsheetML XML — no PhpSpreadsheet
 * needed), landscape PDF (dompdf), or a Hindi/Devanagari-capable A4 PDF (mPDF,
 * with a repeating firm header/footer). Each method sends headers + body and
 * exit()s, exactly like the CI3 library.
 */
class Register_export
{
    /** Excel via SpreadsheetML XML (opens in Excel; no PhpSpreadsheet dependency). */
    public function excel(string $title, array $columns, array $rows, string $filename): void
    {
        header('Content-Type: application/vnd.ms-excel; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $filename . '.xls"');
        header('Cache-Control: max-age=0');

        echo '<?xml version="1.0" encoding="UTF-8"?>';
        echo '<?mso-application progid="Excel.Sheet"?>';
        echo '<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet" xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet">';
        echo '<Styles><Style ss:ID="Default" ss:Name="Normal"><Font ss:FontName="Nirmala UI"/></Style><Style ss:ID="Title"><Font ss:FontName="Nirmala UI" ss:Bold="1" ss:Size="15"/></Style>';
        echo '<Style ss:ID="Header"><Font ss:Bold="1"/><Interior ss:Color="#DCE6F2" ss:Pattern="Solid"/></Style>';
        echo '<Style ss:ID="Number"><NumberFormat ss:Format="#,##0.00"/></Style></Styles>';
        echo '<Worksheet ss:Name="Register"><Table>';
        echo '<Row><Cell ss:MergeAcross="' . max(0, count($columns) - 1) . '" ss:StyleID="Title"><Data ss:Type="String">' . $this->xml_escape($title) . '</Data></Cell></Row>';
        echo '<Row><Cell ss:MergeAcross="' . max(0, count($columns) - 1) . '"><Data ss:Type="String">Generated: ' . date('d-m-Y h:i A') . '</Data></Cell></Row><Row/>';
        echo '<Row>';
        foreach ($columns as $label) {
            echo '<Cell ss:StyleID="Header"><Data ss:Type="String">' . $this->xml_escape($label) . '</Data></Cell>';
        }
        echo '</Row>';
        foreach ($rows as $row) {
            echo '<Row>';
            foreach ($columns as $key => $label) {
                $value = $row[$key] ?? '';
                if ($this->is_numeric_export_value($key, $value)) {
                    echo '<Cell ss:StyleID="Number"><Data ss:Type="Number">' . (float) $value . '</Data></Cell>';
                } else {
                    echo '<Cell><Data ss:Type="String">' . $this->xml_escape($value) . '</Data></Cell>';
                }
            }
            echo '</Row>';
        }
        echo '</Table><WorksheetOptions xmlns="urn:schemas-microsoft-com:office:excel"><FreezePanes/><FrozenNoSplit/><SplitHorizontal>4</SplitHorizontal><TopRowBottomPane>4</TopRowBottomPane></WorksheetOptions></Worksheet></Workbook>';
        exit;
    }

    /** UTF-8 CSV (BOM for Excel). */
    public function csv(string $title, array $columns, array $rows, string $filename, bool $with_header = false): void
    {
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $filename . '.csv"');
        header('Cache-Control: max-age=0');

        $output = fopen('php://output', 'w');
        fwrite($output, "\xEF\xBB\xBF");
        if ($with_header) {
            fputcsv($output, [$title]);
            fputcsv($output, ['Generated: ' . date('d-m-Y h:i A')]);
            fputcsv($output, []);
        }
        fputcsv($output, array_values($columns));
        foreach ($rows as $row) {
            $values = [];
            foreach ($columns as $key => $label) {
                $values[] = $row[$key] ?? '';
            }
            fputcsv($output, $values);
        }
        fclose($output);
        exit;
    }

    /** Landscape A4 PDF via dompdf. */
    public function pdf(string $title, array $columns, array $rows, string $filename): void
    {
        $html = '<!doctype html><html><head><meta charset="utf-8"><style>'
            . '@page{margin:22px 18px}body{font-family:DejaVu Sans,sans-serif;color:#172033;font-size:8px}'
            . 'h1{font-size:16px;margin:0 0 3px}.meta{color:#56657a;margin:0 0 12px}'
            . 'table{border-collapse:collapse;width:100%}thead{display:table-header-group}'
            . 'th,td{border:1px solid #b9c5d3;padding:4px;vertical-align:top;word-break:break-word}'
            . 'th{background:#dce6f2;font-weight:bold}tr{page-break-inside:avoid}'
            . '</style></head><body><h1>' . $this->escape($title) . '</h1>'
            . '<p class="meta">Generated: ' . date('d-m-Y h:i A') . ' | Records: ' . count($rows) . '</p><table><thead><tr>';
        foreach ($columns as $label) {
            $html .= '<th>' . $this->escape($label) . '</th>';
        }
        $html .= '</tr></thead><tbody>';
        foreach ($rows as $row) {
            $html .= '<tr>';
            foreach ($columns as $key => $label) {
                $html .= '<td>' . $this->escape($row[$key] ?? '') . '</td>';
            }
            $html .= '</tr>';
        }
        $html .= '</tbody></table></body></html>';

        $dompdf = new \Dompdf\Dompdf(['isRemoteEnabled' => false]);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();
        $dompdf->stream($filename . '.pdf', ['Attachment' => 1]);
        exit;
    }

    /** Hindi/Devanagari A4 PDF via mPDF with a repeating firm header/footer. */
    public function pdf_hindi(string $title, array $columns, array $rows, string $filename, $orientation = null, array $totals = [], array $meta = [], array $header = []): void
    {
        @ini_set('memory_limit', '768M');
        @set_time_limit(180);

        $h_firm     = ($header['firm'] ?? '') !== '' ? (string) $header['firm'] : (string) ($meta['Firm'] ?? '');
        unset($meta['Firm']);
        $h_mill     = trim((string) ($header['mill'] ?? ''));
        $h_template = trim((string) ($header['template'] ?? ''));
        $h_fy       = trim((string) ($header['fy'] ?? ($meta['Financial Year'] ?? '')));
        $h_genby    = trim((string) ($header['generated_by'] ?? ''));
        $h_genon    = date('d-m-Y h:i A');
        if ($h_template !== '' && isset($meta['Template'])) {
            unset($meta['Template']);
        }

        $html = '<!doctype html><html><head><meta charset="utf-8"><style>'
            . 'body{font-family:freeserif;font-size:8.5px;color:#172033}'
            . 'h1{font-size:13px;margin:0 0 7px;color:#33415a;text-align:center;font-weight:normal}'
            . '.meta{margin:0 0 10px;font-size:9px;color:#33415a;background:#eef3fa;border:1px solid #cdd7e3;border-radius:4px;padding:6px 9px;line-height:1.7}'
            . '.meta b{color:#0c315f}table{border-collapse:collapse;width:100%}'
            . 'thead{display:table-header-group}th,td{border:1px solid #b9c5d3;padding:5px 4px;vertical-align:top}'
            . 'th{background:#0c315f;color:#fff;font-weight:bold}tr{page-break-inside:avoid}'
            . 'tfoot td{background:#eef3fa;font-weight:bold}.num{text-align:right}'
            . '.c-dr{color:#c62828;font-weight:bold}.c-cr{color:#1f7a4d;font-weight:bold}</style></head><body>';
        if (empty($meta)) {
            $meta = ['Records' => count($rows)];
        }
        $parts = [];
        foreach ($meta as $label => $value) {
            if ($value === '' || $value === null) {
                continue;
            }
            $parts[] = '<b>' . $this->escape($label) . ':</b> ' . $this->escape($value);
        }
        $html .= (empty($parts) ? '' : '<div class="meta">' . implode('&nbsp;&nbsp;|&nbsp;&nbsp;', $parts) . '</div>') . '<table><thead><tr>';
        foreach ($columns as $label) {
            $html .= '<th>' . $this->escape($label) . '</th>';
        }
        $html .= '</tr></thead><tbody>';
        foreach ($rows as $row) {
            $status_val = (string) ($row['status'] ?? '');
            $html .= '<tr>';
            foreach ($columns as $key => $label) {
                $val     = $row[$key] ?? '';
                $classes = [];
                if ($this->is_numeric_export_value($key, $val)) {
                    $classes[] = 'num';
                }
                if ($key === 'naam') {
                    $classes[] = 'c-dr';
                } elseif ($key === 'jama') {
                    $classes[] = 'c-cr';
                } elseif ($key === 'status' || $key === 'balance') {
                    if (strpos($status_val, 'नाम') !== false) {
                        $classes[] = 'c-dr';
                    } elseif (strpos($status_val, 'जमा') !== false) {
                        $classes[] = 'c-cr';
                    }
                }
                $cls = $classes ? (' class="' . implode(' ', $classes) . '"') : '';
                $html .= '<td' . $cls . '>' . $this->escape($val) . '</td>';
            }
            $html .= '</tr>';
        }
        $html .= '</tbody>';
        if (! empty($totals)) {
            $html .= '<tfoot><tr>';
            foreach ($columns as $key => $label) {
                $html .= '<td>' . $this->escape($totals[$key] ?? '') . '</td>';
            }
            $html .= '</tr></tfoot>';
        }
        $html .= '</table></body></html>';

        $tmp = rtrim(sys_get_temp_dir(), '/\\') . DIRECTORY_SEPARATOR . 'mpdf';
        if (! is_dir($tmp)) {
            @mkdir($tmp, 0777, true);
        }
        $pdf = new \Mpdf\Mpdf([
            'mode'             => 'utf-8',
            'format'           => 'A4',
            'margin_left'      => 10, 'margin_right' => 10,
            'margin_top'       => 26, 'margin_bottom' => 16,
            'margin_header'    => 8, 'margin_footer' => 9,
            'autoScriptToLang' => true,
            'autoLangToFont'   => true,
            'tempDir'          => $tmp,
        ]);
        $pdf->packTableData = true;
        $pdf->SetTitle($title);
        $pdf->SetHTMLHeader($this->report_pdf_header($h_firm, $h_mill, $h_template, $title, $h_fy));
        $pdf->SetHTMLFooter($this->report_pdf_footer($h_genon, $h_genby));
        $pdf->WriteHTML($html);
        $pdf->Output($filename . '.pdf', 'D');
        exit;
    }

    private function report_pdf_header($firm, $mill, $template, $title, $fy): string
    {
        $left = '<span style="font-size:15px;font-weight:bold;color:#0c315f;letter-spacing:.3px">' . $this->escape($firm) . '</span>';
        if ($mill !== '') {
            $left .= '<br /><span style="font-size:9px;color:#33415a">Mill: ' . $this->escape($mill) . '</span>';
        }
        if ($template !== '') {
            $left .= '<br /><span style="font-size:9px;color:#56657a">' . $this->escape($template) . '</span>';
        }

        $right = '<span style="font-size:11px;font-weight:bold;color:#33415a">' . $this->escape($title) . '</span>';
        if ($fy !== '') {
            $right .= '<br /><span style="font-size:9px;color:#56657a">Financial Year: ' . $this->escape($fy) . '</span>';
        }
        $right .= '<br /><span style="font-size:9px;color:#56657a">Page {PAGENO} of {nb}</span>';

        return '<div style="font-family:freeserif;border-bottom:1.5px solid #0c315f;padding-bottom:3px">'
            . '<table width="100%" style="border:0;border-collapse:collapse"><tr>'
            . '<td style="border:0;text-align:left;vertical-align:bottom">' . $left . '</td>'
            . '<td style="border:0;text-align:right;vertical-align:bottom">' . $right . '</td>'
            . '</tr></table></div>';
    }

    private function report_pdf_footer($generated_on, $generated_by): string
    {
        $brand = 'Generated by Trackme ERP' . ($generated_by !== '' ? ' &middot; ' . $this->escape($generated_by) : '');
        return '<div style="font-family:freeserif;border-top:0.7px solid #cdd7e3;padding-top:2px;font-size:8px;color:#56657a">'
            . '<table width="100%" style="border:0;border-collapse:collapse"><tr>'
            . '<td style="border:0;text-align:left">' . $brand . '</td>'
            . '<td style="border:0;text-align:center">Generated: ' . $this->escape($generated_on) . '</td>'
            . '<td style="border:0;text-align:right">Page {PAGENO} of {nb}</td>'
            . '</tr></table></div>';
    }

    private function is_numeric_export_value($key, $value): bool
    {
        $numeric_keys = ['quantity', 'rate', 'amount', 'freight', 'advance', 'taxable_amount', 'tax_amount', 'total_amount', 'cgst_amount', 'sgst_amount', 'igst_amount', 'naam', 'jama', 'balance', 'dr', 'cr'];
        return in_array($key, $numeric_keys, true) && $value !== '' && is_numeric($value);
    }

    private function escape($value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    }

    private function xml_escape($value): string
    {
        $value = preg_replace('/[^\x09\x0A\x0D\x20-\x{D7FF}\x{E000}-\x{FFFD}]/u', '', (string) $value);
        return htmlspecialchars($value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }
}
