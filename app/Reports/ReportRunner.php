<?php

namespace App\Reports;

use App\Models\ReportDefinition;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use InvalidArgumentException;

class ReportRunner
{
    public function __construct(private ReportCatalog $catalog) {}

    /**
     * @return LengthAwarePaginator<int, array<string, mixed>>
     */
    public function paginate(ReportDefinition $report, int $perPage = 25): LengthAwarePaginator
    {
        $prepared = $this->prepare($report);

        return $this->query($prepared)
            ->paginate($perPage)
            ->through(fn (Model $row): array => $this->values($row, $prepared['definitions']));
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function rows(ReportDefinition $report, int $limit = 500): Collection
    {
        $prepared = $this->prepare($report);

        return $this->query($prepared)
            ->limit($limit)
            ->get()
            ->map(fn (Model $row): array => $this->values($row, $prepared['definitions']))
            ->values();
    }

    /**
     * @param  array{source: array<string, mixed>, definitions: list<array<string, mixed>>, relations: list<string>}  $prepared
     * @return Builder<Model>
     */
    private function query(array $prepared): Builder
    {
        /** @var class-string<Model> $model */
        $model = $prepared['source']['model'];
        $keyName = (new $model)->getKeyName();

        return $model::query()
            ->with(array_values(array_unique($prepared['relations'])))
            ->orderBy($prepared['source']['order_by'])
            ->orderBy($keyName);
    }

    /**
     * @return array{source: array<string, mixed>, definitions: list<array<string, mixed>>, relations: list<string>}
     */
    private function prepare(ReportDefinition $report): array
    {
        $source = $this->catalog->source($report->source);
        $fields = $report->fields;

        if ($fields->isEmpty()) {
            throw new InvalidArgumentException('The report has no fields.');
        }

        $definitions = [];
        $relations = [];

        foreach ($fields as $field) {
            $definition = $this->catalog->field($report->source, $field->column);

            if ($definition === null) {
                throw new InvalidArgumentException("Field [{$field->column}] is not allowed for [{$report->source}].");
            }

            $definitions[] = $definition;

            if (isset($definition['relation'])) {
                $relations[] = $definition['relation'];
            }
        }

        return [
            'source' => $source,
            'definitions' => $definitions,
            'relations' => $relations,
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $definitions
     * @return array<string, mixed>
     */
    private function values(Model $row, array $definitions): array
    {
        $values = [];

        foreach ($definitions as $definition) {
            $key = $definition['key'];
            $values[$key] = $this->value($row, $definition);
        }

        return $values;
    }

    /**
     * @param  array<string, mixed>  $definition
     */
    private function value(Model $row, array $definition): mixed
    {
        if (! isset($definition['relation'])) {
            return $row->getAttribute($definition['key']);
        }

        $related = $row->getRelation($definition['relation']);
        $attribute = $definition['attribute'];

        if ($related instanceof EloquentCollection) {
            return $related->pluck($attribute)->filter()->implode(', ');
        }

        if ($related instanceof Model) {
            return $related->getAttribute($attribute);
        }

        return null;
    }
}
