<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['enrollment_section_id', 'label', 'type', 'options', 'is_required', 'position'])]
class EnrollmentField extends Model
{
    protected function casts(): array
    {
        return ['options' => 'array', 'is_required' => 'boolean', 'position' => 'integer'];
    }

    /** @return BelongsTo<EnrollmentSection, $this> */
    public function section(): BelongsTo
    {
        return $this->belongsTo(EnrollmentSection::class, 'enrollment_section_id');
    }
}
