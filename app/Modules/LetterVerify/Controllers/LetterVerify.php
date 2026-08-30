<?php

namespace App\Modules\LetterVerify\Controllers;

use App\Controllers\BaseController;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Config\Database;
use Psr\Log\LoggerInterface;

/**
 * Letter Verify — public letter authenticity checker. CI4 port of the CI3
 * `letter_verify` module (which extended CI_Controller). No login by design:
 * the QR on every letter-pad letter points here so any recipient can confirm
 * the letter was genuinely issued. The letter body is never exposed — only
 * issue metadata, and details require the secret token from the QR.
 *
 * URLs (unchanged from CI3):
 *   letter_verify                            -> manual entry form
 *   letter_verify/check/<letter_no>/<token>  -> HTML result (QR target)
 *   letter_verify/api/<letter_no>/<token>    -> JSON result
 */
class LetterVerify extends BaseController
{
    protected $helpers = ['url'];

    public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);
        date_default_timezone_set('Asia/Kolkata');
    }

    public function index()
    {
        return $this->renderPage($this->buildResult('', ''));
    }

    /** HTML result page — what the printed QR opens. */
    public function check($letterNo = '', $token = '')
    {
        return $this->renderPage($this->buildResult($letterNo, $token));
    }

    /** JSON API with the same verification logic. */
    public function api($letterNo = '', $token = '')
    {
        $result = $this->buildResult($letterNo, $token);
        unset($result['show_form']);

        return $this->response->setJSON([
            'status'  => 'success',
            'message' => $result['message'],
            'data'    => $result,
        ]);
    }

    /* ---------------- internals ---------------- */

    /**
     * Verdicts: empty | not_found | invalid | cancelled | valid.
     */
    private function buildResult($letterNo, $token): array
    {
        $letterNo = strtoupper(trim((string) $letterNo));
        $token    = trim((string) $token);

        $result = [
            'verdict'   => 'empty',
            'letter_no' => $letterNo,
            'message'   => 'Enter the letter number and verification code printed on the letter.',
            'letter'    => null,
            'show_form' => true,
        ];

        if ($letterNo === '') {
            return $result;
        }

        $row = $this->lookup($letterNo);

        if (empty($row)) {
            $result['verdict'] = 'not_found';
            $result['message'] = 'No letter with this number was issued from this system.';
            return $result;
        }

        $tokenOk = $token !== '' && ! empty($row->verify_token) && hash_equals($row->verify_token, $token);

        if (! $tokenOk) {
            $result['verdict'] = 'invalid';
            $result['message'] = 'Verification code does not match. This copy cannot be confirmed as genuine.';
            return $result;
        }

        $result['letter'] = [
            'letter_no'   => $row->letter_no,
            'firm'        => $row->firm_name,
            'title'       => $row->title,
            'subject'     => $row->subject,
            'letter_date' => (! empty($row->letter_date) && $row->letter_date !== '0000-00-00') ? date('d M Y', strtotime($row->letter_date)) : null,
            'signed_by'   => trim((string) $row->signature_name) !== '' ? $row->signature_name : 'Authorised Signatory',
            'designation' => $row->signature_designation,
            'page_count'  => isset($row->page_count) && (int) $row->page_count > 0 ? (int) $row->page_count : null,
            'issued_at'   => ! empty($row->created_at) ? date('d M Y h:i A', strtotime($row->created_at)) : null,
            'updated_at'  => ! empty($row->updated_at) ? date('d M Y h:i A', strtotime($row->updated_at)) : null,
        ];

        if ($row->status === 'Delete') {
            $result['verdict'] = 'cancelled';
            $result['message'] = 'This letter was issued from this system but has since been CANCELLED/withdrawn.';
        } else {
            $result['verdict'] = 'valid';
            $result['message'] = 'This letter is GENUINE and was issued from this system.';
        }

        return $result;
    }

    private function lookup($letterNo)
    {
        $db = Database::connect();
        if (! $db->tableExists('letter_pad_documents')) {
            return false;
        }

        $row = $db->table('letter_pad_documents lp')
            ->select('lp.letter_no, lp.verify_token, lp.title, lp.subject, lp.letter_date,
                lp.signature_name, lp.signature_designation, lp.created_at, lp.updated_at, lp.status,
                f.name AS firm_name')
            ->join('aa_template t', 't.template_id = lp.template_id', 'left')
            ->join('firm_name f', 'f.id = t.firm_name_id', 'left')
            ->where('lp.letter_no', $letterNo)
            ->get()
            ->getRow();

        return $row ?: false;
    }

    private function renderPage(array $result): string
    {
        return view('\App\Modules\LetterVerify\Views\check', ['result' => $result]);
    }
}
