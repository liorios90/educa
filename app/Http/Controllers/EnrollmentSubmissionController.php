<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreEnrollmentSubmissionRequest;
use App\Models\EnrollmentField;
use App\Models\EnrollmentForm;
use App\Models\EnrollmentSubmission;
use App\Models\EstablecimientoPeriodo;
use App\Services\EnrollmentFormManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

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
            ->get();

        return view('matriculas.index', [
            'periodo' => $periodo,
            'submissions' => $submissions,
        ]);
    }

    public function create(Request $request): View
    {
        [$form, $periodo] = $this->formFor($request);

        return view('matriculas.create', [
            'form' => $form,
            'periodo' => $periodo,
            'submission' => null,
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

        return view('matriculas.create', [
            'form' => $form,
            'periodo' => $periodo,
            'submission' => $enrollmentSubmission,
        ]);
    }

    public function store(StoreEnrollmentSubmissionRequest $request): RedirectResponse
    {
        [$form] = $this->formFor($request);

        return $this->saveSubmission($request, $form, new EnrollmentSubmission());
    }

    public function update(StoreEnrollmentSubmissionRequest $request, EnrollmentSubmission $enrollmentSubmission): RedirectResponse
    {
        $periodo = $this->periodFor($request);
        abort_unless($enrollmentSubmission->user_id === $request->user()->id, 404);
        $form = $enrollmentSubmission->form;
        abort_unless($form->establecimiento_id === $request->user()->establecimiento_id, 404);
        abort_unless($form->periodo_inicio?->toDateString() === $periodo->fecha_inicio->toDateString(), 404);
        abort_unless($form->periodo_fin?->toDateString() === $periodo->fecha_fin->toDateString(), 404);

        return $this->saveSubmission($request, $form, $enrollmentSubmission);
    }

    private function saveSubmission(StoreEnrollmentSubmissionRequest $request, EnrollmentForm $form, EnrollmentSubmission $submission): RedirectResponse
    {
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

        $submission->fill([
            'enrollment_form_id' => $form->id,
            'user_id' => $request->user()->id,
            'student_name' => implode(' ', array_filter($studentNameParts)),
            'responses' => $responses,
            'schema_snapshot' => $snapshot,
        ])->save();

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
}
