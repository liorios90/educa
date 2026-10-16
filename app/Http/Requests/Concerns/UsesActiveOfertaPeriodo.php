<?php

namespace App\Http\Requests\Concerns;

use App\Auth\ActiveOferta;
use App\Auth\ActivePeriodo;
use App\Enums\Role;
use App\Models\EstablecimientoModalidadJornada;
use App\Models\EstablecimientoPeriodo;

trait UsesActiveOfertaPeriodo
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user !== null
            && $user->hasAnyRole([Role::Admin->value, Role::Secretaria->value])
            && $user->establecimiento_id !== null;
    }

    public function oferta(): ?EstablecimientoModalidadJornada
    {
        return $this->container->make(ActiveOferta::class)->get($this->user());
    }

    public function periodo(): ?EstablecimientoPeriodo
    {
        return $this->container->make(ActivePeriodo::class)->get($this->user());
    }

    /**
     * @return list<int>
     */
    public function ids(string $campo): array
    {
        return array_values(array_map(
            fn (mixed $id): int => (int) $id,
            (array) $this->input($campo, []),
        ));
    }
}
