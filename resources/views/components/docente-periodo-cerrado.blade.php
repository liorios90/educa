<p {{ $attributes->merge(['class' => 'mt-4 rounded-md border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800']) }} x-show="seleccionCompleta && aula && ! aula.abierto" x-cloak>
    {{ \App\Auth\DocenteContexto::MENSAJE_PERIODO_CERRADO }}
</p>
