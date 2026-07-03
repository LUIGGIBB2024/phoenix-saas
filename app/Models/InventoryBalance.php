<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InventoryBalance extends Model
{
    // Relación para obtener la Ruta

    use HasFactory;

    /**
     * Los atributos que se pueden asignar de forma masiva.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        // Identificaciones y códigos
        'year',
        'code',
        'store',
        'batch',
        'quantity',
        'quantity1',
        'previous_balance',
        'cost',
        'lastcost',
        'cost00',
        'cost01',
        'cost02',
        'cost03',
        'cost04',
        'cost05',
        'cost06',
        'cost07',
        'cost08',
        'cost09',
        'cost10',
        'cost11',
        'cost12',
        'companies_id',
        'products_id',
        'usercreate',
        'userupdate',
    ];
}
