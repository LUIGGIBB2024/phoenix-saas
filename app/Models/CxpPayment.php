<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CxpPayment extends Model
{
    /**
     * Los atributos que son asignables masivamente.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'nit',
        'branch',
        'lapse',
        'report_date',
        'check_date',
        'delivery_date',
        'consecutive',
        'document',
        'supplier_name',
        'value_cxp',
        'others_payments',
        'observations',
        'payment_method',
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
        'suppliers_id',
        'companies_id',
        'usercreate',
        'userupdate',
    ];

    // Relación uno a muchos
    // Obtener un pago con todos sus detalles cargados de forma eficiente (Eager Loading)
    // Ejemplo para utilizar la relaciín CxpPayment -> DetailCxpPayment (Controller)
    // $payment = CxpPayment::with('details')->find(1);

    // foreach ($payment->details as $detail) {
    //     logger("Concepto: {$detail->concept} - Monto: {$detail->payment_amount}");
    // }
    public function details(): HasMany
    {
        return $this->hasMany(DetailCxpPayment::class, 'cxp_payment_id', 'id');
    }

    // Relación uno a muchos
    public function detailsothers(): HasMany
    {
        return $this->hasMany(DetailCxpOthersPayment::class, 'cxp_payment_id', 'id');
    }
}
