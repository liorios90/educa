<?php

use App\Enums\CalificacionComportamiento;
use App\Enums\UsoEquivalencia;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $mapa = [
            'A' => CalificacionComportamiento::Siempre->value,
            'B' => CalificacionComportamiento::Frecuentemente->value,
            'C' => CalificacionComportamiento::Ocasionalmente->value,
            'D' => CalificacionComportamiento::Nunca->value,
            'E' => CalificacionComportamiento::Nunca->value,
        ];

        DB::transaction(function () use ($mapa): void {
            $equivalencias = DB::table('equivalencias')
                ->where('uso', UsoEquivalencia::Comportamiento->value)
                ->pluck('id');

            foreach ($equivalencias as $equivalenciaId) {
                $oficiales = [];

                foreach (CalificacionComportamiento::cases() as $escala) {
                    $existente = DB::table('equivalencias_detalles')
                        ->where('equivalencia_id', $equivalenciaId)
                        ->where('valor', $escala->value)
                        ->first();
                    $datos = [
                        'nombre' => $escala->label(),
                        'descripcion' => $escala->descripcion(),
                        'valor' => $escala->value,
                        'jerarquia' => $escala->jerarquia(),
                        'activo' => true,
                        'exige_observacion' => $escala->exigeObservacion(),
                    ];

                    if ($existente === null) {
                        $oficiales[$escala->value] = DB::table('equivalencias_detalles')->insertGetId([
                            'equivalencia_id' => $equivalenciaId,
                            ...$datos,
                        ]);
                    } else {
                        DB::table('equivalencias_detalles')->where('id', $existente->id)->update($datos);
                        $oficiales[$escala->value] = $existente->id;
                    }
                }

                $anteriores = DB::table('equivalencias_detalles')
                    ->where('equivalencia_id', $equivalenciaId)
                    ->whereIn('valor', array_keys($mapa))
                    ->get();

                foreach ($anteriores as $anterior) {
                    $destino = $oficiales[$mapa[$anterior->valor]];

                    if ((int) $anterior->id === (int) $destino) {
                        continue;
                    }

                    DB::table('establecimiento_comportamientos')
                        ->where('equivalencia_detalle_id', $anterior->id)
                        ->update(['equivalencia_detalle_id' => $destino]);

                    DB::table('equivalencias_detalles')->where('id', $anterior->id)->delete();
                }

                DB::table('equivalencias')
                    ->where('id', $equivalenciaId)
                    ->where('descripcion', 'like', '%Reglamento General a la LOEI%')
                    ->update([
                        'descripcion' => 'Escala de frecuencia del Ministerio de Educación. El valor jerárquico más alto es el mejor cumplimiento.',
                    ]);
            }
        });
    }

    public function down(): void
    {
        $regreso = [
            'S' => ['valor' => 'A', 'nombre' => 'Muy satisfactorio', 'jerarquia' => 5, 'exige_observacion' => false, 'descripcion' => 'Lidera el cumplimiento de los compromisos establecidos para la sana convivencia social.'],
            'F' => ['valor' => 'B', 'nombre' => 'Satisfactorio', 'jerarquia' => 4, 'exige_observacion' => false, 'descripcion' => 'Cumple con los compromisos establecidos para la sana convivencia social.'],
            'O' => ['valor' => 'C', 'nombre' => 'Poco satisfactorio', 'jerarquia' => 3, 'exige_observacion' => true, 'descripcion' => 'Falla ocasionalmente en el cumplimiento de los compromisos establecidos para la sana convivencia social.'],
            'N' => ['valor' => 'E', 'nombre' => 'Insatisfactorio', 'jerarquia' => 1, 'exige_observacion' => true, 'descripcion' => 'No cumple con los compromisos establecidos para la sana convivencia social.'],
        ];

        DB::transaction(function () use ($regreso): void {
            $equivalencias = DB::table('equivalencias')
                ->where('uso', UsoEquivalencia::Comportamiento->value)
                ->pluck('id');

            foreach ($equivalencias as $equivalenciaId) {
                foreach ($regreso as $codigo => $datos) {
                    DB::table('equivalencias_detalles')
                        ->where('equivalencia_id', $equivalenciaId)
                        ->where('valor', $codigo)
                        ->update($datos);
                }

                $tieneMejorable = DB::table('equivalencias_detalles')
                    ->where('equivalencia_id', $equivalenciaId)
                    ->where('valor', 'D')
                    ->exists();

                if (! $tieneMejorable) {
                    DB::table('equivalencias_detalles')->insert([
                        'equivalencia_id' => $equivalenciaId,
                        'nombre' => 'Mejorable',
                        'descripcion' => 'Falla reiteradamente en el cumplimiento de los compromisos establecidos para la sana convivencia social.',
                        'valor' => 'D',
                        'jerarquia' => 2,
                        'activo' => true,
                        'exige_observacion' => true,
                    ]);
                }
            }
        });
    }
};
