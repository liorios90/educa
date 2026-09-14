<?php

namespace App\Http\Controllers;

use App\Crud\CrudDefinition;
use App\Crud\CrudRegistry;
use App\Http\Requests\UpsertCrudRecordRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class GenericCrudController extends Controller
{
    public function __construct(private CrudRegistry $registry) {}

    public function index(): View
    {
        $definition = $this->definition();

        return view('sistemas.crud.index', [
            'definition' => $definition,
            'records' => $definition->query()
                ->orderBy($definition->orderBy)
                ->orderBy('id')
                ->get(),
        ]);
    }

    public function create(): View
    {
        return view('sistemas.crud.create', [
            'definition' => $this->definition(),
            'record' => null,
        ]);
    }

    public function store(UpsertCrudRecordRequest $request): RedirectResponse
    {
        $definition = $this->definition();

        $definition->query()->create($request->safe()->only($definition->fillableNames()));

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
        ]);
    }

    public function update(UpsertCrudRecordRequest $request, string $record): RedirectResponse
    {
        $definition = $this->definition();
        $model = $definition->findOrFail((int) $record);

        $model->update($request->safe()->only($definition->fillableNames()));

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

    private function definition(): CrudDefinition
    {
        return $this->registry->get($this->registry->slugFromRouteName(request()->route()?->getName()));
    }
}
