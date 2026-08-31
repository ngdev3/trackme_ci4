<?php

namespace App\Modules\Admin\Controllers;

use App\Controllers\BaseController;
use App\Modules\Admin\Models\LetterPadModel;
use Dompdf\Dompdf;

/**
 * Letter_pad — CI4 port of admin/Letter_pad (firm-letterhead letter generator).
 * Full flow: list, create/edit (rich content), render A4 PDF (dompdf) on the
 * firm letterhead with a unique letter number + QR verify link (letter_verify),
 * download/print, soft delete. Hindi mPDF / monogram-GD / watermark versioning
 * are follow-ups; the create→PDF→verify flow is complete. rbac('letter_pad').
 */
class Letter_pad extends BaseController
{
    protected $helpers = ['url', 'app', 'form'];

    public function index()
    {
        return $this->listing();
    }

    public function listing()
    {
        $model = new LetterPadModel();
        $model->ensureTable();
        return _layout('\App\Modules\Admin\Views\letter_pad\listing', [
            'title' => 'Letter Pad · C R Industries ERP',
            'firms' => $model->getFirmTemplates(),
        ]);
    }

    public function listing_data()
    {
        $model = new LetterPadModel();
        $rows  = $model->getData();
        $data = [];
        $i = (int) ($this->request->getPost('start') ?? 0);
        foreach ($rows as $r) {
            $i++;
            $enc = ID_encode((int) $r->id);
            $data[] = [
                $i,
                esc($r->letter_no ?? '—'),
                '<strong>' . esc($r->title ?? '') . '</strong><br><small>' . esc($r->subject ?? '') . '</small>',
                esc($r->firm_name ?? ''),
                ! empty($r->letter_date) ? date('d M Y', strtotime($r->letter_date)) : '-',
                esc($r->created_by_name ?? ''),
                '<div class="text-nowrap">'
                    . '<a class="btn btn-xs btn-default" target="_blank" href="' . base_url('admin/letter_pad/pdf/' . $enc) . '"><i class="fa fa-file-pdf-o"></i> PDF</a> '
                    . '<a class="btn btn-xs btn-primary" href="' . base_url('admin/letter_pad/edit/' . $enc) . '"><i class="fa fa-edit"></i></a> '
                    . '<button class="btn btn-xs btn-danger lp-del" data-id="' . esc($enc, 'attr') . '"><i class="fa fa-trash"></i></button></div>',
            ];
        }
        return $this->response->setJSON(['draw' => (int) $this->request->getPost('draw'), 'recordsTotal' => $model->countData(), 'recordsFiltered' => $model->countData(), 'data' => $data]);
    }

    public function add()
    {
        return $this->form(null);
    }

    public function edit($id = null)
    {
        return $this->form((int) ID_decode($id));
    }

    private function form(?int $id)
    {
        $model = new LetterPadModel();
        $model->ensureTable();
        $letter = $id ? $model->view($id) : null;
        if ($id && ! $letter) {
            return redirect()->to(base_url('admin/letter_pad/listing'))->with('error', 'Letter not found.');
        }

        if (strtoupper($this->request->getMethod()) === 'POST') {
            $err = $this->validateLetter();
            if ($err === '') {
                $data = [
                    'template_id' => (int) $this->request->getPost('template_id'),
                    'title' => trim((string) $this->request->getPost('title')),
                    'subject' => trim((string) $this->request->getPost('subject')),
                    'letter_date' => $this->request->getPost('letter_date') ?: date('Y-m-d'),
                    'recipient' => trim((string) $this->request->getPost('recipient')),
                    'content' => $this->sanitize((string) $this->request->getPost('content')),
                    'signature_name' => trim((string) $this->request->getPost('signature_name')),
                    'signature_designation' => trim((string) $this->request->getPost('signature_designation')),
                    'updated_at' => date('Y-m-d H:i:s'),
                ];
                if ($id) {
                    $model->edit($id, $data);
                    $msg = 'Letter updated.';
                } else {
                    $data['FY'] = fy()->FY;
                    $data['letter_no'] = $this->generateLetterNo($model);
                    $data['verify_token'] = bin2hex(random_bytes(16));
                    $data['created_by'] = (int) (currentuserinfo()->id ?? 0);
                    $data['created_at'] = date('Y-m-d H:i:s');
                    $model->add($data);
                    $msg = 'Letter created.';
                }
                return redirect()->to(base_url('admin/letter_pad/listing'))->with('success', $msg);
            }
            session()->setFlashdata('error', $err);
        }

        return _layout('\App\Modules\Admin\Views\letter_pad\form', [
            'title'  => $id ? 'Edit Letter' : 'Create Letter',
            'row'    => $letter,
            'firm_templates' => $model->getFirmTemplates(),
        ]);
    }

    /** Stream the A4 letterhead PDF (dompdf) with letter number + QR verify. */
    public function pdf($id = null)
    {
        return $this->streamPdf((int) ID_decode($id), false);
    }

    public function download($id = null)
    {
        return $this->streamPdf((int) ID_decode($id), true);
    }

    private function streamPdf(int $id, bool $attachment)
    {
        $model  = new LetterPadModel();
        $letter = $model->view($id);
        if (! $letter) {
            return redirect()->to(base_url('admin/letter_pad/listing'))->with('error', 'Letter not found.');
        }
        $firm = $model->getTemplateDetail((int) $letter->template_id);
        if (! $firm) {
            return redirect()->to(base_url('admin/letter_pad/listing'))->with('error', 'Firm template no longer exists.');
        }

        $html = view('\App\Modules\Admin\Views\letter_pad\pdf', [
            'firm'   => $firm,
            'letter' => $letter,
            'logo'   => $this->firmLogo((int) $firm->firm_id),
            'qr'     => $this->verifyQr($letter),
            'is_pdf' => false,
        ]);

        $dompdf = new Dompdf(['isRemoteEnabled' => false, 'isHtml5ParserEnabled' => true]);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
        return $this->response
            ->setContentType('application/pdf')
            ->setBody($dompdf->output());
    }

    public function delete()
    {
        $id = (int) ID_decode($this->request->getPost('id'));
        $ok = (new LetterPadModel())->softDelete($id);
        return $this->response->setJSON(['status' => $ok ? 'success' : 'error']);
    }

    /* ---- helpers ---- */
    private function validateLetter(): string
    {
        if ((int) $this->request->getPost('template_id') <= 0) { return 'Select a firm letterhead.'; }
        if (trim((string) $this->request->getPost('title')) === '') { return 'Title is required.'; }
        if (trim((string) $this->request->getPost('content')) === '') { return 'Letter content is required.'; }
        return '';
    }

    private function generateLetterNo(LetterPadModel $model): string
    {
        $chars = '23456789ABCDEFGHJKLMNPQRSTUVWXYZ';
        do {
            $rand = '';
            for ($i = 0; $i < 6; $i++) { $rand .= $chars[random_int(0, strlen($chars) - 1)]; }
            $no = 'LP-' . date('ymd') . '-' . $rand;
        } while ($model->letterNoExists($no));
        return $no;
    }

    private function verifyUrl($letter): string
    {
        if (empty($letter->letter_no) || empty($letter->verify_token)) { return ''; }
        return base_url('letter_verify/check/' . $letter->letter_no . '/' . $letter->verify_token);
    }

    /** QR PNG data-URI of the verify URL, via the bundled TCPDF 2D barcode + GD. */
    private function verifyQr($letter): string
    {
        $url = $this->verifyUrl($letter);
        if ($url === '') { return ''; }
        $lib = APPPATH . 'ThirdParty/tecnickcom/tcpdf/tcpdf_barcodes_2d.php';
        if (! is_file($lib) || ! function_exists('imagecreatetruecolor')) { return ''; }
        require_once $lib;
        try {
            $qr  = new \TCPDF2DBarcode($url, 'QRCODE,M');
            $arr = $qr->getBarcodeArray();
            if (empty($arr['bcode']) || empty($arr['num_cols'])) { return ''; }
            $scale = 6; $quiet = 4 * $scale;
            $w = (int) $arr['num_cols'] * $scale + 2 * $quiet;
            $h = (int) $arr['num_rows'] * $scale + 2 * $quiet;
            $im = imagecreatetruecolor($w, $h);
            $white = imagecolorallocate($im, 255, 255, 255);
            $ink = imagecolorallocate($im, 27, 42, 65);
            imagefilledrectangle($im, 0, 0, $w - 1, $h - 1, $white);
            foreach ($arr['bcode'] as $r => $row) {
                foreach ($row as $c => $on) {
                    if ($on) {
                        $x = $quiet + $c * $scale; $y = $quiet + $r * $scale;
                        imagefilledrectangle($im, $x, $y, $x + $scale - 1, $y + $scale - 1, $ink);
                    }
                }
            }
            ob_start(); imagepng($im); $png = ob_get_clean(); imagedestroy($im);
            return $png ? 'data:image/png;base64,' . base64_encode($png) : '';
        } catch (\Throwable $e) {
            return '';
        }
    }

    private function firmLogo(int $firmId): string
    {
        foreach (['png', 'jpg', 'jpeg'] as $ext) {
            $p = FCPATH . 'uploads/firm_logo/' . $firmId . '.' . $ext;
            if (is_file($p)) {
                return 'data:image/' . ($ext === 'jpg' ? 'jpeg' : $ext) . ';base64,' . base64_encode(file_get_contents($p));
            }
        }
        return '';
    }

    /** Whitelist-sanitise the rich-text body (block dangerous tags/attributes). */
    private function sanitize(string $html): string
    {
        $html = preg_replace('#<script\b[^>]*>.*?</script>#is', '', $html);
        $html = preg_replace('#\son\w+\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)#i', '', $html);
        $html = preg_replace('#(href|src)\s*=\s*("|\')\s*javascript:[^"\']*("|\')#i', '$1=$2#$3', $html);
        $allowed = '<p><br><b><strong><i><em><u><ul><ol><li><span><div><h1><h2><h3><h4><h5><h6><table><thead><tbody><tr><td><th><a><img><blockquote><hr><sub><sup><small>';
        return strip_tags($html, $allowed);
    }
}
