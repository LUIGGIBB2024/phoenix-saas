<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PaymentMethod extends Model
{
    use HasFactory;

    /**
     * El nombre de la tabla asociada al modelo.
     * (Opcional si tu modelo se llama exactamente PaymentMethod, 
     * pero bueno definirlo si tu tabla es plural estricto).
     *
     * @var string
     */
    protected $table = 'payment_methods';

    /**
     * Los atributos que son asignables en masa (Mass Assignable).
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'code',
        'name',
        'usercreate',
        'userupdate',
    ];
}
