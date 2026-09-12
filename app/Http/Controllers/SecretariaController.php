<?php

namespace App\Http\Controllers;

use App\Services\GeminiService;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\Client\ConnectionException;


class SecretariaController extends Controller
{
    public function prueba()
    {
        dd('hola');
    }

    public function asistente(): View
    {
        return view('secretaria.asistente');
    }
    public function store(Request $request, GeminiService $gemini): RedirectResponse
    {
        $validated = $request->validate([
            'prompt' => ['required', 'string', 'max:4000'],
        ]);
        try {
            $texto = $gemini->generate($validated['prompt']);
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
        return back()->with('respuesta', $texto);
    }
    
}
