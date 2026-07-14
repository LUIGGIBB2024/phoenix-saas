<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Notifications\Notifiable;

class PriceDetail extends Model
{
    use HasFactory, Notifiable; // 👈 ESTE trait es el que añade createToken()

    protected $fillable = [
        // Códigos y Referencias de Texto
        'code',
        'product',

        // Llaves Foráneas (Relaciones)
        'companies_id',
        'products_id',
        'price_lists_id',

        // Valores Numéricos y Precios (Cálculos)
        'vat',
        'price',
        'priceunit',
        'beforevat',
    ];
}
