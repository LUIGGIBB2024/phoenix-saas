<?php


namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Model;

class DetailCxpOthersPayment extends Model
{
    use HasFactory, Notifiable; // 👈 ESTE trait es el que añade createToken()
    protected $fillable = [
        'cxp_payment_id',
        'consecutive',
        'document',
        'nit',
        'branch',
        'report_date',
        'internaldoc',
        'concept',
        'accounting_code',
        'center',
        'scenter',
        'proyect',
        'sproyect',
        'activity',
        'payment_amount',
        'idregister',
        'calculate',
        'state',
        'state01',
        'state02',
        'state03',
        'suppliers_id',
        'companies_id',
        'usercreate',
        'userupdate',
    ];
}
