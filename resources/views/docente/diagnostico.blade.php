<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-slate-800">Diagnóstico del estudiante</h2>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            @unless ($asistenteListo)
                <p class="mb-4 text-sm font-medium text-amber-800">Este instituto aún no tiene una clave de Gemini. El administrador debe configurarla en Configuración → Asistente.</p>
            @endunless

            <div class="mb-4 grid grid-cols-1 gap-3 sm:grid-cols-2">
                <div class="rounded-2xl border border-slate-200 bg-white p-4">
                    <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Preguntas · {{ $uso->mesEtiqueta }}</p>
                    <p class="mt-2 text-2xl font-semibold text-slate-800">{{ $uso->preguntasUsadas }}</p>
                    <p class="mt-1 text-sm text-slate-600">Quedan {{ $uso->preguntasRestantes }} de {{ $uso->preguntasLimite }}</p>
                </div>
                <div class="rounded-2xl border border-slate-200 bg-white p-4">
                    <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Tokens · {{ $uso->mesEtiqueta }}</p>
                    <p class="mt-2 text-2xl font-semibold text-slate-800">{{ number_format($uso->tokensUsados, 0, ',', '.') }}</p>
                    <p class="mt-1 text-sm text-slate-600">Quedan {{ number_format($uso->tokensRestantes, 0, ',', '.') }} de {{ number_format($uso->tokensLimite, 0, ',', '.') }}</p>
                </div>
            </div>

            @if ($uso->agotado())
                <p class="mb-4 text-sm font-medium text-red-700">Este instituto ya usó el cupo mensual del asistente.</p>
            @endif

            @if (session('error'))
                <p class="mb-4 text-sm font-medium text-red-700">{{ session('error') }}</p>
            @endif

            @error('grupo')
                <p class="mb-4 text-sm font-medium text-red-700">{{ $message }}</p>
            @enderror

            @if (session('status') === 'grupo-sin-problemas')
                <p class="mb-4 text-sm font-medium text-emerald-800">Ningún estudiante de este paralelo muestra señales de dificultad en notas o asistencia.</p>
            @endif

            @if ($aulas === [])
                <p class="rounded-2xl border border-slate-200 bg-white px-6 py-8 text-center text-slate-500">
                    No tienes aulas asignadas en un periodo activo.
                </p>
            @else
                <x-docente-contexto :aulas="$aulas" :seleccion="$seleccion" :store-route="route('Docente.diagnostico.seleccionar')" destino="diagnostico">
                    <div class="mt-4 rounded-lg bg-white p-6 shadow-sm" x-show="seleccionCompleta">
                        @if ($contexto && $alumnos->isNotEmpty())
                            <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
                                <p class="text-sm text-slate-600">{{ $contexto->etiqueta() }}</p>
                                <form method="POST" action="{{ route('Docente.diagnostico.grupo') }}">
                                    @csrf
                                    <x-primary-button :disabled="$uso->agotado() || ! $asistenteListo">
                                        Analizar alumnos con problemas
                                    </x-primary-button>
                                </form>
                            </div>
                            <div class="overflow-x-auto rounded-lg border border-slate-200">
                                <table class="min-w-full divide-y divide-slate-200 text-sm">
                                    <thead class="bg-slate-50">
                                        <tr>
                                            <th scope="col" class="w-12 px-4 py-3 text-left font-semibold text-slate-700">Nº</th>
                                            <th scope="col" class="px-4 py-3 text-left font-semibold text-slate-700">Estudiante</th>
                                            <th scope="col" class="w-56 px-4 py-3 text-right font-semibold text-slate-700">Análisis</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100">
                                        @foreach ($alumnos as $indice => $alumno)
                                            @php
                                                $nombre = trim(($alumno->persona->apellidos ?? '').' '.($alumno->persona->nombres ?? ''));
                                            @endphp
                                            <tr>
                                                <td class="px-4 py-2 text-slate-500">{{ $indice + 1 }}</td>
                                                <td class="px-4 py-2 text-slate-800">{{ $nombre }}</td>
                                                <td class="px-4 py-2 text-right">
                                                    <form method="POST" action="{{ route('Docente.diagnostico.store') }}">
                                                        @csrf
                                                        <input type="hidden" name="alumno_id" value="{{ $alumno->id }}">
                                                        <x-primary-button :disabled="$uso->agotado() || ! $asistenteListo">
                                                            Analizar con IA
                                                        </x-primary-button>
                                                    </form>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @elseif ($contexto)
                            <p class="text-sm text-slate-500">No hay alumnos matriculados en este aula o asignatura.</p>
                        @endif
                    </div>
                </x-docente-contexto>
            @endif

            @php
                $diagnostico = session('diagnostico');
                $riesgo = is_array($diagnostico) ? ($diagnostico['riesgo_reprobar'] ?? '') : '';
                $riesgoClase = match ($riesgo) {
                    'alto' => 'bg-red-50 text-red-800 ring-red-200',
                    'medio' => 'bg-amber-50 text-amber-800 ring-amber-200',
                    'bajo' => 'bg-emerald-50 text-emerald-800 ring-emerald-200',
                    default => 'bg-slate-50 text-slate-700 ring-slate-200',
                };
            @endphp

            @if (is_array($diagnostico))
                <div class="mt-6 space-y-4 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <h3 class="text-lg font-semibold text-slate-800">{{ $diagnostico['estudiante'] }}</h3>
                        <span class="inline-flex rounded-full px-3 py-1 text-xs font-semibold uppercase tracking-wide ring-1 {{ $riesgoClase }}">
                            Riesgo {{ $riesgo }}
                        </span>
                    </div>
                    <p class="text-slate-700">{{ $diagnostico['resumen'] }}</p>

                    @if (($diagnostico['tendencias'] ?? []) !== [])
                        <div>
                            <h4 class="text-sm font-semibold text-slate-800">Tendencias</h4>
                            <ul class="mt-2 list-disc space-y-1 pl-5 text-sm text-slate-700">
                                @foreach ($diagnostico['tendencias'] as $tendencia)
                                    <li>{{ $tendencia }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    @if (($diagnostico['alertas'] ?? []) !== [])
                        <div>
                            <h4 class="text-sm font-semibold text-slate-800">Alertas</h4>
                            <ul class="mt-2 list-disc space-y-1 pl-5 text-sm text-slate-700">
                                @foreach ($diagnostico['alertas'] as $alerta)
                                    <li>{{ $alerta }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    @if (($diagnostico['recomendaciones'] ?? []) !== [])
                        <div>
                            <h4 class="text-sm font-semibold text-slate-800">Recomendaciones</h4>
                            <ul class="mt-2 list-disc space-y-1 pl-5 text-sm text-slate-700">
                                @foreach ($diagnostico['recomendaciones'] as $recomendacion)
                                    <li>{{ $recomendacion }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                </div>
            @endif

            @php
                $diagnosticoGrupo = session('diagnostico_grupo');
            @endphp

            @if (is_array($diagnosticoGrupo))
                <div class="mt-6 space-y-4 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                    <div>
                        <h3 class="text-lg font-semibold text-slate-800">Alumnos con problemas</h3>
                        <p class="mt-1 text-sm text-slate-600">{{ $diagnosticoGrupo['aula'] ?? '' }} · {{ $diagnosticoGrupo['asignatura'] ?? '' }}</p>
                    </div>
                    <p class="text-slate-700">{{ $diagnosticoGrupo['resumen'] ?? '' }}</p>

                    @foreach ($diagnosticoGrupo['estudiantes'] ?? [] as $estudianteGrupo)
                        @php
                            $riesgoGrupo = $estudianteGrupo['riesgo_reprobar'] ?? '';
                            $riesgoGrupoClase = match ($riesgoGrupo) {
                                'alto' => 'bg-red-50 text-red-800 ring-red-200',
                                'medio' => 'bg-amber-50 text-amber-800 ring-amber-200',
                                'bajo' => 'bg-emerald-50 text-emerald-800 ring-emerald-200',
                                default => 'bg-slate-50 text-slate-700 ring-slate-200',
                            };
                        @endphp
                        <div class="rounded-xl border border-slate-200 p-4">
                            <div class="flex flex-wrap items-center justify-between gap-3">
                                <h4 class="font-semibold text-slate-800">{{ $estudianteGrupo['estudiante'] ?? '' }}</h4>
                                <span class="inline-flex rounded-full px-3 py-1 text-xs font-semibold uppercase tracking-wide ring-1 {{ $riesgoGrupoClase }}">
                                    Riesgo {{ $riesgoGrupo }}
                                </span>
                            </div>
                            @if (($estudianteGrupo['problemas'] ?? []) !== [])
                                <ul class="mt-2 list-disc space-y-1 pl-5 text-sm text-slate-700">
                                    @foreach ($estudianteGrupo['problemas'] as $problema)
                                        <li>{{ $problema }}</li>
                                    @endforeach
                                </ul>
                            @endif
                            @if (($estudianteGrupo['recomendaciones'] ?? []) !== [])
                                <div class="mt-3">
                                    <p class="text-sm font-semibold text-slate-800">Recomendaciones</p>
                                    <ul class="mt-1 list-disc space-y-1 pl-5 text-sm text-slate-700">
                                        @foreach ($estudianteGrupo['recomendaciones'] as $recomendacionGrupo)
                                            <li>{{ $recomendacionGrupo }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif
                        </div>
                    @endforeach

                    @if (($diagnosticoGrupo['recomendaciones'] ?? []) !== [])
                        <div>
                            <h4 class="text-sm font-semibold text-slate-800">Recomendaciones para el paralelo</h4>
                            <ul class="mt-2 list-disc space-y-1 pl-5 text-sm text-slate-700">
                                @foreach ($diagnosticoGrupo['recomendaciones'] as $recomendacionGrupo)
                                    <li>{{ $recomendacionGrupo }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
