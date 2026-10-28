<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreNotificacionRequest;
use App\Http\Requests\StoreNotificacionRespuestaRequest;
use App\Models\Notificacion;
use App\Services\BandejaNotificaciones;
use App\Services\DestinatariosNotificacion;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NotificacionController extends Controller
{
    public function __construct(
        private BandejaNotificaciones $bandeja,
        private DestinatariosNotificacion $destinatarios,
    ) {}

    public function index(Request $request): View
    {
        $user = $request->user();

        abort_if($user === null, 403);

        $enviadas = $request->string('bandeja')->toString() === 'enviadas';

        return view('notificaciones.index', [
            'enviadas' => $enviadas,
            'notificaciones' => $enviadas
                ? $this->bandeja->enviadas($user)
                : $this->bandeja->recibidas($user),
        ]);
    }

    public function create(Request $request): View
    {
        $user = $request->user();

        abort_if($user === null, 403);

        return view('notificaciones.create', [
            'destinatarios' => $this->destinatarios->para($user),
        ]);
    }

    public function store(StoreNotificacionRequest $request): RedirectResponse
    {
        $user = $request->user();

        abort_if($user === null, 403);

        $datos = $request->validated();
        $notificacion = $this->bandeja->enviar(
            $user,
            array_map(intval(...), $datos['destinatarios']),
            $datos['asunto'],
            $datos['cuerpo'],
        );

        return redirect()
            ->route('notificaciones.show', $notificacion)
            ->with('status', 'notificacion-enviada');
    }

    public function show(Request $request, Notificacion $notificacion): View
    {
        $user = $request->user();

        abort_if($user === null, 403);
        abort_unless($this->bandeja->puedeVer($notificacion, $user), 404);

        $this->bandeja->marcarLeida($notificacion, $user);
        $notificacion->load(['remitente.persona', 'remitente.roles', 'destinatarios.user.persona', 'destinatarios.user.roles']);

        return view('notificaciones.show', [
            'notificacion' => $notificacion,
            'puedeResponder' => $this->bandeja->puedeResponder($notificacion, $user),
        ]);
    }

    public function responder(StoreNotificacionRespuestaRequest $request, Notificacion $notificacion): RedirectResponse
    {
        $user = $request->user();

        abort_if($user === null, 403);
        abort_unless($this->bandeja->puedeResponder($notificacion, $user), 404);

        $respuesta = $this->bandeja->responder(
            $notificacion,
            $user,
            $request->validated('cuerpo'),
        );

        return redirect()
            ->route('notificaciones.show', $respuesta)
            ->with('status', 'notificacion-enviada');
    }
}
