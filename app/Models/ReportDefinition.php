<?php

namespace App\Models;

use App\Enums\ReportLayout;
use Database\Factories\ReportDefinitionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Permission\Models\Role as RoleModel;

#[Fillable([
    'name',
    'source',
    'is_active',
    'visible_to_all',
    'user_id',
    'layout',
    'table_border_width',
    'table_border_color',
    'table_header',
    'table_header_background',
    'table_striped',
    'table_font_size',
    'table_cell_padding',
])]
class ReportDefinition extends Model
{
    /** @use HasFactory<ReportDefinitionFactory> */
    use HasFactory;

    /**
     * Mirrors the database defaults so a report renders with a usable table
     * format before those columns are read back from the database.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'layout' => 'canvas',
        'table_border_width' => 1,
        'table_border_color' => '#cbd5e1',
        'table_header' => true,
        'table_header_background' => '#f1f5f9',
        'table_striped' => false,
        'table_font_size' => 12,
        'table_cell_padding' => 8,
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'visible_to_all' => 'boolean',
            'layout' => ReportLayout::class,
            'table_border_width' => 'integer',
            'table_header' => 'boolean',
            'table_striped' => 'boolean',
            'table_font_size' => 'integer',
            'table_cell_padding' => 'integer',
        ];
    }

    public function usesTableLayout(): bool
    {
        return $this->layout === ReportLayout::Table;
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
