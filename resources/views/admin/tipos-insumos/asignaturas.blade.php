<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <h2 class="text-xl font-semibold leading-tight text-slate-800">Asignaturas · {{ $tipoInsumo->nombre }}</h2>
                <p class="mt-1 text-sm text-slate-500">Selecciona las asignaturas de las aulas que pertenecen a este tipo de Insumo.</p>
            </div>
            <a href="{{ route('Admin.tipos-insumos.index') }}" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">Volver a Tipos de Insumo</a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-5 px-4 sm:px-6 lg:px-8" x-data="{ buscar: '' }">
            @if (session('status') === 'tipo-insumo-asignaturas-updated')
                <p class="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm font-medium text-green-800">Asignaturas actualizadas correctamente.</p>
            @endif

            @if ($periodoAbierto)
                <p class="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm font-medium text-amber-900">El periodo está aperturado. No se pueden cambiar las asignaciones.</p>
            @endif

            @if ($errors->has('asignaciones'))
                <p class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm font-medium text-red-800">{{ $errors->first('asignaciones') }}</p>
            @endif

            <form method="POST" action="{{ route('Admin.tipos-insumos.asignaturas.update', $tipoInsumo) }}" class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                @csrf
                @method('put')
                <input type="hidden" name="orden" value="{{ $orden }}">
                <input type="hidden" name="direccion" value="{{ $direccion }}">
                <input type="hidden" name="pagina" value="{{ $asignaciones->currentPage() }}">

                <div class="flex flex-wrap items-center justify-between gap-4 border-b border-slate-200 px-5 py-4">
                    <div>
                        <h3 class="font-semibold text-slate-900">Asignaturas de las aulas</h3>
                        <p class="mt-1 text-sm text-slate-500">El tipo que aparece en cada fila es el tipo actualmente asignado.</p>
                    </div>
                    <label class="flex items-center gap-2 text-sm text-slate-600">
                        <span>Buscar</span>
                        <input type="search" x-model.debounce.150ms="buscar" class="w-56 rounded-lg border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500" placeholder="Asignatura o grado">
                    </label>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 text-left text-sm">
                        <thead class="bg-slate-50 text-slate-600">
                            <tr>
                                @foreach (['id' => 'ID', 'asignatura' => 'Asignatura', 'grado' => 'Grado / aula', 'tipo' => 'Tipo asignado'] as $columna => $titulo)
                                    @php($sentido = $orden === $columna && $direccion === 'asc' ? 'desc' : 'asc')
                                    <th scope="col" class="whitespace-nowrap px-5 py-3 font-medium">
                                        <a href="{{ request()->fullUrlWithQuery(['orden' => $columna, 'direccion' => $sentido, 'page' => 1]) }}" class="inline-flex items-center gap-1 hover:text-indigo-700">
                                            {{ $titulo }}
                                            <span aria-hidden="true">{{ $orden === $columna ? ($direccion === 'asc' ? '↑' : '↓') : '↕' }}</span>
                                        </a>
                                    </th>
                                @endforeach
                                <th scope="col" class="px-5 py-3 text-center font-medium">Pertenece a este tipo</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-slate-800">
                            @forelse ($asignaciones as $asignacion)
                                <tr data-buscar="{{ \Illuminate\Support\Str::lower($asignacion['id'].' '.$asignacion['asignatura'].' '.$asignacion['grado'].' '.$asignacion['tipoAsignado']) }}" x-show="buscar === '' || $el.dataset.buscar.includes(buscar.toLocaleLowerCase())">
                                    <td class="whitespace-nowrap px-5 py-3 text-slate-600">{{ $asignacion['id'] }}</td>
                                    <td class="px-5 py-3 font-medium">{{ $asignacion['asignatura'] }}</td>
                                    <td class="px-5 py-3">{{ $asignacion['grado'] }}</td>
                                    <td class="px-5 py-3">{{ $asignacion['tipoAsignado'] }}</td>
                                    <td class="px-5 py-3 text-center">
                                        <input type="hidden" name="visibles[]" value="{{ $asignacion['key'] }}">
                                        <input
                                            type="checkbox"
                                            name="asignaciones[]"
                                            value="{{ $asignacion['key'] }}"
                                            @checked($asignacion['estaAsignada'])
                                            @disabled($periodoAbierto || ($asignacion['estaAsignada'] && ! $asignacion['pertenece']))
                                            aria-label="{{ $asignacion['asignatura'] }} de {{ $asignacion['grado'] }} pertenece a {{ $tipoInsumo->nombre }}"
                                            class="rounded border-slate-300 text-indigo-600 shadow-sm focus:ring-indigo-500"
                                        >
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-5 py-10 text-center text-slate-500">No hay asignaturas configuradas para las aulas del periodo activo.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if ($asignaciones->count() > 0 && ! $periodoAbierto)
                    <div class="flex justify-end border-t border-slate-200 px-5 py-4">
                        <x-primary-button>Guardar asignaturas</x-primary-button>
                    </div>
                @endif
            </form>
            @if ($asignaciones->total() > 0)
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <p class="text-sm text-slate-500">Mostrando {{ $asignaciones->firstItem() }}–{{ $asignaciones->lastItem() }} de {{ $asignaciones->total() }} asignaturas.</p>
                    {{ $asignaciones->links() }}
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
