<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CxcPayment extends Model
{
    protected $fillable = [
        'nit',
        'branch',
        'lapse',
        'report_date',
        'consecutive',
        'document',
        'customer_name',
        'value_cxc',
        'customer_balances',
        'observations',
        'check_number',
        'payment_type',
        'state',
        'state01',
        'state02',
        'state03',
        'proyect',
        'sproyect',
        'center',
        'activity',
        'customers_id',
        'companies_id',
        'usercreate',
        'userupdate',
    ];

    public function details(): HasMany
    {
        return $this->hasMany(DetailCxcPayment::class, 'cxc_payment_id', 'id');
    }

    public function detailsinvoices(): HasMany
    {
        return $this->hasMany(DetailCxcPayment::class, 'sales_invoice_id', 'id');
    }

    // Relación uno a muchos
    public function detailsothers(): HasMany
    {
        return $this->hasMany(DetailCxcOthersPayment::class, 'cxc_payment_id', 'id');
    }
}
