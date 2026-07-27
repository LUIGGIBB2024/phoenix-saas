<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class PaymentConceptController extends Controller
{
    protected $fillable = [
        'code',
        'name',
        'description',
        'account',
        'center',
        'scenter',
        'type',
        'typemovement',
        'typeofcalculation',
        'aplicateaccount',
        'generatenote',
        'advances',
        'indicators',
        'companies_id',
        'usercreate',
        'userupdate',
    ];
}
