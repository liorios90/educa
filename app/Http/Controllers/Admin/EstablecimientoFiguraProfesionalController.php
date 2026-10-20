<?php

namespace App\Http\Controllers\Admin;

use App\Auth\ActiveOferta;
use App\Auth\ActivePeriodo;
use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreEstablecimientoFiguraProfesionalRequest;
use App\Models\Establecimiento;
use App\Models\EstablecimientoFiguraProfesional;
use App\Models\EstablecimientoModalidadJornada;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class EstablecimientoFiguraProfesionalController extends Controller
{
    public function __construct(
        private ActiveOferta $activeOferta,
        private ActivePeriodo $activePeriodo,
    ) {}

    public function store(
        StoreEstablecimientoFiguraProfesionalRequest $request,
        EstablecimientoModalidadJornada $oferta,
    ): RedirectResponse {
        $establecimiento = Establecimiento::query()->findOrFail($request->user()->establecimiento_id);
        $this->exigirOfertaActiva($establecimiento, $oferta, $request);
        abort_if($this->activePeriodo->get($request->user()) === null, 403);

        EstablecimientoFiguraProfesional::query()->create([
            'establecimiento_id' => $establecimiento->id,
            'establecimiento_modalidad_jornada_id' => $oferta->id,
            'nombre' => trim($request->string('nombre')->toString()),
        ]);

        return redirect()
            ->route('Admin.asignaturas.edit', $oferta)
            ->with('status', 'figura-profesional-created');
    }

    public function destroy(
        Request $request,
        EstablecimientoModalidadJornada $oferta,
        EstablecimientoFiguraProfesional $figuraProfesional,
    ): RedirectResponse {
        abort_unless($request->user()?->hasRole(Role::Admin) === true, 403);

        $establecimiento = Establecimiento::query()->findOrFail($request->user()->establecimiento_id);
        $this->exigirOfertaActiva($establecimiento, $oferta, $request);
        abort_unless(
            (int) $figuraProfesional->establecimiento_id === (int) $establecimiento->id
            && (int) $figuraProfesional->establecimiento_modalidad_jornada_id === (int) $oferta->id,
            404,
        );

        if ($figuraProfesional->asignaturas()->exists()
            || $figuraProfesional->asignaturasDeMalla()->exists()
            || $figuraProfesional->matriculas()->exists()) {
            throw ValidationException::withMessages([
                'figura_profesional' => 'No puedes quitar una especialidad que ya tiene módulos o alumnos matriculados.',
            ]);
        }

        $figuraProfesional->delete();

        return redirect()
            ->route('Admin.asignaturas.edit', $oferta)
            ->with('status', 'figura-profesional-deleted');
    }

    private function exigirOfertaActiva(
        Establecimiento $establecimiento,
        EstablecimientoModalidadJornada $oferta,
        Request $request,
    ): void {
        abort_unless(
            (int) $oferta->establecimientoModalidad?->establecimiento_id === (int) $establecimiento->id
            && (int) $this->activeOferta->get($request->user())?->id === (int) $oferta->id,
            404,
        );
    }
}
