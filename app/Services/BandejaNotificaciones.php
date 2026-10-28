<?php

namespace App\Services;

use App\Models\Notificacion;
use App\Models\NotificacionDestinatario;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class BandejaNotificaciones
{
    /**
     * @param  list<int>  $destinatarioIds
     */
    public function enviar(User $remitente, array $destinatarioIds, string $asunto, string $cuerpo): Notificacion
    {
        $ids = collect($destinatarioIds)
            ->map(fn (mixed $id): int => (int) $id)
            ->unique()
            ->values();

        return DB::transaction(function () use ($remitente, $ids, $asunto, $cuerpo): Notificacion {
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

            return $notificacion;
        });
    }

    /**
     * @return LengthAwarePaginator<int, Notificacion>
     */
    public function recibidas(User $user): LengthAwarePaginator
    {
        return Notificacion::query()
            ->with(['remitente.persona', 'remitente.roles'])
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

    public function responder(Notificacion $original, User $remitente, string $cuerpo): Notificacion
    {
        return $this->enviar(
            $remitente,
            [(int) $original->remitente_id],
            $this->asuntoRespuesta($original),
            $cuerpo,
        );
    }
}
