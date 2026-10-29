<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="text-xl font-semibold leading-tight text-slate-800">Tareas</h2>
            <p class="mt-1 text-sm text-slate-500">Calendario de trabajos enviados por tus docentes. Haz clic en una tarea para verla y entregarla.</p>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            @if (session('status') === 'tarea-entregada')
                <p class="mb-4 rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-800">Tu entrega se guardó.</p>
            @endif

            <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="flex items-center justify-between gap-4 border-b border-slate-100 px-4 py-4 sm:px-6">
                    <a
                        href="{{ route('alumno.tareas.index', ['mes' => $mesAnterior]) }}"
                        class="rounded-lg px-3 py-2 text-sm font-medium text-slate-600 hover:bg-slate-100 hover:text-slate-900"
                    >
                        ← Anterior
                    </a>
                    <h3 class="text-lg font-semibold text-slate-900 sm:text-2xl">{{ $tituloMes }}</h3>
                    <a
                        href="{{ route('alumno.tareas.index', ['mes' => $mesSiguiente]) }}"
                        class="rounded-lg px-3 py-2 text-sm font-medium text-slate-600 hover:bg-slate-100 hover:text-slate-900"
                    >
                        Siguiente →
                    </a>
                </div>

                <div class="grid grid-cols-7 border-b border-slate-100 bg-slate-50">
                    @foreach ($diasSemana as $etiqueta)
                        <div class="px-2 py-3 text-center text-xs font-semibold uppercase tracking-wide text-slate-500 sm:text-sm">{{ $etiqueta }}</div>
                    @endforeach
                </div>

                <div class="grid grid-cols-7">
                    @foreach ($dias as $dia)
                        <div
                            class="border-b border-r border-slate-100 p-1.5 last:border-r-0 sm:p-2 {{ $dia['enMes'] ? 'bg-white' : 'bg-slate-50' }} {{ $dia['esHoy'] ? 'bg-indigo-50' : '' }}"
                            style="min-height: 8.5rem;"
                        >
                            <p class="mb-1 text-right text-xs font-semibold sm:text-sm {{ $dia['enMes'] ? 'text-slate-700' : 'text-slate-300' }} {{ $dia['esHoy'] ? 'text-indigo-700' : '' }}">
                                {{ $dia['numero'] }}
                            </p>
                            <div class="flex flex-col gap-1">
                                @foreach ($dia['tareas'] as $tarea)
                                    <button
                                        type="button"
                                        class="block w-full truncate rounded-md px-1.5 py-1 text-left text-xs font-medium text-white {{ $tarea->entregada ? 'bg-emerald-600 hover:bg-emerald-500' : 'bg-indigo-600 hover:bg-indigo-500' }}"
                                        x-on:click="$dispatch('open-modal', 'tarea-alumno-{{ $tarea->id }}')"
                                    >
                                        {{ $tarea->nombre }}
                                    </button>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>
            </section>

            @if ($tareas->isEmpty())
                <p class="mt-4 text-center text-sm text-slate-500">No tienes tareas enviadas en este mes.</p>
            @endif
        </div>
    </div>

    @foreach ($tareas as $tarea)
        @include('alumno.tareas._modal', [
            'tarea' => $tarea,
            'entrega' => $tarea->entregas->first(),
            'destinatario' => $tarea->destinatarios->first(),
            'puedeEntregar' => $tarea->aceptaEntregas(),
        ])
    @endforeach
</x-app-layout>
