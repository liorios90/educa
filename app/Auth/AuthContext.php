<?php

namespace App\Auth;

use App\Models\Establecimiento;
use App\Models\EstablecimientoPeriodo;
use App\Models\User;

class AuthContext
{
    public const USER_KEY = 'auth_user';

    public const ESTABLECIMIENTO_KEY = 'auth_establecimiento';

    public const PERIODO_KEY = 'auth_periodo';

    public function remember(User $user): void
    {
        $user->loadMissing(['establecimiento', 'roles']);

        session([
            self::USER_KEY => $this->userPayload($user),
            self::ESTABLECIMIENTO_KEY => $this->establecimientoPayload($user->establecimiento),
            self::PERIODO_KEY => $this->periodo(),
        ]);
    }

    public function rememberIfMissing(User $user): void
    {
        if (session()->missing(self::USER_KEY)) {
            $this->remember($user);
        }
    }

    /**
     * @return array{id: int, name: string, email: string, establecimiento_id: int|null, roles: list<string>}|null
     */
    public function user(): ?array
    {
        $user = session(self::USER_KEY);

        return is_array($user) ? $user : null;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function establecimiento(): ?array
    {
        $establecimiento = session(self::ESTABLECIMIENTO_KEY);

        return is_array($establecimiento) ? $establecimiento : null;
    }

    /**
     * @return array{
     *     id: int,
     *     nombre: string,
     *     fecha_inicio: string|null,
     *     fecha_fin: string|null,
     *     establecimiento_id: int,
     *     establecimiento_modalidad_jornada_id: int
     * }|null
     */
    public function periodo(): ?array
    {
        $periodo = session(self::PERIODO_KEY);

        return is_array($periodo) ? $periodo : null;
    }

    public function rememberPeriodo(?EstablecimientoPeriodo $periodo): void
    {
        session([self::PERIODO_KEY => $this->periodoPayload($periodo)]);
    }

    public function clear(): void
    {
        session()->forget([self::USER_KEY, self::ESTABLECIMIENTO_KEY, self::PERIODO_KEY]);
    }

    /**
     * @return array{id: int, name: string, email: string, establecimiento_id: int|null, roles: list<string>}
     */
    private function userPayload(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'establecimiento_id' => $user->establecimiento_id,
            'roles' => $user->getRoleNames()->values()->all(),
        ];
    }

    /**
     * @return array{
     *     id: int,
     *     nombre: string,
     *     descripcion: string|null,
     *     direccion: string|null,
     *     telefono: string|null,
     *     representante: string|null,
     *     codigo_amie: string|null,
     *     regimen: string|null,
     *     email: string|null,
     *     activo: mixed,
     *     logo: string|null,
     *     logo_url: string|null,
     *     grupo_amie: int|null,
     *     zona_id: int|null,
     *     distrito_id: int|null,
     *     circuito_id: int|null
     * }|null
     */
    private function establecimientoPayload(?Establecimiento $establecimiento): ?array
    {
        if (! $establecimiento instanceof Establecimiento) {
            return null;
        }

        return [
            'id' => $establecimiento->id,
            'nombre' => $establecimiento->nombre,
            'descripcion' => $establecimiento->descripcion,
            'direccion' => $establecimiento->direccion,
            'telefono' => $establecimiento->telefono,
            'representante' => $establecimiento->representante,
            'codigo_amie' => $establecimiento->codigo_amie,
            'regimen' => $establecimiento->regimen,
            'email' => $establecimiento->email,
            'activo' => $establecimiento->activo,
            'logo' => $establecimiento->logo,
            'logo_url' => $establecimiento->logoUrl(),
            'grupo_amie' => $establecimiento->grupo_amie,
            'zona_id' => $establecimiento->zona_id,
            'distrito_id' => $establecimiento->distrito_id,
            'circuito_id' => $establecimiento->circuito_id,
        ];
    }

    /**
     * @return array{
     *     id: int,
     *     nombre: string,
     *     fecha_inicio: string|null,
     *     fecha_fin: string|null,
     *     establecimiento_id: int,
     *     establecimiento_modalidad_jornada_id: int
     * }|null
     */
    private function periodoPayload(?EstablecimientoPeriodo $periodo): ?array
    {
        if (! $periodo instanceof EstablecimientoPeriodo) {
            return null;
        }

        return [
            'id' => $periodo->id,
            'nombre' => $periodo->nombre,
            'fecha_inicio' => $periodo->fecha_inicio?->toDateString(),
            'fecha_fin' => $periodo->fecha_fin?->toDateString(),
            'establecimiento_id' => $periodo->establecimiento_id,
            'establecimiento_modalidad_jornada_id' => $periodo->establecimiento_modalidad_jornada_id,
        ];
    }
}
