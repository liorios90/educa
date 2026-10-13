<?php

namespace App\Services;

use App\Models\EnrollmentForm;
use App\Models\Establecimiento;
use App\Models\EstablecimientoPeriodo;
use Illuminate\Support\Facades\DB;

class EnrollmentFormManager
{
    /** @var array<string, array<string, array<string, string>>> */
    private const array TEMPLATE = [
        'Datos representante' => [
            'Información representante económico' => ['Correo electrónico' => 'email', 'Celular' => 'tel', 'Sector/barrio' => 'text', 'Dirección domicilio' => 'text', 'Cédula o pasaporte' => 'text'],
            'Información del representante legal' => ['Dirección de domicilio' => 'text', 'Correo electrónico' => 'email', 'Nacionalidad' => 'text', 'Teléfono convencional' => 'tel', 'Teléfono celular del representante' => 'tel', 'Cédula o pasaporte del representante' => 'text', 'Apellidos completos del representante' => 'text', 'Nombres completos del representante' => 'text'],
        ],
        'Datos familiares' => [
            'Datos del padre' => ['¿Es representante legal del estudiante?' => 'yesno', '¿Vive con el estudiante?' => 'yesno', 'Dirección de domicilio' => 'text', 'Correo electrónico personal' => 'email', 'Teléfono convencional' => 'tel', 'Teléfono celular' => 'tel', 'Estado civil' => 'text', 'Fecha de nacimiento' => 'date', 'Nacionalidad' => 'text', 'Cédula o pasaporte' => 'text', 'Apellidos completos' => 'text', 'Nombres completos' => 'text'],
            'Datos de la madre' => ['¿Es representante legal del estudiante?' => 'yesno', '¿Vive con el estudiante?' => 'yesno', 'Dirección de domicilio' => 'text', 'Correo electrónico personal' => 'email', 'Teléfono convencional' => 'tel', 'Teléfono celular' => 'tel', 'Estado civil' => 'text', 'Fecha de nacimiento' => 'date', 'Nacionalidad' => 'text', 'Cédula o pasaporte' => 'text', 'Apellidos completos' => 'text', 'Nombres completos' => 'text'],
        ],
        'Datos del estudiante' => [
            'Información del estudiante' => ['Teléfono convencional' => 'tel', 'Teléfono celular' => 'tel', 'Grado o curso al que aplica' => 'text', 'Fecha en que aplica la matrícula' => 'date', 'Nacionalidad' => 'text', 'Lugar de nacimiento' => 'text', 'Fecha de nacimiento' => 'date', 'Cédula o pasaporte' => 'text', 'Apellidos completos' => 'text', 'Nombres completos' => 'text'],
        ],
    ];

    public function forPeriod(int $establecimientoId, EstablecimientoPeriodo $periodo): EnrollmentForm
    {
        return DB::transaction(function () use ($establecimientoId, $periodo): EnrollmentForm {
            Establecimiento::query()->whereKey($establecimientoId)->lockForUpdate()->firstOrFail();
            $periodoInicio = $periodo->fecha_inicio->toDateString();
            $periodoFin = $periodo->fecha_fin->toDateString();

            $form = EnrollmentForm::query()
                ->where('establecimiento_id', $establecimientoId)
                ->whereDate('periodo_inicio', $periodoInicio)
                ->whereDate('periodo_fin', $periodoFin)
                ->where('is_active', true)
                ->lockForUpdate()
                ->first();

            if ($form === null) {
                $form = EnrollmentForm::query()
                    ->where('establecimiento_id', $establecimientoId)
                    ->whereDate('periodo_inicio', $periodoInicio)
                    ->whereDate('periodo_fin', $periodoFin)
                    ->lockForUpdate()
                    ->latest('id')
                    ->first();

                if ($form !== null) {
                    $form = $this->activate($form);
                }
            }

            if ($form === null) {
                $form = EnrollmentForm::query()
                    ->where('establecimiento_id', $establecimientoId)
                    ->whereNull('periodo_inicio')
                    ->whereNull('periodo_fin')
                    ->lockForUpdate()
                    ->first();

                if ($form !== null) {
                    $form->update([
                        'periodo_inicio' => $periodoInicio,
                        'periodo_fin' => $periodoFin,
                        'is_active' => true,
                    ]);
                }
            }

            if ($form === null) {
                $form = EnrollmentForm::query()->create([
                    'establecimiento_id' => $establecimientoId,
                    'periodo_inicio' => $periodoInicio,
                    'periodo_fin' => $periodoFin,
                    'title' => 'Formulario de matrícula',
                    'is_active' => true,
                ]);
            }

            if ($form->sections()->exists()) {
                return $form;
            }

            $previousForm = $this->sourceForPeriod($establecimientoId, $periodoInicio, $periodoFin, $form->id);

            if ($previousForm !== null && $previousForm->sections->isNotEmpty()) {
                $this->copyStructure($previousForm, $form);
            } else {
                $this->createDefaultStructure($form);
            }

            return $form;
        }, attempts: 3);
    }

    public function createForPeriod(int $establecimientoId, EstablecimientoPeriodo $periodo, string $title): EnrollmentForm
    {
        return DB::transaction(function () use ($establecimientoId, $periodo, $title): EnrollmentForm {
            Establecimiento::query()->whereKey($establecimientoId)->lockForUpdate()->firstOrFail();
            $periodoInicio = $periodo->fecha_inicio->toDateString();
            $periodoFin = $periodo->fecha_fin->toDateString();
            $form = EnrollmentForm::query()->create([
                'establecimiento_id' => $establecimientoId,
                'periodo_inicio' => $periodoInicio,
                'periodo_fin' => $periodoFin,
                'title' => $title,
                'is_active' => false,
            ]);

            $source = $this->sourceForPeriod($establecimientoId, $periodoInicio, $periodoFin, $form->id);
            if ($source !== null && $source->sections->isNotEmpty()) {
                $this->copyStructure($source, $form);
            } else {
                $this->createDefaultStructure($form);
            }

            $hasActiveForm = EnrollmentForm::query()
                ->where('establecimiento_id', $establecimientoId)
                ->whereDate('periodo_inicio', $periodoInicio)
                ->whereDate('periodo_fin', $periodoFin)
                ->where('is_active', true)
                ->exists();

            return $hasActiveForm ? $form : $this->activate($form);
        }, attempts: 3);
    }

    public function activate(EnrollmentForm $form): EnrollmentForm
    {
        return DB::transaction(function () use ($form): EnrollmentForm {
            Establecimiento::query()->whereKey($form->establecimiento_id)->lockForUpdate()->firstOrFail();

            EnrollmentForm::query()
                ->where('establecimiento_id', $form->establecimiento_id)
                ->whereDate('periodo_inicio', $form->periodo_inicio)
                ->whereDate('periodo_fin', $form->periodo_fin)
                ->whereKeyNot($form->id)
                ->update(['is_active' => false]);

            $form->update(['is_active' => true]);

            return $form->refresh();
        }, attempts: 3);
    }

    private function sourceForPeriod(int $establecimientoId, string $periodoInicio, string $periodoFin, int $exceptId): ?EnrollmentForm
    {
        return EnrollmentForm::query()
            ->where('establecimiento_id', $establecimientoId)
            ->whereKeyNot($exceptId)
            ->whereNotNull('periodo_inicio')
            ->where(function ($query) use ($periodoInicio, $periodoFin): void {
                $query->where(function ($periodQuery) use ($periodoInicio, $periodoFin): void {
                    $periodQuery->whereDate('periodo_inicio', $periodoInicio)
                        ->whereDate('periodo_fin', $periodoFin);
                })->orWhereDate('periodo_inicio', '<', $periodoInicio);
            })
            ->with(['sections.fields', 'sections.subsections.fields'])
            ->orderByDesc('periodo_inicio')
            ->orderByDesc('periodo_fin')
            ->orderByDesc('is_active')
            ->orderByDesc('id')
            ->first();
    }

    private function copyStructure(EnrollmentForm $source, EnrollmentForm $destination): void
    {
        foreach ($source->sections as $sourceSection) {
            $section = $destination->sections()->create([
                'title' => $sourceSection->title,
                'position' => $sourceSection->position,
            ]);

            foreach ($sourceSection->fields as $field) {
                $section->fields()->create([
                    'label' => $field->label,
                    'type' => $field->type,
                    'options' => $field->options,
                    'is_required' => $field->is_required,
                    'position' => $field->position,
                ]);
            }

            foreach ($sourceSection->subsections as $sourceSubsection) {
                $subsection = $section->subsections()->create([
                    'enrollment_form_id' => $destination->id,
                    'title' => $sourceSubsection->title,
                    'position' => $sourceSubsection->position,
                ]);

                foreach ($sourceSubsection->fields as $field) {
                    $subsection->fields()->create([
                        'label' => $field->label,
                        'type' => $field->type,
                        'options' => $field->options,
                        'is_required' => $field->is_required,
                        'position' => $field->position,
                    ]);
                }
            }
        }
    }

    private function createDefaultStructure(EnrollmentForm $form): void
    {
        foreach (self::TEMPLATE as $sectionTitle => $subsections) {
            $section = $form->sections()->create([
                'title' => $sectionTitle,
                'position' => $form->sections()->count() + 1,
            ]);

            foreach ($subsections as $subsectionTitle => $fieldDefinitions) {
                $subsection = $section->subsections()->create([
                    'enrollment_form_id' => $form->id,
                    'title' => $subsectionTitle,
                    'position' => $section->subsections()->count() + 1,
                ]);

                foreach ($fieldDefinitions as $label => $type) {
                    $subsection->fields()->create([
                        'label' => $label,
                        'type' => $type === 'yesno' ? 'select' : $type,
                        'options' => $type === 'yesno' ? ['Sí', 'No'] : null,
                        'position' => $subsection->fields()->count() + 1,
                    ]);
                }
            }
        }
    }
}
