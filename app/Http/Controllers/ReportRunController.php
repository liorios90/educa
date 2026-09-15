<?php

namespace App\Http\Controllers;

use App\Enums\Role;
use App\Models\ReportDefinition;
use App\Reports\ReportRunner;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ReportRunController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        $reports = ReportDefinition::query()
            ->with('roles')
            ->where('is_active', true)
            ->orderBy('name')
            ->get()
            ->filter(fn (ReportDefinition $report): bool => $user !== null && $report->isVisibleTo($user))
            ->values();

        return view('reports.index', [
            'reports' => $reports,
        ]);
    }

    public function show(Request $request, ReportDefinition $reportDefinition, ReportRunner $runner): View
    {
        $this->authorizeRun($request, $reportDefinition);

        $reportDefinition->load('fields');

        return view('reports.show', [
            'report' => $reportDefinition,
            'rows' => $runner->paginate($reportDefinition),
        ]);
    }

    public function pdf(Request $request, ReportDefinition $reportDefinition, ReportRunner $runner): Response
    {
        $this->authorizeRun($request, $reportDefinition);

        $reportDefinition->load('fields');

        $filename = Str::slug($reportDefinition->name);
        $filename = ($filename !== '' ? $filename : 'reporte').'.pdf';

        return Pdf::loadView('reports.pdf', [
            'report' => $reportDefinition,
            'rows' => $runner->rows($reportDefinition),
        ])->download($filename);
    }

    /**
     * A report designer previews any report, including one that is hidden or
     * published to other roles.
     */
    private function authorizeRun(Request $request, ReportDefinition $reportDefinition): void
    {
        $user = $request->user();

        abort_unless(
            $user !== null && ($reportDefinition->isVisibleTo($user) || $user->hasRole(Role::Sistemas)),
            404,
        );
    }
}
