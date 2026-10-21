<x-documento-layout>
    <x-slot name="title">Gráfico de notas</x-slot>

    <div class="min-h-screen px-4 py-6 sm:px-6 lg:px-8">
        <div class="no-print mx-auto mb-4 flex max-w-6xl flex-wrap items-center justify-between gap-3">
            <a
                href="{{ route('Docente.diagnostico.index') }}"
                class="inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-xs font-semibold uppercase tracking-widest text-gray-700 shadow-sm hover:bg-gray-50"
            >
                Volver al diagnóstico
            </a>
            <x-primary-button type="button" onclick="window.print()">
                Imprimir PDF
            </x-primary-button>
        </div>

        <div class="documento-imprimible mx-auto max-w-6xl rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h1 class="text-xl font-semibold text-slate-800">Gráfico de barras · Alumnos vs notas</h1>
            <p class="mt-1 text-sm text-slate-600">{{ $contexto->etiqueta() }}</p>
            <p class="mt-1 text-xs text-slate-500">{{ $escala->label() }} · Mínimo de aprobación: {{ number_format($notaMinima, 2, '.', '') }}</p>

            @if ($puntos->isEmpty())
                <p class="mt-6 text-sm text-slate-500">No hay alumnos matriculados en este aula o asignatura.</p>
            @else
                <div class="mt-6 overflow-x-auto">
                    <svg
                        role="img"
                        aria-label="Gráfico de barras de alumnos y notas"
                        viewBox="0 0 {{ $grafico['ancho'] }} {{ $grafico['alto'] }}"
                        class="h-auto w-full"
                    >
                        <line x1="48" y1="{{ $grafico['baseY'] }}" x2="{{ $grafico['ancho'] - 16 }}" y2="{{ $grafico['baseY'] }}" stroke="#94a3b8" stroke-width="1.5" />
                        <line x1="48" y1="28" x2="48" y2="{{ $grafico['baseY'] }}" stroke="#94a3b8" stroke-width="1.5" />
                        <line
                            x1="48"
                            y1="{{ $grafico['lineaMinimaY'] }}"
                            x2="{{ $grafico['ancho'] - 16 }}"
                            y2="{{ $grafico['lineaMinimaY'] }}"
                            stroke="#d97706"
                            stroke-width="1.5"
                            stroke-dasharray="6 4"
                        />

                        @foreach ($grafico['ticks'] as $tick)
                            <text x="40" y="{{ $tick['y'] + 4 }}" text-anchor="end" font-size="12" fill="#64748b">{{ $tick['etiqueta'] }}</text>
                        @endforeach

                        @foreach ($grafico['barras'] as $barra)
                            @if ($barra['alto'] > 0)
                                <rect
                                    x="{{ $barra['x'] }}"
                                    y="{{ $barra['y'] }}"
                                    width="{{ $barra['ancho'] }}"
                                    height="{{ $barra['alto'] }}"
                                    fill="{{ $barra['color'] }}"
                                >
                                    <title>{{ $barra['estudiante'] }}: {{ $barra['etiqueta'] }}</title>
                                </rect>
                            @endif
                            <text
                                x="{{ $barra['centroX'] }}"
                                y="{{ $barra['alto'] > 0 ? $barra['y'] - 6 : $grafico['baseY'] - 6 }}"
                                text-anchor="middle"
                                font-size="11"
                                font-weight="600"
                                fill="#334155"
                            >{{ $barra['etiqueta'] }}</text>
                            <text
                                x="{{ $barra['centroX'] }}"
                                y="{{ $grafico['baseY'] + 14 }}"
                                text-anchor="end"
                                font-size="11"
                                fill="#475569"
                                transform="rotate(-45 {{ $barra['centroX'] }} {{ $grafico['baseY'] + 14 }})"
                            >{{ $barra['estudiante'] }}</text>
                        @endforeach
                    </svg>
                </div>

                <div class="mt-4 flex flex-wrap gap-4 text-xs text-slate-600">
                    <span class="inline-flex items-center gap-2">
                        <span class="h-3 w-3 rounded" style="background:#4f46e5"></span>
                        Aprueba
                    </span>
                    <span class="inline-flex items-center gap-2">
                        <span class="h-3 w-3 rounded" style="background:#dc2626"></span>
                        Bajo el mínimo
                    </span>
                    <span class="inline-flex items-center gap-2">
                        <span class="w-6 border-t border-dashed" style="border-color:#d97706"></span>
                        Nota mínima
                    </span>
                </div>

                <div class="mt-8 overflow-x-auto rounded-lg border border-slate-200">
                    <table class="min-w-full divide-y divide-slate-200 text-sm">
                        <thead>
                            <tr>
                                <th scope="col" class="w-12 px-4 py-3 text-left font-semibold text-slate-700">Nº</th>
                                <th scope="col" class="px-4 py-3 text-left font-semibold text-slate-700">Estudiante</th>
                                <th scope="col" class="w-32 px-4 py-3 text-right font-semibold text-slate-700">Promedio</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach ($puntos as $indice => $punto)
                                <tr>
                                    <td class="px-4 py-2 text-slate-500">{{ $indice + 1 }}</td>
                                    <td class="px-4 py-2 text-slate-800">{{ $punto['estudiante'] }}</td>
                                    <td class="px-4 py-2 text-right {{ $punto['bajoMinimo'] ? 'font-semibold text-red-700' : 'text-slate-800' }}">
                                        {{ $punto['etiqueta'] }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
</x-documento-layout>
