<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['enrollment_form_id', 'user_id', 'student_name', 'responses', 'schema_snapshot', 'submitted_at'])]
class EnrollmentSubmission extends Model
{
    protected function casts(): array
    {
        return ['responses' => 'array', 'schema_snapshot' => 'array', 'submitted_at' => 'datetime'];
    }

    /** @return BelongsTo<EnrollmentForm, $this> */
    public function form(): BelongsTo
    {
        return $this->belongsTo(EnrollmentForm::class, 'enrollment_form_id');
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
