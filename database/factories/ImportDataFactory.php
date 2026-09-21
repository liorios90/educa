<?php

namespace Database\Factories;

use App\Models\Establecimiento;
use App\Models\ImportData;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ImportData>
 */
class ImportDataFactory extends Factory
{
    protected $model = ImportData::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nombre' => 'representantes.xlsx',
            'tablas' => 'users,personas,padres',
            'tipo_archivo' => 'xlsx',
            'mensaje' => 'Importados: 0. Fallidos: 0.',
            'establecimiento_id' => Establecimiento::factory(),
            'usuario' => fake()->userName(),
            'activo' => 1,
        ];
    }
}
