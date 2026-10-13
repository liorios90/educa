<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreEnrollmentSubmissionRequest;
use App\Models\Alumno;
use App\Models\EnrollmentField;
use App\Models\EnrollmentForm;
use App\Models\EnrollmentSubmission;
use App\Models\EstablecimientoPeriodo;
use App\Models\Padre;
use App\Services\EnrollmentFormManager;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Illuminate\Support\Str;

class EnrollmentSubmissionController extends Controller
{
    public function __construct(private EnrollmentFormManager $forms) {}

    public function index(Request $request): View
    {
        [, $periodo] = $this->formFor($request);
        $submissions = EnrollmentSubmission::query()
            ->where('user_id', $request->user()->id)
            ->whereHas('form', fn ($query) => $query
                ->where('establecimiento_id', $request->user()->establecimiento_id)
                ->whereDate('periodo_inicio', $periodo->fecha_inicio)
                ->whereDate('periodo_fin', $periodo->fecha_fin))
            ->latest('submitted_at')
            ->with('alumno.persona')
            ->get();

        return view('matriculas.index', [
            'periodo' => $periodo,
            'submissions' => $submissions,
        ]);
    }

    public function create(Request $request): View|RedirectResponse
    {
        [$form, $periodo] = $this->formFor($request);
        $children = $this->childrenFor($request);
        $selectedChild = $this->selectedChild($request, $children);

        if ($selectedChild !== null) {
            $existingSubmission = $this->submissionForChild($selectedChild, $periodo);
            if ($existingSubmission !== null) {
                return redirect()->route('matriculas.edit', $existingSubmission);
            }
        }

        return view('matriculas.create', [
            'form' => $form,
            'periodo' => $periodo,
            'submission' => null,
            'children' => $children,
            'selectedChild' => $selectedChild,
            'prefillValues' => $selectedChild === null ? [] : $this->prefillStudentFields($form, $selectedChild),
        ]);
    }

    public function edit(Request $request, EnrollmentSubmission $enrollmentSubmission): View
    {
        $periodo = $this->periodFor($request);
        abort_unless($enrollmentSubmission->user_id === $request->user()->id, 404);
        $form = $enrollmentSubmission->form;
        abort_unless($form->establecimiento_id === $request->user()->establecimiento_id, 404);
        abort_unless($form->periodo_inicio?->toDateString() === $periodo->fecha_inicio->toDateString(), 404);
        abort_unless($form->periodo_fin?->toDateString() === $periodo->fecha_fin->toDateString(), 404);
        $form->load('sections.subsections.fields', 'sections.fields');
        $children = $this->childrenFor($request);
        $selectedChild = $enrollmentSubmission->alumno_id === null
            ? $this->selectedChild($request, $children)
            : $children->firstWhere('id', $enrollmentSubmission->alumno_id);

        return view('matriculas.create', [
            'form' => $form,
            'periodo' => $periodo,
            'submission' => $enrollmentSubmission,
            'children' => $children,
            'selectedChild' => $selectedChild,
            'prefillValues' => $selectedChild === null ? [] : $this->prefillStudentFields($form, $selectedChild),
        ]);
    }

    public function store(StoreEnrollmentSubmissionRequest $request): RedirectResponse
    {
        [$form, $periodo] = $this->formFor($request);
        $children = $this->childrenFor($request);
        $validated = $request->validate(['alumno_id' => ['required', 'integer']]);
        $child = $children->firstWhere('id', (int) $validated['alumno_id']);
        abort_if($child === null, 404);

        $existingSubmission = $this->submissionForChild($child, $periodo);
        if ($existingSubmission !== null) {
            return redirect()->route('matriculas.edit', $existingSubmission);
        }

        return $this->saveSubmission($request, $form, $periodo, $child, new EnrollmentSubmission());
    }

    public function update(StoreEnrollmentSubmissionRequest $request, EnrollmentSubmission $enrollmentSubmission): RedirectResponse
    {
        $periodo = $this->periodFor($request);
        abort_unless($enrollmentSubmission->user_id === $request->user()->id, 404);
        $form = $enrollmentSubmission->form;
        abort_unless($form->establecimiento_id === $request->user()->establecimiento_id, 404);
        abort_unless($form->periodo_inicio?->toDateString() === $periodo->fecha_inicio->toDateString(), 404);
        abort_unless($form->periodo_fin?->toDateString() === $periodo->fecha_fin->toDateString(), 404);

        $children = $this->childrenFor($request);
        $validated = $request->validate(['alumno_id' => ['required', 'integer']]);
        $child = $children->firstWhere('id', (int) $validated['alumno_id']);
        abort_if($child === null, 404);
        abort_unless($enrollmentSubmission->alumno_id === null || $enrollmentSubmission->alumno_id === $child->id, 404);

        return $this->saveSubmission($request, $form, $periodo, $child, $enrollmentSubmission);
    }

    private function saveSubmission(StoreEnrollmentSubmissionRequest $request, EnrollmentForm $form, EstablecimientoPeriodo $periodo, Alumno $child, EnrollmentSubmission $submission): RedirectResponse
    {
        $duplicate = $this->submissionForChild($child, $periodo, $submission->exists ? $submission->id : null);
        if ($duplicate !== null) {
            return redirect()->route('matriculas.edit', $duplicate);
        }

        $fields = EnrollmentField::query()
            ->with('section.parent')
            ->whereHas('section', fn ($query) => $query->where('enrollment_form_id', $form->id))
            ->get();
        $rules = ['responses' => ['required', 'array']];

        foreach ($fields as $field) {
            $fieldRules = [$field->is_required ? 'required' : 'nullable', 'string', 'max:2000'];
            if ($field->type === 'email') {
                $fieldRules[] = 'email';
            } elseif ($field->type === 'date') {
                $fieldRules[] = 'date';
            } elseif ($field->type === 'select') {
                $fieldRules[] = Rule::in($field->options ?? []);
            }
            $rules['responses.'.$field->id] = $fieldRules;
        }

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            $firstErrorKey = array_key_first($validator->errors()->messages());
            preg_match('/^responses\.(\d+)$/', (string) $firstErrorKey, $matches);
            $field = isset($matches[1]) ? $fields->firstWhere('id', (int) $matches[1]) : null;
            $section = $field?->section;

            return back()
                ->withErrors($validator)
                ->withInput()
                ->with('activeEnrollmentSectionId', $section?->parent_id ?? $section?->id)
                ->with('activeEnrollmentSubsectionId', $section?->parent_id === null ? null : $section?->id);
        }

        $validated = $validator->validated();
        $responses = [];
        $snapshot = [];
        $studentNameParts = [];

        foreach ($fields as $field) {
            $value = $validated['responses'][$field->id] ?? null;
            $responses[$field->id] = $value;
            $snapshot[$field->id] = ['label' => $field->label];

            $rootSectionTitle = $field->section->parent?->title ?? $field->section->title;
            if (str_contains(mb_strtolower($rootSectionTitle), 'estudiante') && filled($value)) {
                $normalizedLabel = mb_strtolower($field->label);
                if (str_contains($normalizedLabel, 'nombre')) {
                    $studentNameParts[1] = $value;
                } elseif (str_contains($normalizedLabel, 'apellido')) {
                    $studentNameParts[0] = $value;
                }
            }
        }

        $studentName = implode(' ', array_filter($studentNameParts));
        if ($studentName === '') {
            $studentName = trim(($child->persona->apellidos ?? '').' '.($child->persona->nombres ?? ''));
        }

        try {
            $submission->fill([
            'enrollment_form_id' => $form->id,
            'user_id' => $request->user()->id,
            'alumno_id' => $child->id,
            'periodo_inicio' => $periodo->fecha_inicio->toDateString(),
            'periodo_fin' => $periodo->fecha_fin->toDateString(),
            'student_name' => $studentName,
            'responses' => $responses,
            'schema_snapshot' => $snapshot,
            ])->save();
        } catch (QueryException $exception) {
            if (! str_contains($exception->getMessage(), 'enrollment_submissions_student_period_unique')) {
                throw $exception;
            }

            $duplicate = $this->submissionForChild($child, $periodo);

            return $duplicate === null
                ? redirect()->route('matriculas.index')
                : redirect()->route('matriculas.edit', $duplicate);
        }

        return redirect()->route('matriculas.index')->with('status', $submission->wasRecentlyCreated ? 'matricula-submitted' : 'matricula-updated');
    }

    /** @return array{EnrollmentForm, EstablecimientoPeriodo} */
    private function formFor(Request $request): array
    {
        abort_if($request->user()->establecimiento_id === null, 403);

        $periodo = $this->periodFor($request);
        $form = $this->forms->forPeriod($request->user()->establecimiento_id, $periodo);
        $form->load('sections.subsections.fields', 'sections.fields');

        return [$form, $periodo];
    }

    private function periodFor(Request $request): EstablecimientoPeriodo
    {
        abort_if($request->user()->establecimiento_id === null, 403);

        return EstablecimientoPeriodo::query()
            ->activo()
            ->where('establecimiento_id', $request->user()->establecimiento_id)
            ->orderByDesc('fecha_inicio')
            ->orderByDesc('fecha_fin')
            ->orderByDesc('id')
            ->firstOrFail();
    }

    /** @return \Illuminate\Database\Eloquent\Collection<int, Alumno> */
    private function childrenFor(Request $request): \Illuminate\Database\Eloquent\Collection
    {
        $personaId = $request->user()->persona()->value('id');
        if ($personaId === null) {
            return Alumno::query()->whereRaw('1 = 0')->get();
        }

        $parent = Padre::query()->where('persona_id', $personaId)->first();
        if ($parent === null) {
            return Alumno::query()->whereRaw('1 = 0')->get();
        }

        return $parent->alumnos()
            ->with('persona.nacionalidad')
            ->whereHas('persona', fn ($query) => $query->where('establecimiento_id', $request->user()->establecimiento_id))
            ->join('personas', 'personas.id', '=', 'alumnos.persona_id')
            ->orderBy('personas.apellidos')
            ->orderBy('personas.nombres')
            ->orderBy('alumnos.id')
            ->select('alumnos.*')
            ->get();
    }

    /** @param \Illuminate\Database\Eloquent\Collection<int, Alumno> $children */
    private function selectedChild(Request $request, \Illuminate\Database\Eloquent\Collection $children): ?Alumno
    {
        if ($children->count() === 1) {
            return $children->first();
        }

        $childId = $request->query('alumno_id');

        return is_numeric($childId) ? $children->firstWhere('id', (int) $childId) : null;
    }

    private function submissionForChild(Alumno $child, EstablecimientoPeriodo $periodo, ?int $exceptId = null): ?EnrollmentSubmission
    {
        return EnrollmentSubmission::query()
            ->where('alumno_id', $child->id)
            ->whereDate('periodo_inicio', $periodo->fecha_inicio)
            ->whereDate('periodo_fin', $periodo->fecha_fin)
            ->when($exceptId !== null, fn ($query) => $query->whereKeyNot($exceptId))
            ->first();
    }

    /** @return array<int, string> */
    private function prefillStudentFields(EnrollmentForm $form, Alumno $child): array
    {
        $values = [];
        $student = $child->persona;
        $studentSection = collect($form->sections)->first(fn ($section): bool => str_contains(Str::of($section->title)->ascii()->lower()->toString(), 'estudiante'));

        if ($studentSection === null || $student === null) {
            return $values;
        }

        $fields = $studentSection->fields->concat($studentSection->subsections->flatMap->fields);
        foreach ($fields as $field) {
            $label = Str::of($field->label)->ascii()->lower()->squish()->toString();
            $value = match (true) {
                str_contains($label, 'apellido') => $student->apellidos,
                str_contains($label, 'nombre') => $student->nombres,
                str_contains($label, 'cedula') || str_contains($label, 'pasaporte') => $student->identificacion,
                str_contains($label, 'nacionalidad') => $student->nacionalidad?->nombre,
                str_contains($label, 'lugar de nacimiento') => $student->ciudad_nacimiento,
                str_contains($label, 'fecha de nacimiento') => $student->fecha_nacimiento?->format('Y-m-d'),
                str_contains($label, 'telefono convencional') => $student->telefono1,
                str_contains($label, 'telefono celular') => $student->telefono2,
                default => null,
            };

            if (filled($value)) {
                $values[$field->id] = (string) $value;
            }
        }

        return $values;
    }
}
