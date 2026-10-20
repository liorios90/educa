<?php

namespace App\Http\Controllers;

use App\Exceptions\GeminiCredentialsMissingException;
use App\Exceptions\GeminiQuotaExceededException;
use App\Models\Establecimiento;
use App\Services\AsistenteCupo;
use App\Services\GeminiService;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SecretariaController extends Controller
{
    public function prueba()
    {
        dd('hola');
    }

    public function asistente(Request $request, AsistenteCupo $cupo): View
    {
        $establecimiento = $this->establecimiento($request);

        return view('secretaria.asistente', [
            'storeRoute' => $request->routeIs('Docente.asistente')
                ? route('Docente.asistente.store')
                : route('secretaria.asistente.store'),
            'asistenteListo' => $establecimiento->tieneCredencialesGemini(),
            'uso' => $cupo->resumen($establecimiento),
        ]);
    }

    public function store(Request $request, GeminiService $gemini, AsistenteCupo $cupo): RedirectResponse
    {
        $validated = $request->validate([
            'prompt' => ['required', 'string', 'max:4000'],
        ]);
        $establecimiento = $this->establecimiento($request);

        try {
            $cupo->exigirDisponible($establecimiento);
            $respuesta = $gemini->generate($validated['prompt'], $establecimiento);
            $cupo->registrar($establecimiento, $respuesta->tokens);
        } catch (GeminiCredentialsMissingException|GeminiQuotaExceededException $e) {
            return back()
                ->withInput()
                ->with('error', $e->getMessage());
        } catch (ConnectionException $e) {
            report($e);

            return back()
                ->withInput()
                ->with('error', 'No hay conexión con Gemini (tiempo de espera agotado). Revisa red, firewall o VPN.');
        } catch (RequestException $e) {
            report($e);

            return back()
                ->withInput()
                ->with('error', $e->response?->json('error.message') ?? $e->getMessage());
        }

        return back()->with('respuesta', $respuesta->texto);
    }

    private function establecimiento(Request $request): Establecimiento
    {
        $establecimientoId = $request->user()?->establecimiento_id;

        abort_if($establecimientoId === null, 404);

        return Establecimiento::query()->findOrFail($establecimientoId);
    }
}
