<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['enrollment_form_id', 'parent_id', 'title', 'position'])]
class EnrollmentSection extends Model
{
    /** @return BelongsTo<EnrollmentForm, $this> */
    public function form(): BelongsTo
    {
        return $this->belongsTo(EnrollmentForm::class, 'enrollment_form_id');
    }

    /** @return BelongsTo<EnrollmentSection, $this> */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    /** @return HasMany<EnrollmentSection, $this> */
    public function subsections(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('position')->orderBy('id');
    }

    /** @return HasMany<EnrollmentField, $this> */
    public function fields(): HasMany
    {
        return $this->hasMany(EnrollmentField::class)->orderBy('position')->orderBy('id');
    }
}
