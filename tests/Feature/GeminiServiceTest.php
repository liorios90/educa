<?php

use App\Services\GeminiService;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

it('returns the generated text from Gemini', function () {
    Http::preventStrayRequests();

    Http::fake([
        'generativelanguage.googleapis.com/*' => Http::response([
            'candidates' => [
                [
                    'content' => [
                        'parts' => [
                            ['text' => 'Hola desde el fake'],
                        ],
                    ],
                ],
            ],
        ]),
    ]);

    $texto = app(GeminiService::class)->generate('Resume este tema');

    expect($texto)->toBe('Hola desde el fake');

    Http::assertSent(function (Request $request) {
        return str_contains($request->url(), 'models/gemini-2.5-flash:generateContent')
            && $request['contents'][0]['parts'][0]['text'] === 'Resume este tema';
    });
});