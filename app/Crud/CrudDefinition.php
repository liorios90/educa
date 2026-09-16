<?php

namespace App\Crud;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;
use InvalidArgumentException;

class CrudDefinition
{
    /**
     * @param  class-string<Model>  $model
     * @param  list<CrudField>  $fields
     */
    public function __construct(
        public string $slug,
        public string $model,
        public string $title,
        public string $singular,
        public string $orderBy,
        public array $fields,
    ) {
        $allowedOrderBy = [...$this->fillableNames(), 'id'];

        if (! in_array($this->orderBy, $allowedOrderBy, true)) {
            throw new InvalidArgumentException("Invalid CRUD order_by for {$this->slug}.");
        }
    }

    /**
     * @return Builder<Model>
     */
    public function query(): Builder
    {
        return $this->model::query();
    }

    public function findOrFail(int $id): Model
    {
        return $this->query()->findOrFail($id);
    }

    /**
     * @return list<CrudField>
     */
    public function listFields(): array
    {
        return array_values(array_filter(
            $this->fields,
            fn (CrudField $field): bool => $field->list,
        ));
    }

    /**
     * @return list<string>
     */
    public function fillableNames(): array
    {
        return array_map(
            fn (CrudField $field): string => $field->name,
            $this->fields,
        );
    }

    /**
     * @return array<string, list<mixed>>
     */
    public function validationRules(?int $ignoreId = null): array
    {
        $table = (new $this->model)->getTable();
        $rules = [];

        foreach ($this->fields as $field) {
            $fieldRules = $field->rules;

            if ($field->unique) {
                $unique = Rule::unique($table, $field->name);

                if ($ignoreId !== null) {
                    $unique->ignore($ignoreId);
                }

                $fieldRules[] = $unique;
            }

            $rules[$field->name] = $fieldRules;
        }

        return $rules;
    }

    public function routeName(string $action): string
    {
        return "sistemas.crud.{$this->slug}.{$action}";
    }

    /**
     * @return list<string>
     */
    public function listRelations(): array
    {
        $relations = [];

        foreach ($this->fields as $field) {
            if ($field->type === 'select' && $field->relation !== null) {
                $relations[] = "{$field->relation}:id,{$field->optionLabel}";
            }
        }

        return $relations;
    }

    /**
     * @return array<string, list<array{id: int, label: string}>>
     */
    public function selectOptions(): array
    {
        $options = [];

        foreach ($this->fields as $field) {
            if ($field->type !== 'select' || $field->relatedModel === null) {
                continue;
            }

            $options[$field->name] = $field->relatedModel::query()
                ->orderBy($field->optionLabel)
                ->get(['id', $field->optionLabel])
                ->map(fn (Model $record): array => [
                    'id' => (int) $record->getKey(),
                    'label' => (string) $record->getAttribute($field->optionLabel),
                ])
                ->all();
        }

        return $options;
    }

    public function displayValue(Model $record, CrudField $field): string
    {
        if ($field->type === 'select' && $field->relation !== null) {
            $related = $record->getRelationValue($field->relation);
            $label = $related?->getAttribute($field->optionLabel);

            return ($label === null || $label === '') ? '—' : (string) $label;
        }

        $value = $record->getAttribute($field->name);

        if ($field->type === 'boolean') {
            return $value ? 'Sí' : 'No';
        }

        if ($value === null || $value === '') {
            return '—';
        }

        return (string) $value;
    }
}
