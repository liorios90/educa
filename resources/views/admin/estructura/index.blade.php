<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="text-xl font-semibold leading-tight text-slate-800">
                Estructura educativa
            </h2>
            <p class="mt-1 text-sm text-slate-500">
                {{ $establecimiento->nombre }}
            </p>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">
            @if (session('status') === 'estructura-updated')
                <p class="mb-4 text-sm font-medium text-green-700">Estructura de la jornada guardada correctamente.</p>
            @endif

            <section class="mb-6 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <h3 class="text-base font-semibold text-slate-800">Modalidades y jornadas</h3>
                <p class="mt-2 text-sm leading-6 text-slate-600">
                    Cada jornada de cada modalidad tiene su propia estructura de grados.
                    Escoge una para marcar los grados que oferta el colegio en ese horario.
                </p>
            </section>

            @if ($modalidades->isEmpty())
                <p class="rounded-2xl border border-slate-200 bg-white px-6 py-8 text-center text-slate-500">
                    Sistemas aún no ha asignado modalidades y jornadas a este establecimiento.
                    Cuando las asigne, podrás definir la estructura de grados de cada jornada.
                </p>
            @else
                <div class="space-y-6">
                    @foreach ($modalidades as $establecimientoModalidad)
                        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                            <div class="border-b border-slate-200 bg-slate-50 px-6 py-4">
                                <h3 class="text-base font-semibold text-slate-800">
                                    {{ $establecimientoModalidad->modalidad?->nombre ?? 'Modalidad' }}
                                </h3>
                            </div>
                            <ul class="divide-y divide-slate-100">
                                @forelse ($establecimientoModalidad->establecimientoJornadas as $oferta)
                                    <li class="flex flex-wrap items-center justify-between gap-3 px-6 py-4">
                                        <div>
                                            <p class="font-medium text-slate-800">
                                                {{ $oferta->jornada?->nombre ?? 'Jornada' }}
                                            </p>
                                            <p class="mt-1 text-sm text-slate-500">
                                                @if ($oferta->grados_count === 0)
                                                    Sin grados definidos
                                                @elseif ($oferta->grados_count === 1)
                                                    1 grado
                                                @else
                                                    {{ $oferta->grados_count }} grados
                                                @endif
                                            </p>
                                        </div>
                                        <a
                                            href="{{ route('Admin.estructura.edit', $oferta) }}"
                                            class="rounded-lg bg-slate-900 px-3 py-2 text-sm font-medium text-white hover:bg-slate-800"
                                        >
                                            Definir estructura
                                        </a>
                                    </li>
                                @empty
                                    <li class="px-6 py-4 text-sm text-slate-500">
                                        Esta modalidad no tiene jornadas asignadas.
                                    </li>
                                @endforelse
                            </ul>
                        </section>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
