<?php

namespace App\Http\Controllers;

use App\Auth\ActiveDocenteContexto;
use App\Enums\EscalaCalificacion;
use App\Exceptions\GeminiCredentialsMissingException;
use App\Exceptions\GeminiQuotaExceededException;
use App\Http\Requests\SelectDocenteContextoRequest;
use App\Http\Requests\StoreDiagnosticoEstudianteRequest;
use App\Http\Requests\StoreDiagnosticoGrupoRequest;
use App\Models\Establecimiento;
use App\Services\AsignacionesDocente;
use App\Services\AsistenteCupo;
use App\Services\DiagnosticoEstudianteService;
use App\Services\GraficoNotasBarras;
use App\Services\HistorialAcademico;
use App\Services\HistorialAcademicoEstudiante;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DiagnosticoEstudianteController extends Controller
{
    public function __construct(
        private AsignacionesDocente $asignaciones,
        private ActiveDocenteContexto $contexto,
        private HistorialAcademicoEstudiante $historiales,
        private DiagnosticoEstudianteService $diagnosticos,
        private AsistenteCupo $cupo,
        private GraficoNotasBarras $graficoBarras,
    ) {}

    public function index(Request $request): View
    {
        $user = $request->user();
        $actual = $this->contexto->get($user);
        $establecimiento = $this->establecimiento($request);

        return view('docente.diagnostico', [
            'aulas' => $this->asignaciones->aulasSelector($user),
            'contexto' => $actual,
            'alumnos' => $actual === null ? collect() : $this->asignaciones->alumnos($actual),
            'seleccion' => [
                'aula' => old('establecimiento_aula_id', $actual?->aula->id),
                'asignatura' => old('establecimiento_asignatura_id', $actual?->asignatura->id),
                'ciclo' => old('establecimiento_periodo_ciclo_id', $actual?->ciclo->id),
                'parcial' => old('parcial_numero', $actual?->parcialNumero),
            ],
            'asistenteListo' => $establecimiento->tieneCredencialesAsistente(),
            'uso' => $this->cupo->resumen($establecimiento),
        ]);
    }

    public function seleccionar(SelectDocenteContextoRequest $request): RedirectResponse
    {
        $this->contexto->set($request->contexto());

        return redirect()->route('Docente.diagnostico.index');
    }

    public function store(StoreDiagnosticoEstudianteRequest $request): RedirectResponse
    {
        $establecimiento = $this->establecimiento($request);
        $historial = $this->historiales->de($request->alumno(), $request->contexto(), $establecimiento);

        try {
            $this->cupo->exigirDisponible($establecimiento);
            $diagnostico = $this->diagnosticos->analizar($historial, $establecimiento);
            $this->cupo->registrar($establecimiento, $diagnostico->tokens);
        } catch (GeminiCredentialsMissingException|GeminiQuotaExceededException $e) {
            return back()->with('error', $e->getMessage());
        } catch (ConnectionException $e) {
            report($e);

            return back()->with('error', 'No hay conexión con el asistente (tiempo de espera agotado). Revisa red, firewall o VPN.');
        } catch (RequestException $e) {
            report($e);

            return back()->with('error', $e->response?->json('error.message') ?? $e->getMessage());
        }

        return back()->with('diagnostico', $diagnostico->toArray());
    }

    public function grafico(Request $request): View|RedirectResponse
    {
        $user = $request->user();
        $actual = $this->contexto->get($user);

        if ($actual === null) {
            return redirect()->route('Docente.diagnostico.index');
        }

        $establecimiento = $this->establecimiento($request);
        $escala = $actual->asignatura->escala ?? EscalaCalificacion::Nacional;
        $notaMaxima = $escala->maximo();
        $notaMinima = $escala === EscalaCalificacion::BachilleratoInternacional ? 4.0 : 7.0;
        $puntos = $this->historiales
            ->deVarios($this->asignaciones->alumnos($actual), $actual, $establecimiento)
            ->map(fn (HistorialAcademico $historial): array => $historial->toPuntoGrafico($notaMaxima))
            ->values();

        return view('docente.diagnostico-grafico', [
            'contexto' => $actual,
            'puntos' => $puntos,
            'grafico' => $this->graficoBarras->dibujar($puntos, $notaMaxima, $notaMinima),
            'escala' => $escala,
            'notaMaxima' => $notaMaxima,
            'notaMinima' => $notaMinima,
        ]);
    }

    public function grupo(StoreDiagnosticoGrupoRequest $request): RedirectResponse
    {
        $establecimiento = $this->establecimiento($request);
        $historiales = $this->historiales
            ->deVarios($request->alumnos(), $request->contexto(), $establecimiento)
            ->filter(fn (HistorialAcademico $historial): bool => $historial->tieneDificultades())
            ->values();

        if ($historiales->isEmpty()) {
            return back()->with('status', 'grupo-sin-problemas');
        }

        try {
            $this->cupo->exigirDisponible($establecimiento);
            $diagnostico = $this->diagnosticos->analizarGrupo($historiales->all(), $establecimiento);
            $this->cupo->registrar($establecimiento, $diagnostico->tokens);
        } catch (GeminiCredentialsMissingException|GeminiQuotaExceededException $e) {
            return back()->with('error', $e->getMessage());
        } catch (ConnectionException $e) {
            report($e);

            return back()->with('error', 'No hay conexión con el asistente (tiempo de espera agotado). Revisa red, firewall o VPN.');
        } catch (RequestException $e) {
            report($e);

            return back()->with('error', $e->response?->json('error.message') ?? $e->getMessage());
        }

        return back()->with('diagnostico_grupo', $diagnostico->toArray());
    }

    private function establecimiento(Request $request): Establecimiento
    {
        $establecimientoId = $request->user()?->establecimiento_id;

        abort_if($establecimientoId === null, 404);

        return Establecimiento::query()->findOrFail($establecimientoId);
    }
}
