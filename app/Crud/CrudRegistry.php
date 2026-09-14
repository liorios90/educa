<?php

namespace App\Crud;

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

            $fields[] = new CrudField(
                name: $field['name'],
                label: $field['label'],
                type: $field['type'] ?? 'text',
                list: $field['list'] ?? true,
                rules: $field['rules'] ?? [],
                unique: $field['unique'] ?? false,
            );
        }

        return new CrudDefinition(
            slug: $slug,
            model: $config['model'],
            title: $config['title'],
            singular: $config['singular'],
            orderBy: $config['order_by'] ?? 'id',
            fields: $fields,
        );
    }
}
