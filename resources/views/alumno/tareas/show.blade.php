<x-app-layout>
    <x-slot name="header">
        <div>
            <a href="{{ route('alumno.tareas.index') }}" class="text-sm font-medium text-indigo-700 hover:text-indigo-900">← Tareas</a>
            <h2 class="mt-1 text-xl font-semibold leading-tight text-slate-800">{{ $tarea->nombre }}</h2>
            <p class="mt-1 text-sm text-slate-500">{{ $tarea->asignatura?->nombre() }} · {{ $tarea->ciclo?->nombre }} · Parcial {{ $tarea->parcial_numero }}</p>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
            @if (session('status') === 'tarea-entregada')
                <p class="mb-4 rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-800">Tu entrega se guardó.</p>
            @endif

            <article class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <p class="text-sm text-slate-500">Del {{ $tarea->fecha_inicio->format('d/m/Y') }} al {{ $tarea->fecha_fin->format('d/m/Y') }}</p>
                <div class="prose mt-4 max-w-none text-sm text-slate-800">{!! $tarea->descripcion !!}</div>
                @if ($tarea->contenido_tipo === \App\Enums\TipoContenidoTarea::Texto && $tarea->contenido_texto)
                    <p class="mt-4 whitespace-pre-wrap rounded-lg bg-slate-50 p-4 text-sm text-slate-700">{{ $tarea->contenido_texto }}</p>
                @elseif ($tarea->contenido_tipo === \App\Enums\TipoContenidoTarea::Url && $tarea->contenido_url)
                    <p class="mt-4 text-sm"><a class="font-medium text-indigo-700 hover:text-indigo-900" href="{{ $tarea->contenido_url }}" target="_blank" rel="noopener noreferrer">{{ $tarea->contenido_url }}</a></p>
                @elseif ($tarea->contenido_path)
                    <p class="mt-4 text-sm"><a class="font-medium text-indigo-700 hover:text-indigo-900" href="{{ route('alumno.tareas.archivo', $tarea) }}">Descargar {{ $tarea->contenido_nombre }}</a></p>
                @endif
                @php
                    $notaAlumno = $tarea->notaDelAlumno($destinatario?->alumno_id) ?? ($destinatario?->calificada_at ? $destinatario->nota : null);
                @endphp
                @if ($notaAlumno !== null && $notaAlumno !== '')
                    <p class="mt-4 rounded-lg bg-indigo-50 px-4 py-3 text-sm text-indigo-900">
                        Calificación: {{ $notaAlumno }}
                    </p>
                @endif
            </article>

            @if ($entrega)
                <section class="mt-6 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                    <h3 class="text-sm font-semibold text-slate-800">Tu entrega</h3>
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
                <form method="POST" action="{{ route('alumno.tareas.entregar', $tarea) }}" enctype="multipart/form-data" class="mt-6 flex flex-col gap-4 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                    @csrf
                    <div>
                        <x-input-label for="mensaje" value="Texto de la entrega" />
                        <textarea id="mensaje" name="mensaje" rows="5" maxlength="5000" class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">{{ old('mensaje', $entrega?->mensaje) }}</textarea>
                        <x-input-error class="mt-2" :messages="$errors->get('mensaje')" />
                    </div>
                    <div>
                        <x-input-label for="archivos" value="Documento" />
                        <input id="archivos" name="archivos[]" type="file" multiple accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.odt,.ods,.txt,.jpg,.jpeg,.png,.webp" class="mt-1 block w-full text-sm text-slate-700">
                        <p class="mt-1 text-xs text-slate-500">Puedes enviar un texto y/o documentos PDF, Word, Excel, PowerPoint o imagen (hasta 5 archivos).</p>
                        <x-input-error class="mt-2" :messages="$errors->get('archivos')" />
                    </div>
                    <div class="flex justify-end">
                        <button type="submit" class="rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-indigo-500">
                            {{ $entrega ? 'Actualizar entrega' : 'Enviar entrega' }}
                        </button>
                    </div>
                </form>
            @elseif (! $entrega)
                <p class="mt-6 rounded-xl border border-slate-200 bg-white p-4 text-sm text-slate-500">El plazo de esta tarea ya cerró.</p>
            @endif
        </div>
    </div>
</x-app-layout>
