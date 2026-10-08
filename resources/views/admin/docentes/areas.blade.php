@php
    $seleccionadas = collect(old('areas', $docente->especialidades->pluck('area_id')->all()))
        ->map(fn (mixed $id): int => (int) $id)
        ->all();
@endphp

<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-slate-800">
            Áreas de especialización
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
            <form method="POST" action="{{ route('Admin.docentes.update', $docente) }}" class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                @csrf
                @method('patch')

                <div class="flex flex-wrap items-baseline justify-between gap-2">
                    <h3 class="text-base font-semibold text-slate-900">
                        {{ $docente->persona?->apellidos }} {{ $docente->persona?->nombres }}
                    </h3>
                    <p class="text-sm text-slate-500">{{ $docente->persona?->identificacion }}</p>
                </div>
                <p class="mt-2 text-sm text-slate-600">Marca una o varias áreas. Cada área se muestra con su subnivel.</p>

                @if ($subniveles === [])
                    <p class="mt-4 text-sm text-amber-800">Define la malla de asignaturas de esta jornada antes de registrar la especialidad.</p>
                @else
                    <div class="mt-6 flex flex-col gap-4">
                        @foreach ($subniveles as $subnivel)
                            <section class="rounded-2xl border border-slate-200 bg-slate-50 p-5">
                                <h4 class="border-b pb-2 text-sm font-bold" style="border-color: #0f172a; color: #0f172a">
                                    Subnivel: {{ $subnivel['nombre'] }}
                                </h4>
                                <div class="mt-4 flex flex-col gap-2">
                                    @foreach ($subnivel['areas'] as $area)
                                        <label class="flex items-center gap-2 text-sm text-slate-800">
                                            <input type="checkbox" name="areas[]" value="{{ $area['id'] }}" @checked(in_array($area['id'], $seleccionadas, true)) class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                                            {{ $area['nombre'] }}
                                        </label>
                                    @endforeach
                                </div>
                            </section>
                        @endforeach
                    </div>
                @endif

                <x-input-error class="mt-4" :messages="$errors->get('areas')" />

                <div class="mt-6 flex items-center justify-between gap-4">
                    <a href="{{ route('Admin.docentes') }}" class="text-sm text-slate-600 hover:text-slate-900">Volver</a>
                    @if ($subniveles !== [])
                        <button type="submit" class="rounded-lg bg-slate-900 px-3 py-2 text-sm font-medium text-white hover:bg-slate-800">
                            Guardar áreas
                        </button>
                    @endif
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
