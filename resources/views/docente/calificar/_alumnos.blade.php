@if ($alumnos->isEmpty())
    <p class="rounded-md border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-500">
        No hay alumnos matriculados en este aula o asignatura.
    </p>
@else
    @php
        $escala = $contexto->asignatura->escala;
        $esCualitativa = $tipoCalificacion === \App\Enums\TipoCalificacion::Cualitativa;
    @endphp
    <div class="overflow-x-auto rounded-lg border border-slate-200">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50">
                <tr>
                    <th scope="col" class="w-12 px-4 py-3 text-left font-semibold text-slate-700">Nº</th>
                    <th scope="col" class="px-4 py-3 text-left font-semibold text-slate-700">Estudiante</th>
                    <th scope="col" class="w-48 px-4 py-3 text-left font-semibold text-slate-700">Calificación</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @foreach ($alumnos as $indice => $alumno)
                    @php
                        $nombre = trim(($alumno->persona->apellidos ?? '').' '.($alumno->persona->nombres ?? ''));
                        $notaActual = old('notas.'.$alumno->id, $notasGuardadas[(int) $alumno->id] ?? '');
                    @endphp
                    <tr>
                        <td class="px-4 py-2 text-slate-500">{{ $indice + 1 }}</td>
                        <td class="px-4 py-2 text-slate-800">
                            <div class="flex flex-wrap items-center gap-2">
                                <span>{{ $nombre }}</span>
                                @if (isset($actividadEditando) && $actividadEditando && $notaActual === '')
                                    <span class="inline-flex rounded-full bg-amber-50 px-2 py-0.5 text-xs font-semibold text-amber-800 ring-1 ring-amber-200">Sin calificar</span>
                                @endif
                            </div>
                        </td>
                        <td class="px-4 py-2">
                            @if ($esCualitativa)
                                <select
                                    id="nota-{{ $alumno->id }}"
                                    name="notas[{{ $alumno->id }}]"
                                    class="block w-full rounded-md border-gray-300 py-1.5 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                >
                                    <option value="">Selecciona</option>
                                    @foreach ($valoresCualitativos as $valor)
                                        <option value="{{ $valor['valor'] }}" @selected((string) $notaActual === (string) $valor['valor'])>{{ $valor['etiqueta'] }}</option>
                                    @endforeach
                                </select>
                            @else
                                <x-text-input
                                    id="nota-{{ $alumno->id }}"
                                    name="notas[{{ $alumno->id }}]"
                                    type="number"
                                    :min="$escala->minimo()"
                                    :max="$escala->maximo()"
                                    step="0.01"
                                    class="block w-full py-1.5 text-sm"
                                    :value="$notaActual"
                                />
                            @endif
                            <x-input-error class="mt-1" :messages="$errors->get('notas.'.$alumno->id)" />
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endif
