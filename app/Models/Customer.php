<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Customer extends Model
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
        'nit',
        'branch',
        'dv',
        'patient_id',
        'code',
        'provider_code',

        // Nombres
        'name',
        'firstname',
        'lastname',
        'comercial_name',

        // Contacto y Ubicación
        'address',
        'phone',
        'email',
        'latitude',
        'longitude',
        'zip_code',

        // Representante y contacto secundario
        'nit_representative',
        'contact_phone',
        'name_contact',
        'email_contact',

        // Salud / Políticas
        'health_contract_number',
        'health_policy_number',

        // Financiero y Puntos
        'credit_quota',
        'deadline_days',
        'point',
        'accumulated_points',

        // Fechas
        'birthday',
        'last_purchase_date',
        'creation_date',

        // Información comercial / Contable
        'economic_activity',
        'business_registration',
        'sales_account',
        'center',
        'scenter',

        // Llaves Foráneas (Relaciones)
        'health_service_coverage_id',
        'health_payment_method_id',
        'branch_id',
        'route_id',
        'zone_id',
        'type_id',
        'neighborhood_id',
        'price_list_id',
        'municipalities_id',
        'sellers_id',
        'type_document_identification_id',
        'companies_id',
        'type_regime_id',
        'type_liability_id',

        // Estados y Enums
        'sex',
        'state',
        'typeofcurrency',
        'retesource',
        'reteiva',
        'reteica',
        'declare_income',
        'control_points',
        'capture_signature',

        // Auditoría manual
        'usercreate',
        'userupdate',
    ];

    public function route(): BelongsTo
    {
        return $this->belongsTo(MiscellaneousItem::class, 'route_id');
    }

    // Relación para obtener la Zona
    public function zone(): BelongsTo
    {
        return $this->belongsTo(MiscellaneousItem::class, 'zone_id');
    }

    // Relación para obtener el Tipo de Cliente
    public function type(): BelongsTo
    {
        return $this->belongsTo(MiscellaneousItem::class, 'type_id');
    }

    // Relación para obtener el Barrio    
    public function neighborhood(): BelongsTo
    {
        return $this->belongsTo(MiscellaneousItem::class, 'neighborhood_id');
    }

    // Relación para obtener la Lista de Precios
    public function priceList(): BelongsTo
    {
        return $this->belongsTo(PriceList::class, 'price_list_id');
    }

    // Relación para obtener el Municipio
    public function municipality(): BelongsTo
    {
        return $this->belongsTo(Municipality::class, 'municipalities_id');
    }

    // Relación para obtener la Compañía
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'companies_id');
    }

    // Relación para obtener los Detalles de Precios
    public function priceDetails(): BelongsTo
    {
        return $this->belongsTo(PriceDetail::class, 'prices_details_id');
    }

    // Relación para obtener el Vendedor
    public function seller(): BelongsTo
    {
        return $this->belongsTo(Seller::class, 'sellers_id');
    }

    // Relación para obtener el Tipo de Documento de Identificación
    public function typeDocumentIdentification(): BelongsTo
    {
        return $this->belongsTo(TypeDocumentIdentification::class, 'type_document_identification_id');
    }
}
