<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PersonaBusqueda extends Model
{
    protected $connection = 'mysql';

    protected $table = 'persona';

    protected $fillable = [
        'id',
        'rut',
        'nombre1',
        'appaterno',
        'apmaterno',
        'email',
        'telefono',
        'direccion_encrypted',
    ];

    public static function normalizarRut(?string $rut): string
    {
        return strtoupper(preg_replace('/[^0-9K]/i', '', (string) $rut));
    }

    public function scopePorRut($query, ?string $rut)
    {
        $normalizado = self::normalizarRut($rut);
        $formateado = substr($normalizado, 0, -1).'-'.substr($normalizado, -1);

        return $query->where('rut', $formateado);
    }

    public function scopePorNombre($query, ?string $texto)
    {
        $terminos = collect(preg_split('/\s+/', trim((string) $texto)))
            ->filter()
            ->map(fn ($term) => '+'.$term)
            ->implode(' ');

        if ($terminos === '') {
            return $query;
        }

        return $query->where(function ($query) use ($terminos) {
            foreach (explode(' ', str_replace('+', '', $terminos)) as $termino) {
                $query->whereRaw("CONCAT_WS(' ', nombre1, appaterno, apmaterno) LIKE ?", ['%'.$termino.'%']);
            }
        });
    }
}
