<?php

use App\Models\UserModel;
use Config\Database;
use Config\Services;
use Dompdf\Dompdf;
use Dompdf\Options;

if (! function_exists('send_company_deletion_report')) {
    /**
     * Email the company OWNER a final snapshot of a company immediately BEFORE it
     * is permanently deleted — a summary (entries, total Jama/Naam, net, accounts,
     * date range) plus a per-account breakdown as a PDF attachment, together with
     * a clear notice that the company cannot be recovered.
     *
     * MUST be called BEFORE the rows are purged (it reads the data being removed).
     * Best-effort and self-contained: any failure is logged and swallowed so a
     * mail/DB/PDF hiccup can never block the permanent delete. Shared by the web
     * (CompanyController::forceDeleteCompany) and the mobile API
     * (CompanyApiController::purge) so both send an identical report.
     *
     * @param array $company The (soft-deleted) company row about to be purged.
     * @return bool true when the report was accepted for delivery.
     */
    function send_company_deletion_report(array $company): bool
    {
        try {
            $db  = Database::connect();
            $cid = (int) ($company['id'] ?? 0);
            if ($cid <= 0) {
                return false;
            }

            // Cash-book snapshot (Jama = deposits/in, Naam = expenses/out). Active
            // entries only (exclude anything already in the entry-level Trash).
            $row = $db->table('transactions')
                ->select(
                    "COUNT(*) AS entries,
                     COALESCE(SUM(CASE WHEN type = 'jama' THEN amount ELSE 0 END), 0) AS jama,
                     COALESCE(SUM(CASE WHEN type = 'naam' THEN amount ELSE 0 END), 0) AS naam,
                     COUNT(DISTINCT NULLIF(name, '')) AS parties,
                     MIN(txn_date) AS first_date,
                     MAX(txn_date) AS last_date",
                    false
                )
                ->where('company_id', $cid)
                ->where('deleted_at', null)
                ->get()
                ->getRowArray() ?: [];

            $jama = (float) ($row['jama'] ?? 0);
            $naam = (float) ($row['naam'] ?? 0);

            $stats = [
                'entries'    => (int) ($row['entries'] ?? 0),
                'jama'       => $jama,
                'naam'       => $naam,
                'net'        => $jama - $naam,
                'parties'    => (int) ($row['parties'] ?? 0),
                'first_date' => $row['first_date'] ?? null,
                'last_date'  => $row['last_date'] ?? null,
                'deleted_at' => date('d M Y, H:i'),
            ];

            // Per-account breakdown for the PDF (top 100 parties by activity).
            $parties = $db->table('transactions')
                ->select(
                    "name AS party, COUNT(*) AS entries,
                     COALESCE(SUM(CASE WHEN type = 'jama' THEN amount ELSE 0 END), 0) AS jama,
                     COALESCE(SUM(CASE WHEN type = 'naam' THEN amount ELSE 0 END), 0) AS naam",
                    false
                )
                ->where('company_id', $cid)
                ->where('deleted_at', null)
                ->where('name IS NOT NULL', null, false)
                ->where("name <> ''", null, false)
                ->groupBy('name')
                ->orderBy('entries', 'DESC')
                ->limit(100)
                ->get()
                ->getResultArray() ?: [];

            // Report goes to the company OWNER (only an owner/super-admin can purge;
            // if a super-admin deleted it, the owner still gets their record).
            $owner = (new UserModel())->find((int) ($company['owner_id'] ?? 0));
            $to    = trim((string) ($owner['email'] ?? ''));
            if ($to === '' || ! filter_var($to, FILTER_VALIDATE_EMAIL)) {
                return false;
            }
            $ownerName   = (string) ($owner['name'] ?? '');
            $companyName = (string) ($company['name'] ?? 'your company');

            // Build a printable PDF of the full report (best-effort; the email is
            // still sent without it if PDF generation fails).
            $pdf = null;
            try {
                $pdfHtml = view('emails/company_report_pdf', [
                    'companyName' => $companyName,
                    'ownerName'   => $ownerName,
                    'stats'       => $stats,
                    'parties'     => $parties,
                    'brand'       => Services::mailer()->appName(),
                ]);
                $options = new Options();
                $options->set('isRemoteEnabled', false);
                $options->set('defaultFont', 'DejaVu Sans');
                $dompdf = new Dompdf($options);
                $dompdf->loadHtml($pdfHtml);
                $dompdf->setPaper('A4', 'portrait');
                $dompdf->render();
                $pdf = $dompdf->output();
            } catch (\Throwable $e) {
                log_message('error', 'Company deletion PDF failed: ' . $e->getMessage());
            }

            $safe    = preg_replace('/[^A-Za-z0-9._-]+/', '-', $companyName) ?: 'company';
            $pdfName = $safe . '-final-report.pdf';

            return Services::mailer()->companyDeleted($to, $ownerName, $companyName, $stats, $pdf, $pdfName);
        } catch (\Throwable $e) {
            log_message('error', 'Company deletion report failed: ' . $e->getMessage());
            return false;
        }
    }
}
