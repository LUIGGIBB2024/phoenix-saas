<?php

namespace App\Models;


use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Model;

class SourcePayment extends Model
{
    use HasFactory, Notifiable; // 👈 ESTE trait es el que añade createToken()

    protected $fillable = [
        'code',
        'name',
        'type',
        'state',
        'accounting_setups_id',
        'companies_id',
        'usercreate',
        'userupdate',
    ];
}
