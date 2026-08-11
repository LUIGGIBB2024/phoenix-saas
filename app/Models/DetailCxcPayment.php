<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


class DetailCxcPayment extends Model
{
    protected $fillable = [
        'cxc_payment_id',
        'sales_invoice_id',
        'consecutive',
        'document',
        'nit',
        'branch',
        'report_date',
        'concept',
        'invoice',
        'prefix',
        'invoicedcto',
        'quota',
        'payment_amount',
        'calculate',
        'state',
        'state01',
        'state02',
        'state03',
        'customers_id',
        'companies_id',
        'usercreate',
        'userupdate',
    ];
}
