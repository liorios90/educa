<?php

namespace App\Crud;

use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

class CrudRegistry
{
    /**
     * @return list<string>
     */
    public function slugs(): array
    {
        /** @var array<string, mixed> $resources */
        $resources = config('crud.resources', []);

        return array_keys($resources);
    }

    public function get(string $slug): CrudDefinition
    {
        $config = config("crud.resources.{$slug}");

        if (! is_array($config)) {
            abort(404);
        }

        return $this->hydrate($slug, $config);
    }

    public function slugFromRouteName(?string $name): string
    {
        if (preg_match('/^sistemas\.crud\.([a-z0-9_-]+)\./', $name ?? '', $matches) !== 1) {
            abort(404);
        }

        return $matches[1];
    }

    /**
     * @param  array<string, mixed>  $config
     */
    private function hydrate(string $slug, array $config): CrudDefinition
    {
        $fields = [];

        foreach ($config['fields'] as $field) {
            if (! preg_match('/^[a-z][a-z0-9_]*$/', $field['name'])) {
                throw new InvalidArgumentException("Invalid CRUD field name for {$slug}.");
            }

            $type = $field['type'] ?? 'text';
            $relatedModel = $field['related_model'] ?? null;
            $relation = $field['relation'] ?? null;
            $optionLabel = $field['option_label'] ?? 'nombre';
            $options = $this->options($slug, $field);

            if ($type === 'select' && $options === []) {
                if (! is_string($relatedModel) || ! is_a($relatedModel, Model::class, true)) {
                    throw new InvalidArgumentException("Invalid CRUD related_model for {$slug}.{$field['name']}.");
                }

                if (! is_string($relation) || $relation === '') {
                    throw new InvalidArgumentException("Invalid CRUD relation for {$slug}.{$field['name']}.");
                }
            }

            $fields[] = new CrudField(
                name: $field['name'],
                label: $field['label'],
                type: $type,
                list: $field['list'] ?? true,
                rules: $field['rules'] ?? [],
                unique: $field['unique'] ?? false,
                relatedModel: is_string($relatedModel) ? $relatedModel : null,
                optionLabel: is_string($optionLabel) ? $optionLabel : 'nombre',
                relation: is_string($relation) ? $relation : null,
                options: $options,
            );
        }

        $messages = $config['messages'] ?? [];

        if (! is_array($messages)) {
            throw new InvalidArgumentException("Invalid CRUD messages for {$slug}.");
        }

        return new CrudDefinition(
            slug: $slug,
            model: $config['model'],
            title: $config['title'],
            singular: $config['singular'],
            orderBy: $config['order_by'] ?? 'id',
            fields: $fields,
            messages: $messages,
            newLabel: is_string($config['new_label'] ?? null) ? $config['new_label'] : null,
        );
    }

    /**
     * @param  array<string, mixed>  $field
     * @return list<array{id: string, label: string}>
     */
    private function options(string $slug, array $field): array
    {
        if (! array_key_exists('options', $field)) {
            return [];
        }

        if (! is_array($field['options'])) {
            throw new InvalidArgumentException("Invalid CRUD options for {$slug}.{$field['name']}.");
        }

        $options = [];

        foreach ($field['options'] as $option) {
            if (! is_array($option) || ! isset($option['id'], $option['label']) || ! is_scalar($option['id']) || ! is_string($option['label'])) {
                throw new InvalidArgumentException("Invalid CRUD options for {$slug}.{$field['name']}.");
            }

            $options[] = [
                'id' => (string) $option['id'],
                'label' => $option['label'],
            ];
        }

        return $options;
    }
}
