@if ($libreta['materias'] === [])
    <p class="rounded-2xl border border-slate-200 bg-white px-6 py-8 text-center text-slate-500">
        {{ $mensajeVacio ?? 'No hay materias con calificaciones para mostrar.' }}
    </p>
@else
    <div class="space-y-6">
        @foreach ($libreta['materias'] as $materia)
            <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-200 px-4 py-3">
                    <h3 class="text-base font-semibold text-slate-900">{{ $materia['nombre'] }}</h3>
                </div>
                @if ($materia['filas'] === [])
                    <p class="px-4 py-6 text-sm text-slate-500">No hay alumnos en esta materia.</p>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-200 text-left text-sm">
                            <thead class="bg-slate-50 text-slate-600">
                                <tr>
                                    <th rowspan="2" class="sticky left-0 z-10 bg-slate-50 px-3 py-2 font-semibold">Alumno</th>
                                    @foreach ($materia['grupos'] as $grupo)
                                        <th colspan="{{ $grupo['columnas'] }}" class="border-l border-slate-200 px-3 py-2 text-center font-semibold">{{ $grupo['etiqueta'] }}</th>
                                    @endforeach
                                </tr>
                                <tr>
                                    @foreach ($materia['columnas'] as $columna)
                                        <th class="border-l border-slate-200 px-2 py-2 font-medium" title="{{ $columna['ayuda'] }}">
                                            <span class="block">{{ $columna['etiqueta'] }}</span>
                                            @if ($columna['ayuda'] !== '')
                                                <span class="block text-[11px] font-normal text-slate-400">{{ $columna['ayuda'] }}</span>
                                            @endif
                                        </th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @foreach ($materia['filas'] as $fila)
                                    <tr>
                                        <td class="sticky left-0 bg-white px-3 py-2 font-medium text-slate-900">{{ $fila['alumno'] }}</td>
                                        @foreach ($materia['columnas'] as $columna)
                                            @php
                                                $valor = $fila['celdas'][$columna['clave']] ?? '—';
                                                $ancla = $fila['anclas'][$columna['clave']] ?? null;
                                            @endphp
                                            <td class="border-l border-slate-100 px-2 py-2 text-slate-700 {{ str_starts_with($columna['clave'], 'promedio') ? 'font-semibold text-slate-900' : '' }}">
                                                @if (is_array($ancla))
                                                    <a
                                                        href="#{{ $ancla['id'] }}"
                                                        class="font-medium text-indigo-600 hover:text-indigo-500"
                                                        x-on:click.prevent="$dispatch('abrir-libreta-insumos', {{ \Illuminate\Support\Js::from($ancla) }})"
                                                    >{{ $valor }}</a>
                                                @else
                                                    {{ $valor }}
                                                @endif
                                            </td>
                                        @endforeach
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </section>
        @endforeach
    </div>
@endif

<x-modal name="libreta-insumos" maxWidth="2xl">
    <div
        class="flex flex-col gap-4 p-6"
        x-data="{ titulo: '', grupos: [] }"
        x-on:abrir-libreta-insumos.window="
            titulo = $event.detail.titulo;
            grupos = $event.detail.grupos || [];
            $dispatch('open-modal', 'libreta-insumos');
        "
    >
        <div>
            <h3 class="text-lg font-semibold text-slate-800">Insumos</h3>
            <p class="mt-1 text-sm text-slate-500" x-text="titulo"></p>
        </div>

        <div class="flex flex-col gap-4">
            <template x-for="grupo in grupos" :key="grupo.nombre + grupo.porcentaje">
                <section class="rounded-xl border border-slate-200">
                    <div class="flex flex-wrap items-baseline justify-between gap-2 border-b border-slate-200 bg-slate-50 px-3 py-2">
                        <h4 class="text-sm font-semibold text-slate-800">
                            <span x-text="grupo.nombre"></span>
                            <span class="font-normal text-slate-500" x-text="grupo.porcentaje" x-show="grupo.porcentaje"></span>
                        </h4>
                        <p class="text-sm font-semibold text-slate-900" x-text="grupo.promedio"></p>
                    </div>
                    <ul class="divide-y divide-slate-100">
                        <template x-for="actividad in grupo.actividades" :key="actividad.id">
                            <li class="flex flex-wrap items-baseline justify-between gap-2 px-3 py-2 text-sm">
                                <div>
                                    <p class="text-slate-800" x-text="actividad.nombre"></p>
                                    <p class="text-xs text-slate-500" x-text="actividad.parcial" x-show="actividad.parcial"></p>
                                </div>
                                <p class="font-medium text-slate-900" x-text="actividad.nota"></p>
                            </li>
                        </template>
                    </ul>
                </section>
            </template>
            <p class="text-sm text-slate-500" x-show="grupos.length === 0">No hay insumos registrados en este ciclo.</p>
        </div>

        <div class="flex justify-end">
            <x-secondary-button type="button" x-on:click="$dispatch('close-modal', 'libreta-insumos')">Cerrar</x-secondary-button>
        </div>
    </div>
</x-modal>
