<?php

use App\Exceptions\GeminiQuotaExceededException;
use App\Models\Establecimiento;
use App\Services\AsistenteCupo;

it('counts unused quota at the start of the month', function () {
    $this->travelTo('2026-10-20 12:00:00');

    $establecimiento = Establecimiento::factory()->create([
        'gemini_preguntas_limite' => 100,
        'gemini_tokens_limite' => 1000,
    ]);

    $uso = app(AsistenteCupo::class)->resumen($establecimiento);

    expect($uso->preguntasUsadas)->toBe(0)
        ->and($uso->preguntasRestantes)->toBe(100)
        ->and($uso->tokensUsados)->toBe(0)
        ->and($uso->tokensRestantes)->toBe(1000)
        ->and($uso->mesEtiqueta)->toBe('octubre 2026')
        ->and($uso->agotado())->toBeFalse();
});

it('ignores last month usage when summarizing the current month', function () {
    $this->travelTo('2026-10-20 12:00:00');

    $establecimiento = Establecimiento::factory()->create([
        'gemini_preguntas_limite' => 100,
        'gemini_tokens_limite' => 1000,
        'gemini_preguntas_usadas' => 40,
        'gemini_tokens_usados' => 800,
        'gemini_uso_mes' => '2026-09',
    ]);

    $uso = app(AsistenteCupo::class)->resumen($establecimiento);

    expect($uso->preguntasUsadas)->toBe(0)
        ->and($uso->preguntasRestantes)->toBe(100)
        ->and($uso->tokensUsados)->toBe(0)
        ->and($uso->tokensRestantes)->toBe(1000);
});

it('rejects a prompt when the school used every question this month', function () {
    $this->travelTo('2026-10-20 12:00:00');

    $establecimiento = Establecimiento::factory()->create([
        'gemini_preguntas_limite' => 2,
        'gemini_tokens_limite' => 1000,
        'gemini_preguntas_usadas' => 2,
        'gemini_tokens_usados' => 10,
        'gemini_uso_mes' => '2026-10',
    ]);

    expect(fn () => app(AsistenteCupo::class)->exigirDisponible($establecimiento))
        ->toThrow(GeminiQuotaExceededException::class, 'preguntas');
});

it('records a successful consultation against this month', function () {
    $this->travelTo('2026-10-20 12:00:00');

    $establecimiento = Establecimiento::factory()->create([
        'gemini_preguntas_limite' => 100,
        'gemini_tokens_limite' => 1000,
        'gemini_preguntas_usadas' => 3,
        'gemini_tokens_usados' => 80,
        'gemini_uso_mes' => '2026-10',
    ]);

    app(AsistenteCupo::class)->registrar($establecimiento, 25);

    $establecimiento->refresh();

    expect($establecimiento->gemini_preguntas_usadas)->toBe(4)
        ->and($establecimiento->gemini_tokens_usados)->toBe(105)
        ->and($establecimiento->gemini_uso_mes)->toBe('2026-10');
});

it('resets last month counters when recording the first consultation of the month', function () {
    $this->travelTo('2026-10-20 12:00:00');

    $establecimiento = Establecimiento::factory()->create([
        'gemini_preguntas_usadas' => 40,
        'gemini_tokens_usados' => 800,
        'gemini_uso_mes' => '2026-09',
    ]);

    app(AsistenteCupo::class)->registrar($establecimiento, 12);

    $establecimiento->refresh();

    expect($establecimiento->gemini_preguntas_usadas)->toBe(1)
        ->and($establecimiento->gemini_tokens_usados)->toBe(12)
        ->and($establecimiento->gemini_uso_mes)->toBe('2026-10');
});
