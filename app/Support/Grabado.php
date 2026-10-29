<?php

namespace App\Support;

use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Throwable;

final class Grabado
{
    /**
     * @template T
     *
     * @param  callable(): T  $grabar
     * @return T|RedirectResponse
     */
    public static function intentar(
        callable $grabar,
        string $mensaje = 'No se pudo grabar. No se guardó ningún cambio.',
    ): mixed {
        try {
            return $grabar();
        } catch (HttpExceptionInterface|ValidationException $e) {
            throw $e;
        } catch (Throwable $e) {
            report($e);

            return back()->withInput()->with('error', $mensaje);
        }
    }
}
