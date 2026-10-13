<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="text-xl font-semibold leading-tight text-slate-800">Por asignaturas</h2>
            <p class="mt-1 text-sm text-slate-500">{{ $establecimiento->nombre }}</p>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
            @if ($errors->any())
                <div class="mb-4 rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-700" role="alert">
                    <p class="font-semibold">No se pudo guardar la configuración:</p>
                    <ul class="mt-2 list-inside list-disc">
                        @foreach ($errors->all() as $mensaje)
                            <li>{{ $mensaje }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @if ($oferta === null)
                <p class="rounded-2xl border border-slate-200 bg-white px-6 py-8 text-center text-slate-500">
                    Sistemas aún no ha asignado modalidades y jornadas a este establecimiento.
                </p>
            @elseif ($periodo === null)
                <p class="rounded-2xl border border-slate-200 bg-white px-6 py-8 text-center text-slate-500">
                    No existe ningún periodo activo.
                </p>
            @else
                <section class="mb-6 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                    @if (session('status') === 'configuracion-asignaturas-updated')
                        <p class="mb-3 text-sm font-medium text-green-700">Configuración por asignatura guardada correctamente.</p>
                    @endif
                    <p class="text-sm text-slate-600">Configura los porcentajes de insumos, examen y proyecto por ciclo para cada asignatura cuantitativa de un aula.</p>
                    <p class="mt-2 text-sm font-medium text-slate-800">
                        {{ $oferta->etiqueta() }} · {{ $periodo->nombre }}
                    </p>
                </section>

                <section class="rounded-2xl border border-slate-200 bg-slate-50 p-2">
                    @if ($aulas->isEmpty())
                        <p class="rounded-xl border border-slate-200 bg-white px-6 py-8 text-center text-slate-500">
                            No hay aulas con asignaturas cuantitativas para este periodo.
                        </p>
                    @else
                        <div class="flex flex-col gap-3">
                            @foreach ($aulas as $aula)
                                <article class="flex flex-col gap-3 rounded-xl border border-slate-200 bg-white p-4 shadow-sm sm:flex-row sm:items-center sm:justify-between">
                                    <div>
                                        <h3 class="text-sm font-semibold text-slate-800">{{ $aula['etiqueta'] }}</h3>
                                        @if ($aula['sin_aula'])
                                            <p class="mt-1 text-xs text-slate-500">Aún no se ha creado un aula para este grado en el periodo.</p>
                                        @endif
                                    </div>
                                    <x-primary-button
                                        type="button"
                                        x-on:click="$dispatch('aula-asignaturas', { etiqueta: {{ \Illuminate\Support\Js::from($aula['etiqueta']) }}, aulaId: {{ \Illuminate\Support\Js::from($aula['aula_id']) }}, action: {{ \Illuminate\Support\Js::from($aula['aula_id'] === null ? '' : route('Admin.configuracion-asignaturas.update', $aula['aula_id'])) }}, puedeConfigurar: {{ \Illuminate\Support\Js::from($aula['puede_configurar']) }}, asignaturas: {{ \Illuminate\Support\Js::from($aula['asignaturas']) }} })"
                                    >
                                        Asignaturas
                                    </x-primary-button>
                                </article>
                            @endforeach
                        </div>
                    @endif
                </section>

                @include('admin.configuracion-asignaturas._asignaturas-modal')
            @endif
        </div>
    </div>
</x-app-layout>
