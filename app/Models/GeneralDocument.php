<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GeneralDocument extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'code',
        'name',
        'consecutive',
        'type',
        'typedocument1',
        'typedocument2',
        'typedocument3',
        'typedocument4',
        'controlconsecutive',
        'state',
        'companies_id',
        'resolutions_id',
        'control_consecutives_id',
        'accounting_setups_id',
    ];
}
