<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <h2 class="text-xl font-semibold leading-tight text-slate-800">Tareas</h2>
                <p class="mt-1 text-sm text-slate-500">Elige aula, materia, ciclo y parcial para ver las tareas enviadas.</p>
            </div>
            @if ($contexto)
                <a href="{{ route('Docente.tareas.create') }}" class="rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-indigo-500">
                    Nueva tarea
                </a>
            @endif
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            @if (session('error'))
                <p class="mb-4 rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-800">{{ session('error') }}</p>
            @endif
            @if (session('status') === 'tarea-enviada')
                <p class="mb-4 rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-800">La tarea se envió correctamente.</p>
            @endif
            @if (session('status') === 'tarea-eliminada')
                <p class="mb-4 rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-800">La tarea se eliminó.</p>
            @endif

            @if ($aulas === [])
                <p class="rounded-2xl border border-slate-200 bg-white px-6 py-8 text-center text-slate-500">
                    No tienes aulas asignadas en un periodo activo.
                </p>
            @else
                <x-docente-contexto :aulas="$aulas" :seleccion="$seleccion" :store-route="route('Docente.tareas.contexto')" destino="tareas" />

                @if ($contexto)
                    <section class="mt-6 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                        @forelse ($tareas as $tarea)
                            <div class="flex flex-wrap items-start justify-between gap-3 border-b border-slate-100 px-5 py-4 last:border-b-0">
                                <a href="{{ route('Docente.tareas.show', $tarea) }}" class="min-w-0 flex-1">
                                    <p class="text-base font-semibold text-slate-900">{{ $tarea->nombre }}</p>
                                    <p class="mt-1 text-xs text-slate-400">{{ $tarea->fecha_inicio->format('d/m/Y') }} — {{ $tarea->fecha_fin->format('d/m/Y') }}</p>
                                    <p class="mt-1 text-sm text-slate-600">
                                        {{ $tarea->contenido_tipo->label() }}
                                        · {{ $tarea->destinatarios_count }} destinatario{{ $tarea->destinatarios_count === 1 ? '' : 's' }}
                                        · {{ $tarea->entregas_count }} entrega{{ $tarea->entregas_count === 1 ? '' : 's' }}
                                        @if ($tarea->estaCalificada())
                                            · Calificada
                                        @endif
                                    </p>
                                </a>
                                <div class="flex shrink-0 items-center gap-3">
                                    <a href="{{ route('Docente.tareas.entregas', $tarea) }}" class="text-sm font-medium text-indigo-600 hover:text-indigo-800">{{ $tarea->estaCalificada() ? 'Ver entregas' : 'Calificar' }}</a>
                                    <a href="{{ route('Docente.tareas.edit', $tarea) }}" class="text-sm font-medium text-indigo-600 hover:text-indigo-800">Modificar</a>
                                    <form method="POST" action="{{ route('Docente.tareas.destroy', $tarea) }}" onsubmit="return confirm('¿Eliminar esta tarea y sus entregas?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-sm font-medium text-red-600 hover:text-red-800">Eliminar</button>
                                    </form>
                                </div>
                            </div>
                        @empty
                            <p class="px-6 py-8 text-center text-sm text-slate-500">Aún no has enviado tareas en este contexto.</p>
                        @endforelse
                    </section>
                @endif
            @endif
        </div>
    </div>
</x-app-layout>
