<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PriceList extends Model
{
    protected $fillable = [
        'code',
        'name',
        'companies_id',
        'state',
        'typeofcurrency',
        'sale_value',
        'usercreate',
        'userupdate',
    ];
}
