<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-slate-800">
            Docentes
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            @if (session('status') === 'docente-especialidad-updated')
                <p class="mb-4 text-sm font-medium text-green-700">Áreas de especialización guardadas correctamente.</p>
            @endif

            <div class="overflow-x-auto rounded-2xl border border-slate-200 bg-white shadow-sm">
                <table class="min-w-full divide-y divide-slate-200 text-left text-sm">
                    <thead class="bg-slate-50 text-slate-600">
                        <tr>
                            <th class="px-6 py-3 font-medium">Apellidos</th>
                            <th class="px-6 py-3 font-medium">Nombres</th>
                            <th class="px-6 py-3 font-medium">Identificación</th>
                            <th class="px-6 py-3 font-medium">Especialización</th>
                            <th class="sticky right-0 bg-slate-50 px-6 py-3 font-medium">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-slate-800">
                        @forelse ($docentes as $docente)
                            @php
                                $especializacion = $docente->especialidades->map(function ($asignatura): string {
                                    $subnivel = $asignatura->area?->subnivel?->nombre;
                                    $area = $asignatura->area?->nombre ?? $asignatura->nombre;

                                    return $subnivel !== null && $subnivel !== ''
                                        ? $subnivel.' · '.$area
                                        : $area;
                                })->unique()->join(', ') ?: '—';
                            @endphp
                            <tr>
                                <td class="px-6 py-3">
                                    <span class="inline-flex items-center gap-2">
                                        {{ $docente->persona?->apellidos }}
                                        @if ((int) $docente->activo !== 1)
                                            <span class="text-xs font-medium text-amber-800">Inactivo</span>
                                        @endif
                                    </span>
                                </td>
                                <td class="px-6 py-3">{{ $docente->persona?->nombres }}</td>
                                <td class="px-6 py-3">{{ $docente->persona?->identificacion }}</td>
                                <td class="px-6 py-3">
                                    <span
                                        class="block overflow-hidden"
                                        style="display: -webkit-box; -webkit-box-orient: vertical; -webkit-line-clamp: 3;"
                                        title="{{ $especializacion }}"
                                    >{{ $especializacion }}</span>
                                </td>
                                <td class="sticky right-0 whitespace-nowrap bg-white px-6 py-3 text-right">
                                    <a href="{{ route('Admin.docentes.areas', $docente) }}" class="inline-flex rounded-lg bg-slate-900 px-3 py-2 text-sm font-medium text-white hover:bg-slate-800">
                                        Áreas de especialización
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-6 py-8 text-center text-slate-500">
                                    Aún no hay empleados con el rol Docente.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
