<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['establecimiento_id', 'periodo_inicio', 'periodo_fin', 'title', 'is_active'])]
class EnrollmentForm extends Model
{
    protected function casts(): array
    {
        return [
            'periodo_inicio' => 'date',
            'periodo_fin' => 'date',
            'is_active' => 'boolean',
        ];
    }

    /** @return BelongsTo<Establecimiento, $this> */
    public function establecimiento(): BelongsTo
    {
        return $this->belongsTo(Establecimiento::class);
    }

    /** @return HasMany<EnrollmentSection, $this> */
    public function sections(): HasMany
    {
        return $this->hasMany(EnrollmentSection::class)->whereNull('parent_id')->orderBy('position')->orderBy('id');
    }

    /** @return HasMany<EnrollmentSubmission, $this> */
    public function submissions(): HasMany
    {
        return $this->hasMany(EnrollmentSubmission::class);
    }
}
