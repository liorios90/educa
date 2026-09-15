<?php

namespace App\Reports;

use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

class ReportCatalog
{
    /**
     * @return array<string, array<string, mixed>>
     */
    public function sources(): array
    {
        /** @var array<string, array<string, mixed>> $sources */
        $sources = config('reports.sources', []);

        return $sources;
    }

    /**
     * Payload for the designer: labels and fields grouped as the controller sends them.
     *
     * @return array<string, array{label: string, fields: list<array{key: string, label: string, group: string}>}>
     */
    public function designerSources(): array
    {
        $payload = [];

        foreach ($this->sources() as $slug => $source) {
            $payload[$slug] = [
                'label' => $source['label'],
                'fields' => array_values(array_map(
                    function (array $field) use ($source): array {
                        return [
                            'key' => $field['key'],
                            'label' => $field['label'],
                            'group' => $field['group'] ?? $source['label'],
                        ];
                    },
                    $source['fields'],
                )),
            ];
        }

        return $payload;
    }

    /**
     * @return array<string, mixed>
     */
    public function source(string $slug): array
    {
        $source = $this->sources()[$slug] ?? null;

        if ($source === null) {
            throw new InvalidArgumentException("Unknown report source [{$slug}].");
        }

        $model = $source['model'] ?? null;

        if (! is_string($model) || ! is_a($model, Model::class, true)) {
            throw new InvalidArgumentException("Invalid report model [{$slug}].");
        }

        if (! preg_match('/^[a-z][a-z0-9_]*$/', $source['order_by'])) {
            throw new InvalidArgumentException("Invalid report order_by [{$slug}].");
        }

        return $source;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function field(string $slug, string $key): ?array
    {
        foreach ($this->source($slug)['fields'] as $field) {
            if ($field['key'] === $key) {
                $relation = $field['relation'] ?? null;
                $attribute = $field['attribute'] ?? null;

                if ($relation !== null && ! preg_match('/^[a-z][a-z0-9_]*$/', $relation)) {
                    return null;
                }

                if ($attribute !== null && ! preg_match('/^[a-z][a-z0-9_]*$/', $attribute)) {
                    return null;
                }

                return $field;
            }
        }

        return null;
    }

    public function hasField(string $slug, string $key): bool
    {
        if (! array_key_exists($slug, $this->sources())) {
            return false;
        }

        return $this->field($slug, $key) !== null;
    }
}
