<?php

namespace App\Services;

use App\Exceptions\GeminiCredentialsMissingException;
use App\Models\Establecimiento;
use App\Rules\AsistenteBaseUrl;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;

class AsistenteCompatibleService
{
    public function __construct(
        private AsistenteConsultaLog $consultas,
    ) {}

    public function generate(string $prompt, Establecimiento $establecimiento, ?string $instruccionSistema = null): GeminiRespuesta
    {
        $key = $establecimiento->gemini_api_key;
        $url = $establecimiento->asistente_base_url;

        if (! is_string($key) || $key === '' || ! is_string($url) || $url === '') {
            throw new GeminiCredentialsMissingException;
        }

        if ((new AsistenteBaseUrl)->mensaje($url) !== null) {
            throw new GeminiCredentialsMissingException('La URL base del asistente no está permitida. Revísala en Configuración.');
        }

        $response = Http::withOptions(['allow_redirects' => false])
            ->withToken($key)
            ->acceptJson()
            ->asJson()
            ->connectTimeout(5)
            ->timeout(30)
            ->post($this->endpoint($url), [
                'model' => $establecimiento->modeloGemini(),
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => $instruccionSistema ?? 'Eres un asistente para una plataforma educativa.',
                    ],
                    [
                        'role' => 'user',
                        'content' => $prompt,
                    ],
                ],
            ])
            ->throw();

        $text = $response->json('choices.0.message.content');

        if (! is_string($text) || $text === '') {
            throw new RequestException($response);
        }

        $this->consultas->registrar($establecimiento, $prompt);

        return new GeminiRespuesta($text, $this->tokens($response->json('usage')));
    }

    private function endpoint(string $baseUrl): string
    {
        $base = rtrim(trim($baseUrl), '/');
        $sufijo = '/chat/completions';

        if (str_ends_with(strtolower($base), $sufijo)) {
            $base = rtrim(substr($base, 0, -strlen($sufijo)), '/');
        }

        return $base.'/chat/completions';
    }

    private function tokens(mixed $usage): int
    {
        if (! is_array($usage)) {
            return 0;
        }

        $total = $usage['total_tokens'] ?? null;

        if (is_numeric($total)) {
            return max(0, (int) $total);
        }

        $prompt = $usage['prompt_tokens'] ?? null;
        $completion = $usage['completion_tokens'] ?? null;

        if (! is_numeric($prompt) && ! is_numeric($completion)) {
            return 0;
        }

        return max(0, (int) $prompt + (int) $completion);
    }
}
