<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * Saved UPI QR payees (per company). Backs the mobile app's UPI QR directory,
 * synced via UpiQrApiController.
 */
class UpiQrPayeeModel extends Model
{
    protected $table         = 'upi_qr_payees';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps  = true;
    protected $allowedFields = [
        'company_id', 'user_id', 'label', 'method', 'payee_name', 'upi_id',
        'bank_name', 'branch', 'city', 'account_number', 'ifsc', 'amount', 'note',
    ];
}
