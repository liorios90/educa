<?php

namespace App\Services;

use App\Exceptions\GeminiCredentialsMissingException;
use App\Models\Establecimiento;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;

class GeminiService
{
    public function generate(string $prompt, Establecimiento $establecimiento): GeminiRespuesta
    {
        $key = $establecimiento->gemini_api_key;

        if (! is_string($key) || $key === '') {
            throw new GeminiCredentialsMissingException;
        }

        $model = $establecimiento->modeloGemini();

        $response = Http::baseUrl(config('services.gemini.base_url'))
            ->withHeaders([
                'x-goog-api-key' => $key,
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

        $tokens = $response->json('usageMetadata.totalTokenCount');

        return new GeminiRespuesta(
            $text,
            is_numeric($tokens) ? max(0, (int) $tokens) : 0,
        );
    }
}
