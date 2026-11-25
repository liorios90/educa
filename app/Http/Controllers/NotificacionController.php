<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreNotificacionRequest;
use App\Http\Requests\StoreNotificacionRespuestaRequest;
use App\Models\Notificacion;
use App\Models\NotificacionArchivo;
use App\Services\BandejaNotificaciones;
use App\Services\DestinatariosNotificacion;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

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
            'aulas' => $this->destinatarios->aulasPara($user),
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
            $this->archivosSubidos($request),
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
        $notificacion->load(['remitente.persona', 'remitente.roles', 'destinatarios.user.persona', 'destinatarios.user.roles', 'archivos']);

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
            $this->archivosSubidos($request),
        );

        return redirect()
            ->route('notificaciones.show', $respuesta)
            ->with('status', 'notificacion-enviada');
    }

    public function archivo(Request $request, Notificacion $notificacion, NotificacionArchivo $archivo): StreamedResponse
    {
        $user = $request->user();

        abort_if($user === null, 403);
        abort_unless($this->bandeja->puedeVer($notificacion, $user), 404);
        abort_unless((int) $archivo->notificacion_id === (int) $notificacion->id, 404);
        abort_unless(Storage::disk('local')->exists($archivo->path), 404);

        return Storage::disk('local')->response($archivo->path, $this->nombreDescarga($archivo), [
            'Content-Type' => $archivo->tipoContenido(),
            'X-Content-Type-Options' => 'nosniff',
        ], $archivo->seMuestraEnLinea() ? 'inline' : 'attachment');
    }

    private function nombreDescarga(NotificacionArchivo $archivo): string
    {
        $nombre = str_replace(['/', '\\'], '-', $archivo->nombre_original);
        $nombre = trim($nombre);

        return $nombre !== '' ? $nombre : 'archivo';
    }

    /**
     * @return list<UploadedFile>
     */
    private function archivosSubidos(Request $request): array
    {
        $archivos = $request->file('archivos', []);

        if ($archivos instanceof UploadedFile) {
            return [$archivos];
        }

        if (! is_array($archivos)) {
            return [];
        }

        return array_values(array_filter(
            $archivos,
            fn (mixed $archivo): bool => $archivo instanceof UploadedFile,
        ));
    }
}
