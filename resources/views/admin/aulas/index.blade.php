<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="text-xl font-semibold leading-tight text-slate-800">
                Aulas
            </h2>
            <p class="mt-1 text-sm text-slate-500">
                {{ $establecimiento->nombre }}
            </p>
        </div>
    </x-slot>

    <div
        class="py-8"
        x-data
    >
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            @if (session('status') === 'aula-created')
                <p class="mb-4 text-sm font-medium text-green-700">Aula creada correctamente.</p>
            @endif

            @if (session('status') === 'aula-deleted')
                <p class="mb-4 text-sm font-medium text-green-700">Aula eliminada correctamente.</p>
            @endif

            @if (session('error'))
                <p class="mb-4 text-sm font-medium text-red-700">{{ session('error') }}</p>
            @endif

            @if ($oferta === null)
                <p class="rounded-2xl border border-slate-200 bg-white px-6 py-8 text-center text-slate-500">
                    Sistemas aún no ha asignado modalidades y jornadas a este establecimiento.
                </p>
            @elseif ($periodo === null)
                <p class="rounded-2xl border border-slate-200 bg-white px-6 py-8 text-center text-slate-500">
                    No existe ningún periodo activo.
                </p>
            @else
                <section class="mb-6 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                    <p class="text-sm text-slate-600">
                        Las aulas se crean para la modalidad, jornada y periodo de esta sesión. Elige el grado y escribe el paralelo.
                    </p>
                    <p class="mt-2 text-sm font-medium text-slate-800">
                        {{ $oferta->etiqueta() }}
                        · {{ $periodo->nombre }}
                    </p>
                </section>

                @if ($grados->isEmpty())
                    <p class="rounded-2xl border border-slate-200 bg-white px-6 py-8 text-center text-slate-500">
                        Define los grados de esta modalidad y jornada en
                        <a href="{{ route('Admin.estructura.edit', $oferta) }}" class="font-medium text-indigo-600 hover:text-indigo-500">Estructura</a>
                        antes de crear aulas.
                    </p>
                @else
                    <div class="mb-4 flex justify-end">
                        <x-primary-button type="button" x-on:click="$dispatch('open-modal', 'aula-create')">
                            Crear aula
                        </x-primary-button>
                    </div>

                    <div class="overflow-x-auto rounded-2xl border border-slate-200 bg-white shadow-sm">
                        <table class="min-w-full divide-y divide-slate-200 text-left text-sm">
                            <thead class="bg-slate-50 text-slate-600">
                                <tr>
                                    <th class="px-6 py-3 font-medium">Id</th>
                                    <th class="px-6 py-3 font-medium">Grado</th>
                                    <th class="px-6 py-3 font-medium">Paralelo</th>
                                    <th class="sticky right-0 bg-slate-50 px-6 py-3 font-medium">Acciones</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 text-slate-800">
                                @forelse ($aulas as $aula)
                                    <tr>
                                        <td class="px-6 py-3">{{ $aula->id }}</td>
                                        <td class="px-6 py-3 font-medium">{{ $aula->establecimientoGrado?->etiqueta() }}</td>
                                        <td class="px-6 py-3">{{ $aula->paralelo }}</td>
                                        <td class="sticky right-0 whitespace-nowrap bg-white px-6 py-3">
                                            <form method="POST" action="{{ route('Admin.aulas.destroy', $aula) }}" onsubmit="return confirm('¿Eliminar esta aula?')">
                                                @csrf
                                                @method('delete')
                                                <button type="submit" class="font-medium text-red-600 hover:text-red-500">
                                                    Eliminar
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="px-6 py-8 text-center text-slate-500">
                                            Aún no hay aulas en este periodo.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    @include('admin.aulas._aula-create-modal')
                @endif
            @endif
        </div>
    </div>
</x-app-layout>
