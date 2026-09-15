<?php

namespace Database\Factories;

use App\Models\ReportDefinition;
use App\Models\ReportDefinitionField;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ReportDefinitionField>
 */
class ReportDefinitionFieldFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'report_definition_id' => ReportDefinition::factory(),
            'column' => 'nombre',
            'label' => 'Nombre',
            'sort_order' => 0,
            'label_x' => 4,
            'label_y' => 8,
            'value_x' => 32,
            'value_y' => 8,
        ];
    }
}
