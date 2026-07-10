<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InventoryDocument extends Model
{
    use HasFactory;

    protected $fillable = [
        'nit',
        'branch',
        'name',
        'number',
        'concept_inv',
        'concept_class',
        'report_date',
        'purchase_invoice',
        'prefix',
        'documento_purchase',
        'order_number',
        'date_from',
        'date_to',
        'subtotal',
        'vatvalue',
        'reteiva',
        'reteica',
        'products_discount',
        'additional_discounts',
        'additional_value',
        'freight',
        'total_purchases',
        'plate',
        'type',
        'type_of_purchase',
        'state',
        'state01',
        'state02',
        'state03',
        'companies_id',
        'proyect',
        'sproyect',
        'center',
        'activity',
        'observations',
        'usercreate',
        'userupdate',
    ];
}
