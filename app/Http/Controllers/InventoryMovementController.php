<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class InventoryMovementController extends Controller
{
    protected $fillable = [
        'code',
        'name',
        'store',
        'batch',
        'report_date',
        'number',
        'prefix',
        'concept_inv',
        'concept_class',
        'nit',
        'nit2',
        'branch',
        'health_batch',
        'plate',
        'serial',
        'amount',
        'amount1',
        'vat',
        'discount1',
        'discount2',
        'discount3',
        'unit_cost',
        'sale_price',
        'parcial_value',
        'type',
        'idregister',
        'state',
        'state01',
        'state02',
        'state03',
        'companies_id',
        'inventory_documents_id',
        'proyect',
        'sproyect',
        'center',
        'activity',
        'usercreate',
        'userupdate',
    ];
}
