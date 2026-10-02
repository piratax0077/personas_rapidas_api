<?php

namespace App\Http\Controllers;

use App\Models\PersonaBusqueda;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PersonaRapidaController extends Controller
{
    public function index()
    {
        return view('personas_rapidas.prueba');
    }

    public function buscar(Request $request): JsonResponse
    {
        $rut = PersonaBusqueda::normalizarRut($request->query('rut'));
        $rutPartes = $this->rutPartes($rut);

        if (! $rutPartes['ok']) {
            return response()->json([
                'ok' => false,
                'found' => false,
                'ready_to_create' => false,
                'message' => $rutPartes['message'],
            ]);
        }

        try {
            $persona = PersonaBusqueda::porRut($rut)->first();
        } catch (\Throwable $e) {
            Log::error('Error consultando persona rapida', [
                'rut' => $rut,
                'message' => $e->getMessage(),
            ]);

            return response()->json([
                'ok' => false,
                'found' => false,
                'ready_to_create' => false,
                'message' => 'No se pudo consultar la base rapida.',
            ], 500);
        }

        if (! $persona) {
            return response()->json([
                'ok' => true,
                'found' => false,
                'ready_to_create' => true,
                'rut_normalizado' => $rut,
                'rut_cuerpo' => $rutPartes['cuerpo'],
                'rut_dv' => $rutPartes['dv'],
                'message' => 'RUT no encontrado.',
            ]);
        }

        return response()->json([
            'ok' => true,
            'found' => true,
            'ready_to_create' => false,
            'persona' => $this->payload($persona),
            'message' => 'Persona encontrada.',
        ]);
    }

    public function guardar(Request $request): JsonResponse
    {
        $data = $request->validate([
            'rut' => ['required', 'string', 'max:30'],
            'nombre1' => ['required', 'string', 'max:255'],
            'appaterno' => ['nullable', 'string', 'max:255'],
            'apmaterno' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'telefono' => ['nullable', 'string', 'max:50'],
            'direccion' => ['nullable', 'string', 'max:500'],
        ]);

        $rut = PersonaBusqueda::normalizarRut($data['rut']);
        $rutPartes = $this->rutPartes($rut);

        if (! $rutPartes['ok']) {
            return response()->json([
                'ok' => false,
                'message' => $rutPartes['message'],
            ], 422);
        }

        $connection = DB::connection('personas_fast');
        $lockName = 'medsdi_persona_rapida_guardar';
        $lock = $connection->selectOne('SELECT GET_LOCK(?, 10) AS acquired', [$lockName]);

        if (! $lock || (int) $lock->acquired !== 1) {
            return response()->json([
                'ok' => false,
                'message' => 'No se pudo reservar la escritura. Intente nuevamente.',
            ], 503);
        }

        try {
            [$persona, $created] = $connection->transaction(function () use ($data, $rut) {
                $persona = PersonaBusqueda::porRut($rut)->first();
                $created = ! $persona;

                if (! $persona) {
                    $persona = new PersonaBusqueda();
                    $persona->id = ((int) PersonaBusqueda::max('id')) + 1;
                    $persona->rut_original = $this->formatearRut($rut);
                    $persona->rut_normalizado = $rut;
                    $persona->rut_cuerpo = (int) substr($rut, 0, -1);
                    $persona->rut_dv = substr($rut, -1);
                }

                $persona->nombre1 = trim($data['nombre1']);
                $persona->appaterno = $this->nullableTrim($data['appaterno'] ?? null);
                $persona->apmaterno = $this->nullableTrim($data['apmaterno'] ?? null);
                $persona->nombre_completo = trim(implode(' ', array_filter([
                    $persona->nombre1,
                    $persona->appaterno,
                    $persona->apmaterno,
                ])));
                $persona->estado = 'activo';
                $persona->save();

                $connection->table('personas_rapidas_ediciones')->updateOrInsert(
                    ['rut_normalizado' => $rut],
                    [
                        'nombre1' => $persona->nombre1,
                        'appaterno' => $persona->appaterno,
                        'apmaterno' => $persona->apmaterno,
                        'nombre_completo' => $persona->nombre_completo,
                        'email' => $this->nullableTrim($data['email'] ?? null),
                        'telefono' => $this->nullableTrim($data['telefono'] ?? null),
                        'direccion_encrypted' => ! empty($data['direccion'])
                            ? Crypt::encryptString(trim($data['direccion']))
                            : null,
                        'origen' => 'formulario_prueba',
                        'updated_at' => now(),
                    ]
                );

                return [$persona, $created];
            });
        } catch (\Throwable $e) {
            Log::error('Error guardando persona', [
                'rut' => $rut,
                'message' => $e->getMessage(),
            ]);

            return response()->json([
                'ok' => false,
                'message' => 'No se pudo guardar la persona.',
            ], 500);
        } finally {
            $connection->selectOne('SELECT RELEASE_LOCK(?) AS released', [$lockName]);
        }

        return response()->json([
            'ok' => true,
            'found' => true,
            'created' => $created,
            'persona' => $this->payload($persona),
            'message' => $created ? 'Persona creada.' : 'Persona actualizada.',
        ], $created ? 201 : 200);
    }

    private function payload(PersonaBusqueda $persona): array
    {
        return [
            'id' => $persona->id,
            'rut' => $persona->rut,
            'rut_normalizado' => PersonaBusqueda::normalizarRut($persona->rut),
            'nombre1' => $persona->nombre1,
            'appaterno' => $persona->appaterno,
            'apmaterno' => $persona->apmaterno,
            'email' => $persona->email,
            'telefono' => $persona->telefono,
            'direccion' => $this->decryptNullable($persona->direccion_encrypted),
            'nombre_completo' => trim(implode(' ', array_filter([
                $persona->nombre1,
                $persona->appaterno,
                $persona->apmaterno,
            ]))),
        ];
    }

    private function formatearRut(string $rut): string
    {
        return substr($rut, 0, -1).'-'.substr($rut, -1);
    }

    private function nullableTrim(?string $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    private function decryptNullable(?string $value): ?string
    {
        if (! $value) {
            return null;
        }

        try {
            return Crypt::decryptString($value);
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function rutPartes(string $rut): array
    {
        if (! preg_match('/^([0-9]+)([0-9K])$/', $rut, $matches)) {
            return ['ok' => false, 'message' => 'Ingrese un RUT valido: numeros y digito verificador.'];
        }

        $cuerpo = (int) $matches[1];

        if ($cuerpo < 1000000) {
            return ['ok' => false, 'message' => 'No se aceptan RUT con numero menor a 1.000.000.'];
        }

        return [
            'ok' => true,
            'cuerpo' => $cuerpo,
            'dv' => $matches[2],
        ];
    }
}
