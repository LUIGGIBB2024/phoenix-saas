<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\DB;

class ControlConsecutive extends Model
{
    use HasFactory, Notifiable; // 👈 ESTE trait es el que añade createToken()
    protected $fillable = [
        'type',
        'lapso',
        'consecutive',
        'companies_id',
    ];

    public static function getNextConsecutive(string $type, int $companyId, ?string $lapso = null): int
    {
        // Si no se envía el lapso, tomamos el año actual por defecto
        $lapso = $lapso ?? date('Y');

        return DB::transaction(function () use ($type, $companyId, $lapso) {
            // Buscamos el registro bloqueándolo para actualización en BD
            $control = static::where('type', $type)
                ->where('companies_id', $companyId)
                ->where('lapso', $lapso)
                ->lockForUpdate()
                ->first();

            // Si el registro no existe, lo creamos iniciando en 1
            if (!$control) {
                static::create([
                    'type'         => $type,
                    'companies_id' => $companyId,
                    'lapso'        => $lapso,
                    'consecutive'  => 1,
                ]);

                return 1;
            }

            // Incrementamos en 1
            $control->increment('consecutive');

            return $control->consecutive;
        });
    }
}
