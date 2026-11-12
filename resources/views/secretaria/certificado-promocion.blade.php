<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="text-xl font-semibold leading-tight text-slate-800">Certificado de promoción</h2>
            <p class="mt-1 text-sm text-slate-500">{{ $periodo?->nombre ?? 'Sin periodo activo' }}</p>
        </div>
    </x-slot>

    <style>
        @media print {
            @page {
                size: A4 portrait;
                margin: 14mm;
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
        <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8 print:max-w-none print:px-0">
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
                    action="{{ route('secretaria.certificado-promocion.index') }}"
                    class="print:hidden mb-6 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm"
                >
                    <label for="establecimiento_aula_id" class="block text-sm font-medium text-slate-700">Aula</label>
                    <div class="mt-2 flex flex-wrap items-end gap-3">
                        <select
                            id="establecimiento_aula_id"
                            name="establecimiento_aula_id"
                            required
                            class="block w-full max-w-md rounded-lg border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        >
                            <option value="">Selecciona un aula</option>
                            @foreach ($aulas as $opcion)
                                <option value="{{ $opcion->id }}" @selected((int) $aula?->id === (int) $opcion->id)>{{ $opcion->etiqueta() }}</option>
                            @endforeach
                        </select>
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
                        @if (($informe['por_destrezas'] ?? false) === true && ($informe['equivale_nota_nacional'] ?? false) !== true)
                            En Educación Inicial y Preparatoria la promoción no depende de una nota sobre 10.
                        @else
                            La promoción exige un promedio anual de al menos 7,00 en cada asignatura cuantitativa, conforme al Art. 197 del Reglamento General a la LOEI. Si una asignatura no tiene calificación, no hay promoción.
                        @endif
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
                                <article class="informe-estudiante rounded-2xl border border-slate-200 bg-white p-8 shadow-sm print:rounded-none print:border print:border-slate-800 print:shadow-none">
                                    <header class="flex items-start gap-4 border-b border-slate-200 pb-4">
                                        @if ($informe['logo'])
                                            <img src="{{ $informe['logo'] }}" alt="" class="h-16 w-16 object-contain">
                                        @endif
                                        <div class="min-w-0 flex-1 text-center">
                                            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">República del Ecuador</p>
                                            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Ministerio de Educación</p>
                                            <h1 class="mt-1 text-lg font-semibold text-slate-900">{{ $informe['establecimiento'] }}</h1>
                                            @if ($informe['amie'] !== '')
                                                <p class="text-xs text-slate-500">Código AMIE {{ $informe['amie'] }}</p>
                                            @endif
                                            <p class="mt-3 text-base font-semibold uppercase tracking-wide text-slate-800">{{ $informe['titulo'] }}</p>
                                        </div>
                                    </header>

                                    <p class="mt-6 text-sm leading-relaxed text-slate-800">
                                        El rectorado de esta institución educativa certifica que el/la estudiante
                                        <span class="font-semibold">{{ $estudiante['nombre'] }}</span>
                                        @if ($estudiante['identificacion'] !== '')
                                            , con identificación <span class="font-semibold">{{ $estudiante['identificacion'] }}</span>,
                                        @endif
                                        cursó el <span class="font-semibold">{{ $informe['grado'] }}</span>, paralelo
                                        <span class="font-semibold">{{ $informe['paralelo'] }}</span>,
                                        durante el periodo lectivo <span class="font-semibold">{{ $informe['periodo'] }}</span>.
                                    </p>

                                    <p class="mt-3 text-sm font-semibold text-slate-800">{{ $estudiante['promocion'] }}</p>

                                    <div class="mt-6 overflow-x-auto">
                                        <table class="min-w-full divide-y divide-slate-200 text-sm">
                                            <thead class="bg-slate-50">
                                                <tr>
                                                    <th scope="col" class="px-3 py-2 text-left font-semibold text-slate-700">Asignatura</th>
                                                    <th scope="col" class="px-3 py-2 text-center font-semibold text-slate-700">Promedio anual</th>
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

                                    @include('secretaria._destrezas-informe', ['informe' => $informe, 'estudiante' => $estudiante])

                                    @if (($informe['por_destrezas'] ?? false) !== true || ($informe['equivale_nota_nacional'] ?? false) === true)
                                    <div class="mt-4 rounded-lg border border-slate-200 p-3 text-xs text-slate-600">
                                        <p class="font-semibold text-slate-700">Escala de calificaciones (Art. 194)</p>
                                        <ul class="mt-2 grid grid-cols-1 gap-1 sm:grid-cols-2">
                                            @foreach ($informe['escala'] as $banda)
                                                <li>{{ $banda['rango'] }} · {{ $banda['etiqueta'] }}</li>
                                            @endforeach
                                        </ul>
                                        <p class="mt-2">{{ $informe['base_legal'] }}</p>
                                    </div>
                                    @endif

                                    <p class="mt-6 text-sm text-slate-700">
                                        @if ($informe['direccion'] !== '')
                                            {{ $informe['direccion'] }},
                                        @endif
                                        {{ $informe['fecha'] }}.
                                    </p>

                                    <div class="mt-12 grid grid-cols-1 gap-10 text-center text-xs text-slate-600 sm:grid-cols-2">
                                        <p class="border-t border-slate-400 pt-2">
                                            Secretaría
                                        </p>
                                        <p class="border-t border-slate-400 pt-2">
                                            Rector / a
                                            @if ($informe['representante'] !== '')
                                                <span class="mt-1 block font-semibold text-slate-700">{{ $informe['representante'] }}</span>
                                            @endif
                                        </p>
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
