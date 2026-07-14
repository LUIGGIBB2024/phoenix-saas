<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentConcept extends Model
{
    /**
     * Los atributos que son asignables masivamente.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'code',
        'name',
        'description',
        'account',
        'center',
        'scenter',
        'type',
        'typemovement',
        'typeofcalculation',
        'aplicateaccount',
        'generatenote',
        'advances',
        'indicators',
        'companies_id',
        'usercreate',
        'userupdate',
    ];
}
