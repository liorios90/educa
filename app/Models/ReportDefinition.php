<?php

namespace App\Models;

use Database\Factories\ReportDefinitionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Permission\Models\Role as RoleModel;

#[Fillable(['name', 'source', 'is_active', 'visible_to_all', 'user_id'])]
class ReportDefinition extends Model
{
    /** @use HasFactory<ReportDefinitionFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'visible_to_all' => 'boolean',
        ];
    }

    /**
     * @return HasMany<ReportDefinitionField, $this>
     */
    public function fields(): HasMany
    {
        return $this->hasMany(ReportDefinitionField::class)->orderBy('sort_order')->orderBy('id');
    }

    /**
     * @return BelongsToMany<RoleModel, $this>
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(RoleModel::class, 'report_definition_role');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function isVisibleTo(User $user): bool
    {
        if (! $this->is_active) {
            return false;
        }

        if ($this->visible_to_all) {
            return true;
        }

        return $user->hasAnyRole($this->roles->pluck('name')->all());
    }
}
