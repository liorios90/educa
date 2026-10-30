<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class AsistenteBaseUrl implements ValidationRule
{
    /**
     * @param  (Closure(string): list<string>)|null  $resolver
     */
    public function __construct(private ?Closure $resolver = null) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || trim($value) === '') {
            return;
        }

        $mensaje = $this->mensaje($value);

        if ($mensaje !== null) {
            $fail($mensaje);
        }
    }

    public function mensaje(string $url): ?string
    {
        $url = trim($url);

        if ($url === '') {
            return null;
        }

        if (preg_match('/\s/', $url) === 1) {
            return 'La URL base del asistente no es válida.';
        }

        $partes = parse_url($url);

        if (! is_array($partes) || isset($partes['query']) || isset($partes['fragment'])) {
            return 'La URL base del asistente no es válida.';
        }

        if (isset($partes['user']) || isset($partes['pass'])) {
            return 'La URL base no puede incluir usuario ni contraseña.';
        }

        $esquema = strtolower((string) ($partes['scheme'] ?? ''));
        $host = trim((string) ($partes['host'] ?? ''), '[]');

        if ($host === '' || ! in_array($esquema, ['http', 'https'], true)) {
            return 'La URL base debe usar https.';
        }

        if ($this->esLoopback($host)) {
            return null;
        }

        if ($esquema === 'http') {
            return 'La URL base debe usar https, salvo en localhost.';
        }

        if (filter_var($host, FILTER_VALIDATE_IP) !== false) {
            return $this->esIpPublica($host)
                ? null
                : 'La URL base no puede apuntar a una red privada.';
        }

        if (preg_match('/^(?:[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?\.)*[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?$/i', $host) !== 1) {
            return 'La URL base del asistente no es válida.';
        }

        $direcciones = $this->direcciones($host);

        if ($direcciones === []) {
            return 'No se pudo comprobar el servidor de la URL base.';
        }

        foreach ($direcciones as $direccion) {
            if (! $this->esIpPublica($direccion)) {
                return 'La URL base no puede apuntar a una red privada.';
            }
        }

        return null;
    }

    private function esLoopback(string $host): bool
    {
        return in_array(strtolower($host), ['localhost', '127.0.0.1', '::1'], true);
    }

    private function esIpPublica(string $ip): bool
    {
        return filter_var(
            $ip,
            FILTER_VALIDATE_IP,
            FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE,
        ) !== false;
    }

    /**
     * @return list<string>
     */
    private function direcciones(string $host): array
    {
        if ($this->resolver !== null) {
            return array_values(($this->resolver)($host));
        }

        $direcciones = [];
        $ipv4 = gethostbynamel($host);

        if (is_array($ipv4)) {
            $direcciones = $ipv4;
        }

        if (defined('DNS_AAAA')) {
            $registros = @dns_get_record($host, DNS_AAAA);

            if (is_array($registros)) {
                foreach ($registros as $registro) {
                    if (isset($registro['ipv6']) && is_string($registro['ipv6'])) {
                        $direcciones[] = $registro['ipv6'];
                    }
                }
            }
        }

        return array_values(array_unique($direcciones));
    }
}
