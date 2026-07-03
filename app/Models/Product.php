<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Notifications\Notifiable;

class Product extends Model
{
    use HasFactory, Notifiable; // 👈 ESTE trait es el que añade createToken()

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $table = "products";
    protected $fillable = [
        'id',
        'code',
        'name',
        'codereference',
        'unit_of_measure',
        'presentation',
        'percent',
        'sale_value',
        'cost',
        'location',
        'control_id',
        'typeofproduct',
        'require_scale',
        'billable',
        'group',
        'subgroup',
        'division',
        'category',
        'family',
        'namephoto',
        'routephoto',
        'observations',
        'cups',
        'alternate_code',
        'cie10_code',
        'invima_register',
        'units_per_packaging',
        'weight_volume',
        'conversion_factor',
        'date_last_purchase',
        'minimum_stock',
        'maximum_stock',
        'profitability',
        'consumption_tax',
        'listvalue1',
        'listvalue2',
        'listvalue3',
        'companies_id',
        'state'
    ];

    // 🔗 Relación: un producto pertenece a una empresa
    public function company()
    {
        return $this->belongsTo(Company::class, 'companies_id');
    }
}
