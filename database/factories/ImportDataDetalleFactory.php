<?php

namespace Database\Factories;

use App\Models\ImportData;
use App\Models\ImportDataDetalle;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ImportDataDetalle>
 */
class ImportDataDetalleFactory extends Factory
{
    protected $model = ImportDataDetalle::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'import_data_id' => ImportData::factory(),
            'num_fila' => 2,
            'identificacion' => fake()->numerify('##########'),
            'descripcion' => 'Importado correctamente',
        ];
    }
}
