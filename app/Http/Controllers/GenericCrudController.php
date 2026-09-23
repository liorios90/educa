<?php

namespace App\Http\Controllers;

use App\Crud\CrudDefinition;
use App\Crud\CrudRegistry;
use App\Http\Requests\UpsertCrudRecordRequest;
use App\Models\NavigationItem;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class GenericCrudController extends Controller
{
    public function __construct(private CrudRegistry $registry) {}

    public function index(): View
    {
        $definition = $this->definition();
        $query = $definition->query();
        $relations = $definition->listRelations();

        if ($relations !== []) {
            $query->with($relations);
        }

        return view('sistemas.crud.index', [
            'definition' => $definition,
            'records' => $query
                ->orderBy($definition->orderBy)
                ->orderBy('id')
                ->get(),
            'backUrl' => NavigationItem::hubBackUrlForRoute($definition->routeName('index')),
        ]);
    }

    public function create(): View
    {
        $definition = $this->definition();

        return view('sistemas.crud.create', [
            'definition' => $definition,
            'record' => null,
            'options' => $definition->selectOptions(),
        ]);
    }

    public function store(UpsertCrudRecordRequest $request): RedirectResponse
    {
        $definition = $this->definition();

        $definition->query()->create($this->attributesForPersistence($request, $definition));

        return redirect()
            ->route($definition->routeName('index'))
            ->with('status', 'crud-created');
    }

    public function edit(string $record): View
    {
        $definition = $this->definition();

        return view('sistemas.crud.edit', [
            'definition' => $definition,
            'record' => $definition->findOrFail((int) $record),
            'options' => $definition->selectOptions(),
        ]);
    }

    public function update(UpsertCrudRecordRequest $request, string $record): RedirectResponse
    {
        $definition = $this->definition();
        $model = $definition->findOrFail((int) $record);

        $model->update($this->attributesForPersistence($request, $definition));

        return redirect()
            ->route($definition->routeName('index'))
            ->with('status', 'crud-updated');
    }

    public function destroy(string $record): RedirectResponse
    {
        $definition = $this->definition();

        $definition->findOrFail((int) $record)->delete();

        return redirect()
            ->route($definition->routeName('index'))
            ->with('status', 'crud-deleted');
    }

    /**
     * @return array<string, mixed>
     */
    private function attributesForPersistence(UpsertCrudRecordRequest $request, CrudDefinition $definition): array
    {
        $attributes = $request->safe()->only($definition->fillableNames());
        $model = new $definition->model;

        if ($model->isFillable('usuario')) {
            $attributes['usuario'] = (string) $request->user()?->name;
        }

        return $attributes;
    }

    private function definition(): CrudDefinition
    {
        return $this->registry->get($this->registry->slugFromRouteName(request()->route()?->getName()));
    }
}
