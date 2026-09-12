<?php

namespace App\Services;

use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;

class GeminiService
{
    public function generate(string $prompt): string
    {
        $model = config('services.gemini.model');

        $response = Http::baseUrl(config('services.gemini.base_url'))
            ->withHeaders([
                'x-goog-api-key' => config('services.gemini.key'),
                'Content-Type' => 'application/json',
            ])
            ->connectTimeout(5)
            ->timeout(30)
            ->post("/models/{$model}:generateContent", [
                'system_instruction' => [
                    'parts' => [
                        ['text' => 'Eres un asistente para una plataforma educativa.'],
                    ],
                ],
                'contents' => [
                    [
                        'parts' => [
                            ['text' => $prompt],
                        ],
                    ],
                ],
            ])
            ->throw();

        $text = $response->json('candidates.0.content.parts.0.text');

        if (! is_string($text) || $text === '') {
            throw new RequestException($response);
        }

        return $text;
    }
}