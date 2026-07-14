<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DetailCxpPayment extends Model
{
    /**
     * Los atributos que son asignables masivamente.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'cxp_payment_id',
        'consecutive',
        'document',
        'nit',
        'branch',
        'report_date',
        'concept',
        'invoice',
        'prefix',
        'payment_amount',
        'calculate',
        'state',
        'state01',
        'state02',
        'state03',
        'suppliers_id',
        'companies_id',
        'usercreate',
        'userupdate',
    ];
}
