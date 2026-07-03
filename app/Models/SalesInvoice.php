<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Model;

class SalesInvoice extends Model
{
    use HasFactory, Notifiable; // 👈 ESTE trait es el que añade createToken()

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */

    protected $fillable = [
        'date_issue',
        'expiration_date',
        'entry_date',
        'departure_date',
        'number',
        'prefix',
        'document_name',
        'customer',
        'branch',
        'patient_id',
        'client_name',
        'subtotal',
        'discounts',
        'vatvalue',
        'retefuente',
        'reteiva',
        'reteica',
        'impoconsumo',
        'products_discount',
        'additional_discounts',
        'exempt_sales',
        'taxed_sales',
        'additional_value',
        'cost_of_sale',
        'payment_value',
        'health_copays',
        'health_advances',
        'health_moderator_fee',
        'tip',
        'hours',
        'minutes',
        'total_items',
        'total_sale',
        'cufe',
        'observations',
        'plate',
        'room',
        'purchase_orders',
        'document_number',
        'property',
        'authorization',
        'type_operation',
        'scenery',
        'conveyor',
        'table',
        'order',
        'seller',
        'route',
        'zone',
        'typecustomer',
        'box',
        'atm',
        'list',
        'proyect',
        'sproyect',
        'center',
        'activity',
        'state',
        'type',
        'companies_id',
        'payment_methods_id',
        'payment_forms_id',
        'usercreate',
        'userupdate',
    ];


    // 🔗 Relación: una factura pertenece a una empresa
    public function company()
    {
        return $this->belongsTo(Company::class, 'companies_id');
    }
}
