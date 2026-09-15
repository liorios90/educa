<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpsertReportDefinitionRequest;
use App\Models\ReportDefinition;
use App\Reports\ReportCatalog;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Spatie\Permission\Models\Role;

class ReportDefinitionController extends Controller
{
    public function __construct(private ReportCatalog $catalog) {}

    public function index(): View
    {
        return view('sistemas.reports.index', [
            'reports' => ReportDefinition::query()
                ->with('roles')
                ->withCount('fields')
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function create(): View
    {
        return view('sistemas.reports.create', $this->formData());
    }

    public function store(UpsertReportDefinitionRequest $request): RedirectResponse
    {
        $report = ReportDefinition::create([
            ...$request->safe()->only($this->reportAttributes()),
            'user_id' => $request->user()?->id,
        ]);

        $this->syncFields($report, $request->validated('fields'));
        $this->syncRoles($report, $request);

        return redirect()
            ->route('sistemas.reports.index')
            ->with('status', 'report-created');
    }

    public function edit(ReportDefinition $reportDefinition): View
    {
        $reportDefinition->load(['fields', 'roles']);

        return view('sistemas.reports.edit', [
            ...$this->formData(),
            'report' => $reportDefinition,
        ]);
    }

    public function update(UpsertReportDefinitionRequest $request, ReportDefinition $reportDefinition): RedirectResponse
    {
        $reportDefinition->update($request->safe()->only($this->reportAttributes()));
        $this->syncFields($reportDefinition, $request->validated('fields'));
        $this->syncRoles($reportDefinition, $request);

        return redirect()
            ->route('sistemas.reports.index')
            ->with('status', 'report-updated');
    }

    public function destroy(ReportDefinition $reportDefinition): RedirectResponse
    {
        $reportDefinition->delete();

        return redirect()
            ->route('sistemas.reports.index')
            ->with('status', 'report-deleted');
    }

    /**
     * @return list<string>
     */
    private function reportAttributes(): array
    {
        return [
            'name',
            'source',
            'is_active',
            'visible_to_all',
            'layout',
            'table_border_width',
            'table_border_color',
            'table_header',
            'table_header_background',
            'table_striped',
            'table_font_size',
            'table_cell_padding',
        ];
    }

    /**
     * @return array{sources: array<string, mixed>, roles: Collection<int, Role>}
     */
    private function formData(): array
    {
        return [
            'sources' => $this->catalog->designerSources(),
            'roles' => Role::query()->orderBy('name')->get(),
        ];
    }

    /**
     * @param  list<array{column: string, label: string, label_x?: int, label_y?: int, value_x?: int, value_y?: int}>  $fields
     */
    private function syncFields(ReportDefinition $report, array $fields): void
    {
        $report->fields()->delete();

        foreach (array_values($fields) as $index => $field) {
            $report->fields()->create([
                'column' => $field['column'],
                'label' => $field['label'],
                'sort_order' => $index,
                'label_x' => $field['label_x'] ?? 4,
                'label_y' => $field['label_y'] ?? min(8 + ($index * 14), 86),
                'value_x' => $field['value_x'] ?? 32,
                'value_y' => $field['value_y'] ?? min(8 + ($index * 14), 86),
            ]);
        }
    }

    private function syncRoles(ReportDefinition $report, UpsertReportDefinitionRequest $request): void
    {
        if ($request->boolean('visible_to_all')) {
            $report->roles()->sync([]);

            return;
        }

        $report->roles()->sync($request->validated('roles'));
    }
}
