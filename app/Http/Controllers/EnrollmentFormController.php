<?php

namespace App\Http\Controllers;

use App\Auth\ActiveOferta;
use App\Http\Requests\StoreEnrollmentFieldRequest;
use App\Http\Requests\StoreEnrollmentSectionRequest;
use App\Models\EnrollmentField;
use App\Models\EnrollmentForm;
use App\Models\EnrollmentSection;
use App\Models\EstablecimientoPeriodo;
use App\Services\EnrollmentFormManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EnrollmentFormController extends Controller
{
    public function __construct(
        private ActiveOferta $activeOferta,
        private EnrollmentFormManager $forms,
    ) {}

    public function index(Request $request): View|RedirectResponse
    {
        $user = $request->user();
        abort_if($user->establecimiento_id === null, 403);

        if ($this->activeOferta->needsSelection($user)) {
            return redirect()->guest(route('oferta.select'));
        }

        $periodos = EstablecimientoPeriodo::query()
            ->where('establecimiento_id', $user->establecimiento_id)
            ->orderByDesc('fecha_inicio')
            ->orderByDesc('fecha_fin')
            ->get()
            ->unique(fn (EstablecimientoPeriodo $periodo): string => $this->periodKey($periodo->fecha_inicio->toDateString(), $periodo->fecha_fin->toDateString()))
            ->values();
        $formsByPeriod = EnrollmentForm::query()
            ->where('establecimiento_id', $user->establecimiento_id)
            ->withCount('submissions')
            ->orderByDesc('periodo_inicio')
            ->orderByDesc('periodo_fin')
            ->orderByDesc('id')
            ->get()
            ->groupBy(fn (EnrollmentForm $form): string => $this->periodKey($form->periodo_inicio?->toDateString() ?? '', $form->periodo_fin?->toDateString() ?? ''));

        return view('admin.matriculas.forms', [
            'periodos' => $periodos,
            'formsByPeriod' => $formsByPeriod,
        ]);
    }

    public function storeForm(Request $request): RedirectResponse
    {
        $user = $request->user();
        abort_if($user->establecimiento_id === null, 403);
        $validated = $request->validate([
            'periodo_id' => ['required', 'integer'],
            'title' => ['required', 'string', 'max:120'],
        ]);
        $periodo = EstablecimientoPeriodo::query()
            ->where('establecimiento_id', $user->establecimiento_id)
            ->findOrFail($validated['periodo_id']);
        $form = $this->forms->createForPeriod($user->establecimiento_id, $periodo, $validated['title']);

        return redirect()->route('Admin.matriculas.forms.edit', $form)->with('status', 'matricula-form-created');
    }

    public function edit(Request $request, EnrollmentForm $enrollmentForm): View
    {
        $form = $this->ownedForm($request, $enrollmentForm);
        $form->load(['sections.subsections.fields', 'sections.fields']);

        return view('admin.matriculas.index', [
            'form' => $form,
            'periodo' => EstablecimientoPeriodo::query()
                ->where('establecimiento_id', $form->establecimiento_id)
                ->whereDate('fecha_inicio', $form->periodo_inicio)
                ->whereDate('fecha_fin', $form->periodo_fin)
                ->first(),
            'submissionsCount' => $form->submissions()->count(),
        ]);
    }

    public function update(Request $request, EnrollmentForm $enrollmentForm): RedirectResponse
    {
        $form = $this->ownedForm($request, $enrollmentForm);
        $form->update($request->validate(['title' => ['required', 'string', 'max:120']]));

        return back()->with('status', 'matricula-form-updated');
    }

    public function activate(Request $request, EnrollmentForm $enrollmentForm): RedirectResponse
    {
        $this->forms->activate($this->ownedForm($request, $enrollmentForm));

        return back()->with('status', 'matricula-form-activated');
    }

    public function destroyForm(Request $request, EnrollmentForm $enrollmentForm): RedirectResponse
    {
        $form = $this->ownedForm($request, $enrollmentForm);

        if ($form->submissions()->exists()) {
            return back()->with('status', 'matricula-form-has-submissions');
        }

        $form->delete();

        return back()->with('status', 'matricula-form-deleted');
    }

    public function storeSection(StoreEnrollmentSectionRequest $request, EnrollmentForm $enrollmentForm): RedirectResponse
    {
        $form = $this->ownedForm($request, $enrollmentForm);
        $parentId = $request->validated('parent_id');

        if ($parentId !== null) {
            $form->sections()->findOrFail($parentId);
        }

        $position = EnrollmentSection::query()
            ->where('enrollment_form_id', $form->id)
            ->where('parent_id', $parentId)
            ->max('position') + 1;
        $section = $form->sections()->create([
            'parent_id' => $parentId,
            'title' => $request->validated('title'),
            'position' => $position,
        ]);

        return back()
            ->with('status', 'matricula-section-created')
            ->with('activeSectionId', $parentId ?? $section->id)
            ->with('activeSubsectionId', $parentId === null ? null : $section->id);
    }

    public function storeField(StoreEnrollmentFieldRequest $request, EnrollmentForm $enrollmentForm, EnrollmentSection $section): RedirectResponse
    {
        $form = $this->ownedForm($request, $enrollmentForm);
        abort_unless($section->enrollment_form_id === $form->id, 404);
        $validated = $request->validated();
        $options = $validated['type'] === 'select'
            ? collect(preg_split('/\R/', $validated['options'] ?? '') ?: [])->map(fn (string $option): string => trim($option))->filter()->unique()->values()->all()
            : null;

        abort_if($validated['type'] === 'select' && count($options) < 2, 422, 'Agrega al menos dos opciones para este campo.');

        $section->fields()->create([
            'label' => $validated['label'],
            'type' => $validated['type'],
            'options' => $options,
            'is_required' => $request->boolean('is_required'),
            'position' => $section->fields()->max('position') + 1,
        ]);

        return back()
            ->with('status', 'matricula-field-created')
            ->with('activeSectionId', $section->parent_id ?? $section->id)
            ->with('activeSubsectionId', $section->parent_id === null ? null : $section->id);
    }

    public function destroySection(Request $request, EnrollmentSection $section): RedirectResponse
    {
        $this->ownedForm($request, $section->form);
        $section->delete();

        return back()->with('status', 'matricula-section-deleted');
    }

    public function destroyField(Request $request, EnrollmentField $field): RedirectResponse
    {
        $this->ownedForm($request, $field->section->form);
        $field->delete();

        return back()->with('status', 'matricula-field-deleted');
    }

    public function submissions(Request $request, EnrollmentForm $enrollmentForm): View
    {
        $form = $this->ownedForm($request, $enrollmentForm);
        $submissions = $form->submissions()->with('user')->latest('submitted_at')->paginate(20);

        return view('admin.matriculas.submissions', [
            'form' => $form,
            'periodo' => EstablecimientoPeriodo::query()
                ->where('establecimiento_id', $form->establecimiento_id)
                ->whereDate('fecha_inicio', $form->periodo_inicio)
                ->whereDate('fecha_fin', $form->periodo_fin)
                ->first(),
            'submissions' => $submissions,
        ]);
    }

    private function ownedForm(Request $request, EnrollmentForm $form): EnrollmentForm
    {
        abort_unless($request->user()->establecimiento_id === $form->establecimiento_id, 404);

        return $form;
    }

    private function periodKey(string $periodoInicio, string $periodoFin): string
    {
        return $periodoInicio.'|'.$periodoFin;
    }
}
