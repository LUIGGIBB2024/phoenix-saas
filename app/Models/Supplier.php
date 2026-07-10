<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Supplier extends Model
{

    use HasFactory;

    protected $fillable = [
        'nit',
        'branch',
        'dv',
        'patient_id',
        'code',
        'name',
        'firstname',
        'lastname',
        'comercial_name',
        'address',
        'phone',
        'email',
        'contact_phone',
        'name_contact',
        'email_contact',
        'position_contact',
        'latitude',
        'longitude',
        'economic_activity',
        'zip_code',
        'business_registration',
        'bank_account_id',
        'branch_id',
        'type_id',
        'municipalities_id',
        'type_document_identification_id',
        'companies_id',
        'type_regime_id',
        'type_liability_id',
        'stype_of_taxpayer',
        'state',
        'retesource',
        'reteiva',
        'reteica',
        'usercreate',
        'userupdate',
    ];
}
