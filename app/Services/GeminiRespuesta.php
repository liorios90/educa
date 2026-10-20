<?php

namespace App\Services;

readonly class GeminiRespuesta
{
    public function __construct(
        public string $texto,
        public int $tokens,
    ) {}
}
