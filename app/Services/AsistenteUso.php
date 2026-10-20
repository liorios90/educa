<?php

namespace App\Services;

readonly class AsistenteUso
{
    public function __construct(
        public int $preguntasUsadas,
        public int $preguntasLimite,
        public int $preguntasRestantes,
        public int $tokensUsados,
        public int $tokensLimite,
        public int $tokensRestantes,
        public string $mes,
        public string $mesEtiqueta,
    ) {}

    public function agotado(): bool
    {
        return $this->preguntasRestantes === 0 || $this->tokensRestantes === 0;
    }
}
