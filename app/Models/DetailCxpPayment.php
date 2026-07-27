<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DetailCxpPayment extends Model
{
    /**
     * Los atributos que son asignables masivamente.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'cxp_payment_id',
        'purchases_invoice_id',
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
        'suppliers_id',
        'companies_id',
        'usercreate',
        'userupdate',
    ];

    public function supplier(): BelongsTo
    {
        // 🔑 'suppliers_id' le indica a Eloquent la llave foránea exacta en esta tabla.
        return $this->belongsTo(Supplier::class, 'suppliers_id');
    }
}
