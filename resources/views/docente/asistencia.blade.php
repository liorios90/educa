<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="text-xl font-semibold leading-tight text-slate-800">Asistencia diaria</h2>
            <p class="mt-1 text-sm text-slate-500">Pasa lista una vez al día o en cada asignatura.</p>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            @if (session('status') === 'asistencia-registrada')
                <p class="mb-4 rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm font-medium text-green-800">Asistencia de la jornada guardada.</p>
            @endif

            @if ($aulas === [])
                <p class="rounded-2xl border border-slate-200 bg-white px-6 py-8 text-center text-slate-500">
                    No tienes aulas asignadas en un periodo activo.
                </p>
            @else
                <x-docente-contexto :aulas="$aulas" :seleccion="$seleccion" :store-route="route('Docente.asistencia.store')" destino="asistencia" :con-modo="true">
                    <div class="mt-4 flex flex-col gap-4 rounded-lg bg-white p-6 shadow-sm" x-show="seleccionCompleta && coincideConGuardado" x-cloak>
                        @if ($registro)
                            <p class="text-sm leading-6 text-slate-600">
                                La falta justificada solo procede con certificado médico, calamidad doméstica, representación del plantel o citación de autoridad.
                            </p>

                            <form method="GET" action="{{ route('Docente.asistencia.index') }}" class="flex flex-wrap items-end gap-3">
                                <div>
                                    <label for="fecha" class="block text-sm font-medium text-slate-700">Fecha de la jornada</label>
                                    <input
                                        id="fecha"
                                        name="fecha"
                                        type="date"
                                        value="{{ old('fecha', $registro['fecha']) }}"
                                        @if ($registro['fecha_min']) min="{{ $registro['fecha_min'] }}" @endif
                                        @if ($registro['fecha_max']) max="{{ $registro['fecha_max'] }}" @endif
                                        @change="$el.form.submit()"
                                        class="mt-1 block rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                        required
                                    >
                                </div>
                                <x-secondary-button type="submit">Ver jornada</x-secondary-button>
                            </form>

                            @if ($registro['mensaje_fecha'])
                                <p class="rounded-md border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">{{ $registro['mensaje_fecha'] }}</p>
                            @endif

                            <x-input-error :messages="$errors->get('fecha')" />
                            <x-input-error :messages="$errors->get('asistencias')" />

                            @if ($registro['jornada']['registrada'])
                                <p class="text-sm text-slate-700">
                                    Jornada guardada:
                                    {{ $registro['jornada']['presentes'] }} presentes,
                                    {{ $registro['jornada']['faltas'] }} faltas injustificadas,
                                    {{ $registro['jornada']['justificadas'] }} justificadas,
                                    {{ $registro['jornada']['atrasos'] }} atrasos.
                                </p>
                            @else
                                <p class="text-sm text-slate-500">Esta jornada todavía no tiene asistencia guardada. Si no cambias el estado, el estudiante se registra como presente.</p>
                            @endif

                            @if ($registro['alumnos'] === [])
                                <p class="rounded-md border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-500">
                                    No hay alumnos matriculados en este aula o asignatura.
                                </p>
                            @else
                                <form method="POST" action="{{ route('Docente.asistencia.registrar') }}" class="flex flex-col gap-4">
                                    @csrf
                                    <input type="hidden" name="modo" value="{{ $contexto->modo->value }}">
                                    <input type="hidden" name="establecimiento_aula_id" value="{{ $contexto->aula->id }}">
                                    @unless ($contexto->modo->esJornada())
                                        <input type="hidden" name="establecimiento_asignatura_id" value="{{ $contexto->asignatura?->id }}">
                                        <input type="hidden" name="establecimiento_periodo_ciclo_id" value="{{ $contexto->ciclo?->id }}">
                                        <input type="hidden" name="parcial_numero" value="{{ $contexto->parcialNumero }}">
                                    @endunless
                                    <input type="hidden" name="fecha" value="{{ old('fecha', $registro['fecha']) }}">

                                    <div class="overflow-x-auto rounded-lg border border-slate-200">
                                        <table class="min-w-full divide-y divide-slate-200 text-sm">
                                            <thead class="bg-slate-50">
                                                <tr>
                                                    <th scope="col" class="w-12 px-4 py-3 text-left font-semibold text-slate-700">Nº</th>
                                                    <th scope="col" class="px-4 py-3 text-left font-semibold text-slate-700">Estudiante</th>
                                                    <th scope="col" class="px-4 py-3 text-left font-semibold text-slate-700">Estado del día</th>
                                                    <th scope="col" class="px-4 py-3 text-left font-semibold text-slate-700">Documento</th>
                                                </tr>
                                            </thead>
                                            <tbody class="divide-y divide-slate-100">
                                                @foreach ($registro['alumnos'] as $indice => $alumno)
                                                    @php
                                                        $estadoActual = old('asistencias.'.$alumno['id'].'.estado', $alumno['estado']);
                                                        $justificacionActual = old('asistencias.'.$alumno['id'].'.justificacion', $alumno['justificacion']);
                                                    @endphp
                                                    <tr x-data="{ estado: @js($estadoActual) }">
                                                        <td class="px-4 py-2 text-slate-500">{{ $indice + 1 }}</td>
                                                        <td class="px-4 py-2 text-slate-800">{{ $alumno['nombre'] }}</td>
                                                        <td class="px-4 py-2">
                                                            <label class="sr-only" for="estado-{{ $alumno['id'] }}">Estado de {{ $alumno['nombre'] }}</label>
                                                            <select
                                                                id="estado-{{ $alumno['id'] }}"
                                                                name="asistencias[{{ $alumno['id'] }}][estado]"
                                                                x-model="estado"
                                                                class="block w-full min-w-44 rounded-md border-gray-300 py-1.5 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                                            >
                                                                @foreach (\App\Enums\EstadoAsistencia::cases() as $estado)
                                                                    <option value="{{ $estado->value }}">{{ $estado->etiquetaRegistro() }}</option>
                                                                @endforeach
                                                            </select>
                                                            <x-input-error class="mt-1" :messages="$errors->get('asistencias.'.$alumno['id'].'.estado')" />
                                                        </td>
                                                        <td class="px-4 py-2">
                                                            <div x-show="estado === 'justificada'" x-cloak>
                                                                <label class="sr-only" for="justificacion-{{ $alumno['id'] }}">Justificación de {{ $alumno['nombre'] }}</label>
                                                                <select
                                                                    id="justificacion-{{ $alumno['id'] }}"
                                                                    name="asistencias[{{ $alumno['id'] }}][justificacion]"
                                                                    class="block w-full min-w-52 rounded-md border-gray-300 py-1.5 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                                                >
                                                                    <option value="">Selecciona el documento</option>
                                                                    @foreach (\App\Enums\MotivoJustificacionAsistencia::cases() as $motivo)
                                                                        <option value="{{ $motivo->value }}" @selected((string) $justificacionActual === $motivo->value)>{{ $motivo->label() }}</option>
                                                                    @endforeach
                                                                </select>
                                                                <x-input-error class="mt-1" :messages="$errors->get('asistencias.'.$alumno['id'].'.justificacion')" />
                                                            </div>
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                    <div class="flex justify-end">
                                        <x-primary-button :disabled="! $registro['puede_registrar']">Guardar asistencia</x-primary-button>
                                    </div>
                                </form>
                            @endif
                        @endif
                    </div>
                </x-docente-contexto>
            @endif
        </div>
    </div>
</x-app-layout>
