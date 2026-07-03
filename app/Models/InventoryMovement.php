<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;

class InventoryMovement extends Model
{
    use HasFactory, Notifiable; // 👈 ESTE trait es el que añade createToken()
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
