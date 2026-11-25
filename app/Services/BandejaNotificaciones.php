<?php

namespace App\Services;

use App\Models\Notificacion;
use App\Models\NotificacionDestinatario;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Throwable;

class BandejaNotificaciones
{
    /**
     * @param  list<int>  $destinatarioIds
     * @param  list<UploadedFile>  $archivos
     */
    public function enviar(User $remitente, array $destinatarioIds, string $asunto, string $cuerpo, array $archivos = []): Notificacion
    {
        $ids = collect($destinatarioIds)
            ->map(fn (mixed $id): int => (int) $id)
            ->unique()
            ->values();
        $guardados = [];

        try {
            return DB::transaction(function () use ($remitente, $ids, $asunto, $cuerpo, $archivos, &$guardados): Notificacion {
                $notificacion = Notificacion::query()->create([
                    'establecimiento_id' => $remitente->establecimiento_id,
                    'remitente_id' => $remitente->id,
                    'asunto' => $asunto,
                    'cuerpo' => $cuerpo,
                ]);

                foreach ($ids as $destinatarioId) {
                    $notificacion->destinatarios()->create([
                        'user_id' => $destinatarioId,
                    ]);
                }

                foreach ($archivos as $archivo) {
                    if (! $archivo instanceof UploadedFile) {
                        continue;
                    }

                    $path = $archivo->store('notificaciones/'.$notificacion->id, 'local');
                    $guardados[] = $path;
                    $notificacion->archivos()->create([
                        'path' => $path,
                        'nombre_original' => $this->nombreOriginal($archivo),
                        'mime' => (string) ($archivo->getMimeType() ?: $archivo->getClientMimeType() ?: 'application/octet-stream'),
                        'size' => (int) $archivo->getSize(),
                    ]);
                }

                return $notificacion;
            });
        } catch (Throwable $e) {
            foreach ($guardados as $path) {
                Storage::disk('local')->delete($path);
            }

            throw $e;
        }
    }

    /**
     * @return LengthAwarePaginator<int, Notificacion>
     */
    public function recibidas(User $user): LengthAwarePaginator
    {
        return Notificacion::query()
            ->with(['remitente.persona', 'remitente.roles'])
            ->withCount('archivos')
            ->whereHas('destinatarios', fn ($query) => $query->where('user_id', $user->id))
            ->withExists([
                'destinatarios as no_leida' => fn ($query) => $query
                    ->where('user_id', $user->id)
                    ->whereNull('leida_at'),
            ])
            ->latest('id')
            ->paginate(20)
            ->withQueryString();
    }

    /**
     * @return LengthAwarePaginator<int, Notificacion>
     */
    public function enviadas(User $user): LengthAwarePaginator
    {
        return Notificacion::query()
            ->with(['destinatarios.user.persona', 'destinatarios.user.roles'])
            ->withCount('archivos')
            ->where('remitente_id', $user->id)
            ->latest('id')
            ->paginate(20)
            ->withQueryString();
    }

    public function puedeVer(Notificacion $notificacion, User $user): bool
    {
        if ((int) $notificacion->remitente_id === (int) $user->id) {
            return true;
        }

        return $notificacion->destinatarios()
            ->where('user_id', $user->id)
            ->exists();
    }

    public function marcarLeida(Notificacion $notificacion, User $user): void
    {
        NotificacionDestinatario::query()
            ->where('notificacion_id', $notificacion->id)
            ->where('user_id', $user->id)
            ->whereNull('leida_at')
            ->update(['leida_at' => now()]);
    }

    public function noLeidas(User $user): int
    {
        return NotificacionDestinatario::query()
            ->where('user_id', $user->id)
            ->whereNull('leida_at')
            ->count();
    }

    public function puedeResponder(Notificacion $notificacion, User $user): bool
    {
        if ((int) $notificacion->remitente_id === (int) $user->id) {
            return false;
        }

        return $notificacion->destinatarios()
            ->where('user_id', $user->id)
            ->exists();
    }

    public function asuntoRespuesta(Notificacion $notificacion): string
    {
        $asunto = trim($notificacion->asunto);

        if (str_starts_with(mb_strtolower($asunto), 're:')) {
            return mb_substr($asunto, 0, 160);
        }

        return mb_substr('Re: '.$asunto, 0, 160);
    }

    /**
     * @param  list<UploadedFile>  $archivos
     */
    public function responder(Notificacion $original, User $remitente, string $cuerpo, array $archivos = []): Notificacion
    {
        return $this->enviar(
            $remitente,
            [(int) $original->remitente_id],
            $this->asuntoRespuesta($original),
            $cuerpo,
            $archivos,
        );
    }

    private function nombreOriginal(UploadedFile $archivo): string
    {
        $nombre = basename(str_replace('\\', '/', $archivo->getClientOriginalName()));
        $nombre = str_replace(["\0", "\r", "\n"], '', $nombre);
        $nombre = trim($nombre);

        if ($nombre === '' || $nombre === '.' || $nombre === '..') {
            $extension = $archivo->extension();
            $nombre = $extension !== '' ? 'archivo.'.$extension : 'archivo';
        }

        return mb_substr($nombre, 0, 180);
    }
}
