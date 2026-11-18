<?php

namespace Database\Seeders;

use App\Models\Sys_Circuito;
use App\Models\Sys_Distrito;
use App\Models\Sys_Pais;
use App\Models\Sys_Provincia;
use App\Models\Sys_Zona;
use Illuminate\Database\Seeder;
use InvalidArgumentException;

class TerritorioEducativoSeeder extends Seeder
{
    /**
     * Carga las provincias del Ecuador y las zonas, distritos y circuitos
     * educativos registrados por el Ministerio de Educación.
     *
     * Se puede volver a ejecutar: actualiza los registros oficiales y no duplica.
     */
    public function run(): void
    {
        /** @var array{zonas: list<array{nombre: string, descripcion: string}>, provincias: list<array{nombre: string, descripcion: string}>, distritos: list<array{zona: string, codigo: string, nombre: string, provincia: string}>} $territorio */
        $territorio = require __DIR__.'/data/territorio_educativo.php';

        $pais = $this->paisEcuador();

        foreach ($territorio['provincias'] as $provincia) {
            $this->assertLongitud($provincia['descripcion'], $provincia['nombre']);

            Sys_Provincia::query()->updateOrCreate(
                [
                    'nombre' => $provincia['nombre'],
                    'pais_id' => $pais->id,
                ],
                [
                    'descripcion' => $provincia['descripcion'],
                    'usuario' => 'MINEDUC',
                    'activo' => 1,
                ],
            );
        }

        foreach ($territorio['zonas'] as $zona) {
            $this->assertLongitud($zona['descripcion'], $zona['nombre']);

            Sys_Zona::query()->updateOrCreate(
                ['nombre' => $zona['nombre']],
                [
                    'descripcion' => $zona['descripcion'],
                    'usuario' => 'MINEDUC',
                    'activo' => 1,
                ],
            );
        }

        foreach ($territorio['distritos'] as $distrito) {
            $zona = Sys_Zona::query()->where('nombre', $distrito['zona'])->first();

            if ($zona === null) {
                throw new InvalidArgumentException("La zona {$distrito['zona']} del distrito {$distrito['codigo']} no está registrada.");
            }

            $nombre = $distrito['codigo'].' '.$distrito['nombre'];
            $descripcion = "Dirección Distrital {$nombre}, provincia de {$distrito['provincia']}.";
            $this->assertLongitud($descripcion, $distrito['codigo']);

            $registro = Sys_Distrito::query()
                ->where(function ($query) use ($distrito): void {
                    $query->where('nombre', $distrito['codigo'])
                        ->orWhere('nombre', 'like', $distrito['codigo'].' %');
                })
                ->first() ?? new Sys_Distrito;

            $registro->fill([
                'nombre' => $nombre,
                'provincia' => $distrito['provincia'],
                'descripcion' => $descripcion,
                'usuario' => 'MINEDUC',
                'activo' => 1,
                'zona_id' => $zona->id,
            ]);
            $registro->save();
        }

        $this->cargarCircuitos();
    }

    private function cargarCircuitos(): void
    {
        /** @var list<array{codigo: string, parroquia: string}> $circuitos */
        $circuitos = require __DIR__.'/data/circuitos_educativos.php';

        $distritosPorCodigo = [];
        foreach (Sys_Distrito::query()->get() as $distrito) {
            if (preg_match('/^(\d{2}D\d{2})\b/', $distrito->nombre, $coincidencia) === 1) {
                $distritosPorCodigo[$coincidencia[1]] = $distrito;
            }
        }

        $existentes = [];
        foreach (Sys_Circuito::query()->get() as $circuitoExistente) {
            if (preg_match('/^(\d{2}D\d{2}C\d{2})\b/', $circuitoExistente->nombre, $coincidencia) === 1) {
                $existentes[$coincidencia[1]] = $circuitoExistente;
            }
        }

        foreach ($circuitos as $circuito) {
            $codigoDistrito = substr($circuito['codigo'], 0, 5);
            $distrito = $distritosPorCodigo[$codigoDistrito] ?? null;

            if (! $distrito instanceof Sys_Distrito) {
                throw new InvalidArgumentException("El distrito {$codigoDistrito} del circuito {$circuito['codigo']} no está registrado.");
            }

            $nombre = $circuito['parroquia'] === ''
                ? $circuito['codigo']
                : $circuito['codigo'].' '.$circuito['parroquia'];
            $descripcion = $circuito['parroquia'] === ''
                ? "Circuito educativo {$circuito['codigo']}."
                : "Circuito educativo {$circuito['codigo']}, {$circuito['parroquia']}.";
            $this->assertLongitud($nombre, $circuito['codigo']);
            $this->assertLongitud($descripcion, $circuito['codigo']);

            $registro = $existentes[$circuito['codigo']] ?? new Sys_Circuito;
            $registro->fill([
                'nombre' => $nombre,
                'descripcion' => $descripcion,
                'usuario' => 'MINEDUC',
                'activo' => 1,
                'distrito_id' => $distrito->id,
            ]);
            $registro->save();
            $existentes[$circuito['codigo']] = $registro;
        }
    }

    private function paisEcuador(): Sys_Pais
    {
        $pais = Sys_Pais::query()->where('nombre', 'Ecuador')->first()
            ?? Sys_Pais::query()->whereRaw('lower(nombre) = ?', ['ecuador'])->first();

        if ($pais !== null) {
            return $pais;
        }

        return Sys_Pais::query()->create([
            'nombre' => 'Ecuador',
            'descripcion' => 'República del Ecuador.',
            'usuario' => 'MINEDUC',
            'activo' => 1,
        ]);
    }

    private function assertLongitud(string $texto, string $etiqueta): void
    {
        if (mb_strlen($texto) > 255) {
            throw new InvalidArgumentException("La descripción de {$etiqueta} supera 255 caracteres.");
        }
    }
}
