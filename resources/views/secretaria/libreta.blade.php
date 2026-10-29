<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="text-xl font-semibold leading-tight text-slate-800">Libreta de calificaciones</h2>
            <p class="mt-1 text-sm text-slate-500">{{ $periodo?->nombre ?? 'Sin periodo activo' }}</p>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-screen-2xl px-4 sm:px-6 lg:px-8">
            @if ($periodo === null)
                <p class="rounded-2xl border border-slate-200 bg-white px-6 py-8 text-center text-slate-500">
                    Selecciona una oferta con un periodo activo para consultar las aulas.
                </p>
            @elseif ($aulas->isEmpty())
                <p class="rounded-2xl border border-slate-200 bg-white px-6 py-8 text-center text-slate-500">
                    No hay aulas configuradas en este periodo.
                </p>
            @else
                <form method="GET" action="{{ route('secretaria.libreta.index') }}" class="mb-6 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                    <label for="establecimiento_aula_id" class="block text-sm font-medium text-slate-700">Aula</label>
                    <div class="mt-2 flex flex-wrap items-end gap-3">
                        <select
                            id="establecimiento_aula_id"
                            name="establecimiento_aula_id"
                            required
                            onchange="this.form.submit()"
                            class="block w-full max-w-md rounded-lg border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        >
                            <option value="">Selecciona un aula</option>
                            @foreach ($aulas as $opcion)
                                <option value="{{ $opcion->id }}" @selected((int) $aula?->id === (int) $opcion->id)>{{ $opcion->etiqueta() }}</option>
                            @endforeach
                        </select>
                        <button type="submit" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500">
                            Ver
                        </button>
                    </div>
                    <p class="mt-2 text-xs text-slate-500">Insumos (100 %) es el promedio de las notas ingresadas: las actividades o tipos en blanco no entran. Pond. es ese valor por el porcentaje de insumos configurado en la asignatura, o en el grado, o en el periodo.</p>
                </form>

                @if ($aula && $libreta !== null)
                    @include('libreta.tabla', [
                        'libreta' => $libreta,
                        'mensajeVacio' => 'Esta aula no tiene materias asignadas.',
                    ])
                @endif
            @endif
        </div>
    </div>
</x-app-layout>
