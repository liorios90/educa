<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="text-xl font-semibold leading-tight text-slate-800">Informes de aprendizaje</h2>
            <p class="mt-1 text-sm text-slate-500">{{ $periodo?->nombre ?? 'Sin periodo activo' }}</p>
        </div>
    </x-slot>

    <style>
        @media print {
            @page {
                size: A4 portrait;
                margin: 12mm;
            }

            .informe-estudiante {
                page-break-after: always;
                break-after: page;
            }

            .informe-estudiante:last-child {
                page-break-after: auto;
                break-after: auto;
            }
        }
    </style>

    <div class="py-8 print:py-0">
        <div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8 print:max-w-none print:px-0">
            @if ($periodo === null)
                <p class="rounded-2xl border border-slate-200 bg-white px-6 py-8 text-center text-slate-500">
                    Selecciona una oferta con un periodo activo para consultar las aulas.
                </p>
            @elseif ($aulas->isEmpty())
                <p class="rounded-2xl border border-slate-200 bg-white px-6 py-8 text-center text-slate-500">
                    No hay aulas configuradas en este periodo.
                </p>
            @else
                <form
                    method="GET"
                    action="{{ route('secretaria.informes-aprendizaje.index') }}"
                    class="print:hidden mb-6 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm"
                    x-data="{ tipo: @js($tipo) }"
                >
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-4">
                        <div>
                            <label for="establecimiento_aula_id" class="block text-sm font-medium text-slate-700">Aula</label>
                            <select
                                id="establecimiento_aula_id"
                                name="establecimiento_aula_id"
                                required
                                class="mt-2 block w-full rounded-lg border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            >
                                <option value="">Selecciona un aula</option>
                                @foreach ($aulas as $opcion)
                                    <option value="{{ $opcion->id }}" @selected((int) $aula?->id === (int) $opcion->id)>{{ $opcion->etiqueta() }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label for="establecimiento_periodo_ciclo_id" class="block text-sm font-medium text-slate-700">{{ $etiquetaCiclo }}</label>
                            <select
                                id="establecimiento_periodo_ciclo_id"
                                name="establecimiento_periodo_ciclo_id"
                                required
                                class="mt-2 block w-full rounded-lg border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            >
                                <option value="">Selecciona</option>
                                @foreach ($ciclos as $opcion)
                                    <option value="{{ $opcion->id }}" @selected((int) $ciclo?->id === (int) $opcion->id)>{{ $opcion->nombre }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label for="tipo" class="block text-sm font-medium text-slate-700">Tipo de informe</label>
                            <select
                                id="tipo"
                                name="tipo"
                                required
                                x-model="tipo"
                                class="mt-2 block w-full rounded-lg border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            >
                                <option value="">Selecciona</option>
                                <option value="parcial" @selected($tipo === 'parcial')>Informe parcial</option>
                                <option value="ciclo" @selected($tipo === 'ciclo')>{{ $etiquetaInformeCiclo }}</option>
                            </select>
                        </div>

                        <div x-show="tipo === 'parcial'" x-cloak>
                            <label for="parcial_numero" class="block text-sm font-medium text-slate-700">Parcial</label>
                            <select
                                id="parcial_numero"
                                name="parcial_numero"
                                x-bind:required="tipo === 'parcial'"
                                x-bind:disabled="tipo !== 'parcial'"
                                class="mt-2 block w-full rounded-lg border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            >
                                <option value="">Selecciona</option>
                                @foreach ($parciales as $numero)
                                    <option value="{{ $numero }}" @selected((int) $parcialNumero === (int) $numero)>
                                        {{ match ((int) $numero) {
                                            1 => '1er parcial',
                                            2 => '2do parcial',
                                            3 => '3er parcial',
                                            default => $numero.'º parcial',
                                        } }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="mt-4 flex flex-wrap items-center gap-3">
                        <button type="submit" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500">
                            Generar
                        </button>
                        @if ($informe !== null)
                            <button type="button" onclick="window.print()" class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                                Imprimir
                            </button>
                        @endif
                    </div>

                    <p class="mt-3 text-xs text-slate-500">
                        El informe parcial promedia los insumos de ese parcial. El informe de ciclo aplica los pesos de insumos, examen y proyecto. Las notas en blanco no entran al promedio. Escala del Art. 194 del Reglamento General a la LOEI.
                    </p>
                </form>

                @if ($informe !== null)
                    @if ($informe['estudiantes'] === [])
                        <p class="rounded-2xl border border-slate-200 bg-white px-6 py-8 text-center text-slate-500">
                            No hay estudiantes matriculados en esta aula.
                        </p>
                    @else
                        <div class="flex flex-col gap-8">
                            @foreach ($informe['estudiantes'] as $estudiante)
                                <article class="informe-estudiante rounded-2xl border border-slate-200 bg-white p-6 shadow-sm print:rounded-none print:border print:border-slate-800 print:shadow-none">
                                    <header class="flex items-start gap-4 border-b border-slate-200 pb-4">
                                        @if ($informe['logo'])
                                            <img src="{{ $informe['logo'] }}" alt="" class="h-16 w-16 object-contain">
                                        @endif
                                        <div class="min-w-0 flex-1 text-center">
                                            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Ministerio de Educación</p>
                                            <h1 class="mt-1 text-lg font-semibold text-slate-900">{{ $informe['establecimiento'] }}</h1>
                                            @if ($informe['amie'] !== '')
                                                <p class="text-xs text-slate-500">Código AMIE {{ $informe['amie'] }}</p>
                                            @endif
                                            <p class="mt-3 text-base font-semibold uppercase tracking-wide text-slate-800">{{ $informe['titulo'] }}</p>
                                            <p class="mt-1 text-sm text-slate-600">
                                                {{ $informe['periodo'] }}
                                                · {{ $informe['ciclo'] }}
                                                @if ($informe['parcial'])
                                                    · {{ $informe['parcial'] }}
                                                @endif
                                            </p>
                                            <p class="text-sm text-slate-600">{{ $informe['aula'] }}</p>
                                        </div>
                                    </header>

                                    <div class="mt-4 grid grid-cols-1 gap-2 text-sm sm:grid-cols-2">
                                        <p><span class="font-semibold text-slate-700">Estudiante:</span> {{ $estudiante['nombre'] }}</p>
                                        @if ($estudiante['identificacion'] !== '')
                                            <p><span class="font-semibold text-slate-700">Identificación:</span> {{ $estudiante['identificacion'] }}</p>
                                        @endif
                                    </div>

                                    <div class="mt-4 overflow-x-auto">
                                        <table class="min-w-full divide-y divide-slate-200 text-sm">
                                            <thead class="bg-slate-50">
                                                <tr>
                                                    <th scope="col" class="px-3 py-2 text-left font-semibold text-slate-700">Asignatura</th>
                                                    <th scope="col" class="px-3 py-2 text-center font-semibold text-slate-700">Calificación</th>
                                                    <th scope="col" class="px-3 py-2 text-left font-semibold text-slate-700">Equivalencia cualitativa</th>
                                                </tr>
                                            </thead>
                                            <tbody class="divide-y divide-slate-100">
                                                @foreach ($estudiante['asignaturas'] as $asignatura)
                                                    <tr>
                                                        <td class="px-3 py-2 text-slate-800">{{ $asignatura['nombre'] }}</td>
                                                        <td class="px-3 py-2 text-center font-medium text-slate-800">{{ $asignatura['nota'] }}</td>
                                                        <td class="px-3 py-2 text-slate-600">{{ $asignatura['cualitativa'] }}</td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                            <tfoot>
                                                <tr class="bg-slate-50 font-semibold">
                                                    <td class="px-3 py-2 text-slate-800">Promedio general</td>
                                                    <td class="px-3 py-2 text-center text-slate-800">{{ $estudiante['promedio'] }}</td>
                                                    <td class="px-3 py-2 text-slate-700">{{ $estudiante['promedio_cualitativa'] }}</td>
                                                </tr>
                                            </tfoot>
                                        </table>
                                    </div>

                                    <div class="mt-4 grid grid-cols-1 gap-2 text-sm sm:grid-cols-2">
                                        <p>
                                            <span class="font-semibold text-slate-700">Asistencia:</span>
                                            Faltas injustificadas: {{ $estudiante['asistencia']['faltas'] }}
                                            · Faltas justificadas: {{ $estudiante['asistencia']['justificadas'] }}
                                            · Atrasos: {{ $estudiante['asistencia']['atrasos'] }}
                                        </p>
                                        <p><span class="font-semibold text-slate-700">Comportamiento:</span> {{ $estudiante['comportamiento'] }}</p>
                                    </div>

                                    <div class="mt-4 rounded-lg border border-slate-200 p-3 text-xs text-slate-600">
                                        <p class="font-semibold text-slate-700">Escala de calificaciones (Art. 194)</p>
                                        <ul class="mt-2 grid grid-cols-1 gap-1 sm:grid-cols-2">
                                            @foreach ($informe['escala'] as $banda)
                                                <li>{{ $banda['rango'] }} · {{ $banda['etiqueta'] }}</li>
                                            @endforeach
                                        </ul>
                                        <p class="mt-2">{{ $informe['base_legal'] }}</p>
                                    </div>

                                    <div class="mt-10 grid grid-cols-1 gap-8 text-center text-xs text-slate-600 sm:grid-cols-3">
                                        <p class="border-t border-slate-400 pt-2">Secretaría</p>
                                        <p class="border-t border-slate-400 pt-2">Rector / a</p>
                                        <p class="border-t border-slate-400 pt-2">Representante legal</p>
                                    </div>
                                </article>
                            @endforeach
                        </div>
                    @endif
                @endif
            @endif
        </div>
    </div>
</x-app-layout>
