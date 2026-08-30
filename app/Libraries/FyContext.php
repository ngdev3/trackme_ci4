<?php

namespace App\Libraries;

use Config\Database;

/**
 * FyContext — the multi-tenant (firm × financial-year × product) context, the
 * CI4 replacement for the CI3 session model:
 *
 *   currentuserinfo()  === session 'userinfo'  (the users row object, no password)
 *   fy()               === session 'fy'        (an aa_template ROW object:
 *                          template_id, FY, product_type, firm_name, status …)
 *
 * The `fy` row is loaded by loadFirmContext() from aa_template keyed by
 * userinfo->default_firm — exactly what CI3 validate_admin_login() did. Values
 * are OBJECTS (stdClass) to match every CI3 call site (`fy()->template_id`,
 * `currentuserinfo()->isSuperAdmin`, …) and the ported permission helper.
 */
class FyContext
{
    /** CI3 currentuserinfo(): the logged-in user row (object) or null. */
    public function userInfo()
    {
        return session()->get('userinfo') ?: null;
    }

    /** CI3 fy(): the active aa_template row (object) or null. */
    public function fyRow()
    {
        return session()->get('fy') ?: null;
    }

    public function isLoggedIn(): bool
    {
        return session()->get('isLogin') === 'yes' && $this->userInfo() !== null;
    }

    /** Firm id (CI3 fy()->template_id, falling back to userinfo->default_firm). */
    public function templateId(): ?int
    {
        $fy = $this->fyRow();
        if ($fy && isset($fy->template_id) && $fy->template_id !== '') {
            return (int) $fy->template_id;
        }
        $u = $this->userInfo();
        return ($u && isset($u->default_firm) && $u->default_firm !== '') ? (int) $u->default_firm : null;
    }

    /** Financial year string, e.g. "2025-2026" (CI3 fy()->FY). */
    public function fyYear(): ?string
    {
        $fy = $this->fyRow();
        return ($fy && isset($fy->FY)) ? (string) $fy->FY : null;
    }

    /** Product type (CI3 fy()->product_type). */
    public function productType()
    {
        $fy = $this->fyRow();
        return ($fy && isset($fy->product_type)) ? $fy->product_type : null;
    }

    /** Super admin? (CI3 erp_is_super_admin(): isSuperAdmin==1). */
    public function isSuperAdmin(): bool
    {
        $u = $this->userInfo();
        return $u && isset($u->isSuperAdmin) && (int) $u->isSuperAdmin === 1;
    }

    public function userId(): ?int
    {
        $u = $this->userInfo();
        return ($u && isset($u->id)) ? (int) $u->id : null;
    }

    /** Cache-scope suffix (CI3 cr_cache_scope): f<tid>_<FY>. */
    public function cacheScope(): string
    {
        if ($this->templateId() !== null && $this->fyYear() !== null) {
            return 'f' . $this->templateId() . '_' . preg_replace('/[^0-9A-Za-z]/', '', (string) $this->fyYear());
        }
        return 'g';
    }

    /**
     * Replicate CI3 validate_admin_login(): load the active aa_template row for
     * the logged-in user's default_firm (joined with firm_name) into session
     * 'fy'. Falls back to the first Active template. Idempotent per request.
     */
    public function loadFirmContext(): void
    {
        static $done = false;
        if ($done) {
            return;
        }
        $done = true;

        if (! $this->isLoggedIn()) {
            return;
        }

        $db = Database::connect();
        $u  = $this->userInfo();
        $row = null;

        if ($u && ! empty($u->default_firm)) {
            $row = $db->table('aa_template atp')
                ->select('atp.*, ftp.name as firm_name')
                ->join('firm_name ftp', 'atp.firm_name_id = ftp.id', 'left')
                ->where('atp.template_id', $u->default_firm)
                ->get()->getRow();
        }

        if (! $row) {
            $row = $db->table('aa_template')->where('status', 'Active')->get()->getRow();
        }

        if ($row) {
            session()->set('fy', $row);
        }
    }
}
