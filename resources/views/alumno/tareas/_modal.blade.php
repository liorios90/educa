@php
    $esModalActivo = (int) request('tarea') === (int) $tarea->id;
    $mensajeFormulario = $esModalActivo ? old('mensaje', $entrega?->mensaje) : $entrega?->mensaje;
@endphp

<x-modal :name="'tarea-alumno-'.$tarea->id" maxWidth="4xl" :show="$esModalActivo" focusable>
    <div class="overflow-y-auto p-6" style="max-height: 90vh;">
        <div class="flex items-start justify-between gap-4">
            <div>
                <h3 class="text-lg font-semibold text-slate-900">{{ $tarea->nombre }}</h3>
                <p class="mt-1 text-sm text-slate-500">
                    {{ $tarea->asignatura?->nombre() }}
                    · {{ $tarea->ciclo?->nombre }}
                    · Parcial {{ $tarea->parcial_numero }}
                </p>
                <p class="mt-1 text-sm text-slate-500">
                    Enviada el {{ $tarea->fecha_inicio->format('d/m/Y') }}
                    · Entregar hasta {{ $tarea->fecha_fin->format('d/m/Y') }}
                </p>
            </div>
            <x-secondary-button type="button" x-on:click="$dispatch('close')">Cerrar</x-secondary-button>
        </div>

        <div class="prose mt-4 max-w-none text-sm text-slate-800">{!! $tarea->descripcion !!}</div>

        @if ($tarea->contenido_tipo === \App\Enums\TipoContenidoTarea::Texto && $tarea->contenido_texto)
            <p class="mt-4 whitespace-pre-wrap rounded-lg bg-slate-50 p-4 text-sm text-slate-700">{{ $tarea->contenido_texto }}</p>
        @elseif ($tarea->contenido_tipo === \App\Enums\TipoContenidoTarea::Url && $tarea->contenido_url)
            <p class="mt-4 text-sm">
                <a class="font-medium text-indigo-700 hover:text-indigo-900" href="{{ $tarea->contenido_url }}" target="_blank" rel="noopener noreferrer">{{ $tarea->contenido_url }}</a>
            </p>
        @elseif ($tarea->contenido_path)
            <p class="mt-4 text-sm">
                <a class="font-medium text-indigo-700 hover:text-indigo-900" href="{{ route('alumno.tareas.archivo', $tarea) }}">Descargar {{ $tarea->contenido_nombre }}</a>
            </p>
        @endif

        @php
            $notaAlumno = $tarea->notaDelAlumno($destinatario?->alumno_id) ?? ($destinatario?->calificada_at ? $destinatario->nota : null);
        @endphp
        @if ($notaAlumno !== null && $notaAlumno !== '')
            <p class="mt-4 rounded-lg bg-indigo-50 px-4 py-3 text-sm text-indigo-900">
                Calificación: {{ $notaAlumno }}
            </p>
        @endif

        @if ($entrega)
            <section class="mt-5 rounded-xl border border-slate-200 bg-slate-50 p-4">
                <h4 class="text-sm font-semibold text-slate-800">Tu entrega</h4>
                @if ($entrega->mensaje)
                    <p class="mt-2 whitespace-pre-wrap text-sm text-slate-700">{{ $entrega->mensaje }}</p>
                @endif
                @if ($entrega->archivos->isNotEmpty())
                    <ul class="mt-2 flex flex-col gap-1 text-sm">
                        @foreach ($entrega->archivos as $archivo)
                            <li>
                                <a class="text-indigo-700 hover:text-indigo-900" href="{{ route('alumno.tareas.archivo-entrega', [$tarea, $archivo]) }}">{{ $archivo->nombre_original }}</a>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </section>
        @endif

        @if ($puedeEntregar)
            <form method="POST" action="{{ route('alumno.tareas.entregar', $tarea) }}" enctype="multipart/form-data" class="mt-5 flex flex-col gap-4">
                @csrf
                <div>
                    <x-input-label :for="'mensaje-'.$tarea->id" value="Texto de la entrega" />
                    <textarea
                        id="mensaje-{{ $tarea->id }}"
                        name="mensaje"
                        rows="5"
                        maxlength="5000"
                        class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                    >{{ $mensajeFormulario }}</textarea>
                    @if ($esModalActivo)
                        <x-input-error class="mt-2" :messages="$errors->get('mensaje')" />
                    @endif
                </div>
                <div>
                    <x-input-label :for="'archivos-'.$tarea->id" value="Documento" />
                    <input
                        id="archivos-{{ $tarea->id }}"
                        name="archivos[]"
                        type="file"
                        multiple
                        accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.odt,.ods,.txt,.jpg,.jpeg,.png,.webp"
                        class="mt-1 block w-full text-sm text-slate-700"
                    >
                    <p class="mt-1 text-xs text-slate-500">Puedes enviar un texto y/o documentos PDF, Word, Excel, PowerPoint o imagen (hasta 5 archivos).</p>
                    @if ($esModalActivo)
                        <x-input-error class="mt-2" :messages="$errors->get('archivos')" />
                        <x-input-error class="mt-2" :messages="$errors->get('archivos.*')" />
                    @endif
                </div>
                <div class="flex justify-end gap-2">
                    <x-secondary-button type="button" x-on:click="$dispatch('close')">Cancelar</x-secondary-button>
                    <button type="submit" class="rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-indigo-500">
                        {{ $entrega ? 'Actualizar entrega' : 'Enviar entrega' }}
                    </button>
                </div>
            </form>
        @elseif (! $entrega)
            <p class="mt-5 rounded-xl border border-slate-200 bg-slate-50 p-4 text-sm text-slate-500">El plazo de esta tarea ya cerró.</p>
        @endif
    </div>
</x-modal>
