<x-app-layout>
    <x-slot name="header">
        <div>
            <a href="{{ route('Docente.tareas.index') }}" class="text-sm font-medium text-indigo-700 hover:text-indigo-900">← Tareas</a>
            <h2 class="mt-1 text-xl font-semibold leading-tight text-slate-800">{{ $tarea->nombre }}</h2>
            <p class="mt-1 text-sm text-slate-500">{{ $tarea->aula?->etiqueta() }} · {{ $tarea->asignatura?->nombre() }} · {{ $tarea->ciclo?->nombre }} · Parcial {{ $tarea->parcial_numero }}</p>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            @if (session('error'))
                <p class="mb-4 rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-800">{{ session('error') }}</p>
            @endif
            @if (session('status') === 'tarea-calificada')
                <p class="mb-4 rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-800">Las calificaciones se grabaron como insumo.</p>
            @endif

            @if ($yaCalificada)
                <p class="mb-4 rounded-xl border border-indigo-200 bg-indigo-50 p-4 text-sm text-indigo-900">
                    Esta tarea ya está calificada.
                    <a href="{{ route('Docente.calificar.editar', ['actividad' => $tarea->establecimiento_insumo_actividad_id]) }}" class="font-semibold text-indigo-700 hover:text-indigo-900">Editarla en insumos</a>
                </p>
            @endif

            <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                @if ($yaCalificada)
                    <div class="grid grid-cols-1 gap-8 md:grid-cols-2">
                        <div>
                            <p class="text-sm font-medium text-slate-700">Nombre de la actividad</p>
                            <p class="mt-1 text-sm text-slate-900">{{ $tarea->nombre }}</p>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-slate-700">Tipo de insumo</p>
                            <p class="mt-1 text-sm text-slate-900">{{ $tarea->actividad?->detalle?->nombre ?? '—' }}</p>
                        </div>
                    </div>
                @else
                    <form method="POST" action="{{ route('Docente.tareas.calificar', $tarea) }}" class="grid grid-cols-1 gap-8 md:grid-cols-2">
                        @csrf
                        <div>
                            <x-input-label for="nombre" value="Nombre de la actividad" />
                            <x-text-input id="nombre" type="text" class="mt-1 block w-full bg-slate-50" :value="$tarea->nombre" readonly />
                        </div>
                        <div>
                            <x-input-label for="tipo_insumo_detalle_id" value="Elegir un tipo de insumo:" />
                            <select
                                id="tipo_insumo_detalle_id"
                                name="tipo_insumo_detalle_id"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                required
                                @disabled($insumos === [])
                            >
                                <option value="">Selecciona un tipo de insumo</option>
                                @foreach ($insumos as $insumo)
                                    <option value="{{ $insumo['id'] }}" @selected((string) old('tipo_insumo_detalle_id') === (string) $insumo['id'])>{{ $insumo['etiqueta'] }}</option>
                                @endforeach
                            </select>
                            @if ($insumos === [])
                                <p class="mt-2 text-sm text-slate-500">Esta materia no tiene un tipo de insumo asignado en el aula.</p>
                            @endif
                            <x-input-error class="mt-2" :messages="$errors->get('tipo_insumo_detalle_id')" />
                        </div>
                @endif

                <div class="{{ $yaCalificada ? 'mt-8' : 'md:col-span-2' }}">
                    <h3 class="mb-3 text-sm font-semibold text-slate-800">Entregas de los alumnos</h3>
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
                                    <th scope="col" class="px-4 py-3 text-left font-semibold text-slate-700">Entrega</th>
                                    <th scope="col" class="w-48 px-4 py-3 text-left font-semibold text-slate-700">Calificación</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @foreach ($tarea->destinatarios as $indice => $destinatario)
                                    @php
                                        $alumno = $destinatario->alumno;
                                        $nombre = trim(($alumno?->persona?->apellidos ?? '').' '.($alumno?->persona?->nombres ?? ''));
                                        $nombre = $nombre !== '' ? $nombre : 'Alumno';
                                        $entrega = $entregas->get($destinatario->alumno_id);
                                        $notaActual = old('notas.'.$destinatario->alumno_id, $notasGuardadas[(int) $destinatario->alumno_id] ?? '');
                                    @endphp
                                    <tr class="align-top">
                                        <td class="px-4 py-3 text-slate-500">{{ $indice + 1 }}</td>
                                        <td class="px-4 py-3 text-slate-800">{{ $nombre }}</td>
                                        <td class="px-4 py-3">
                                            <p class="text-xs text-slate-500">{{ $entrega ? 'Entregó el '.$entrega->updated_at?->format('d/m/Y H:i') : 'Sin entrega' }}</p>
                                            @if ($entrega?->mensaje)
                                                <p class="mt-2 whitespace-pre-wrap rounded-lg bg-slate-50 p-3 text-sm text-slate-700">{{ $entrega->mensaje }}</p>
                                            @endif
                                            @if ($entrega && $entrega->archivos->isNotEmpty())
                                                @foreach ($entrega->archivos as $archivo)
                                                    <x-tarea-entrega-archivo
                                                        :archivo="$archivo"
                                                        :url="route('Docente.tareas.archivo-entrega', [$tarea, $archivo])"
                                                    />
                                                @endforeach
                                            @endif
                                        </td>
                                        <td class="px-4 py-3">
                                            @if ($yaCalificada)
                                                <p class="text-sm font-medium text-slate-900">{{ $notaActual !== '' ? $notaActual : 'Sin calificar' }}</p>
                                            @elseif ($esCualitativa)
                                                <select
                                                    id="nota-{{ $destinatario->alumno_id }}"
                                                    name="notas[{{ $destinatario->alumno_id }}]"
                                                    class="block w-full rounded-md border-gray-300 py-1.5 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                                >
                                                    <option value="">Selecciona</option>
                                                    @foreach ($valoresCualitativos as $valor)
                                                        <option value="{{ $valor['valor'] }}" @selected((string) $notaActual === (string) $valor['valor'])>{{ $valor['etiqueta'] }}</option>
                                                    @endforeach
                                                </select>
                                                <x-input-error class="mt-1" :messages="$errors->get('notas.'.$destinatario->alumno_id)" />
                                            @else
                                                <x-text-input
                                                    :id="'nota-'.$destinatario->alumno_id"
                                                    name="notas[{{ $destinatario->alumno_id }}]"
                                                    type="number"
                                                    :min="$escala->minimo()"
                                                    :max="$escala->maximo()"
                                                    step="0.01"
                                                    class="block w-full py-1.5 text-sm"
                                                    :value="$notaActual"
                                                />
                                                <x-input-error class="mt-1" :messages="$errors->get('notas.'.$destinatario->alumno_id)" />
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

                @unless ($yaCalificada)
                        <div class="md:col-span-2 flex justify-end">
                            <x-primary-button :disabled="$insumos === []">Grabar calificaciones</x-primary-button>
                        </div>
                    </form>
                @endunless
            </section>
        </div>
    </div>
</x-app-layout>
