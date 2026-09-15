<?php

namespace App\Models;

use Database\Factories\ReportDefinitionFieldFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['report_definition_id', 'column', 'label', 'sort_order', 'label_x', 'label_y', 'value_x', 'value_y'])]
class ReportDefinitionField extends Model
{
    /** @use HasFactory<ReportDefinitionFieldFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
            'label_x' => 'integer',
            'label_y' => 'integer',
            'value_x' => 'integer',
            'value_y' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<ReportDefinition, $this>
     */
    public function reportDefinition(): BelongsTo
    {
        return $this->belongsTo(ReportDefinition::class);
    }
}
