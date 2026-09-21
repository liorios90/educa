@php
    $areaBag = $errors->getBag('area-'.$area->id);
    $asignaturaCreateBag = $errors->getBag('asignatura-create-'.$area->id);
    $areaCodigo = $areaBag->isNotEmpty() ? old('codigo', $area->codigo) : $area->codigo;
    $areaNombre = $areaBag->isNotEmpty() ? old('nombre', $area->nombre) : $area->nombre;
    $areaDescripcion = $areaBag->isNotEmpty() ? old('descripcion', $area->descripcion) : $area->descripcion;
    $areaOrden = $areaBag->isNotEmpty() ? old('orden', $area->orden) : $area->orden;
    $areaLibreta = $areaBag->isNotEmpty()
        ? (bool) old('aparece_en_libreta', $area->aparece_en_libreta)
        : $area->aparece_en_libreta;
    $asignaturaCreateCodigo = $asignaturaCreateBag->isNotEmpty() ? old('codigo') : '';
    $asignaturaCreateNombre = $asignaturaCreateBag->isNotEmpty() ? old('nombre') : '';
    $asignaturaCreateDescripcion = $asignaturaCreateBag->isNotEmpty() ? old('descripcion') : '';
    $asignaturaCreateOrden = $asignaturaCreateBag->isNotEmpty() ? old('orden', 0) : 0;
    $asignaturaCreateHoras = $asignaturaCreateBag->isNotEmpty() ? old('horas_semanales') : '';
    $asignaturaCreateLibreta = $asignaturaCreateBag->isNotEmpty()
        ? (bool) old('aparece_en_libreta', true)
        : true;
@endphp

<article class="overflow-hidden rounded-lg border border-slate-200 bg-white">
    <div class="flex flex-col gap-3 px-4 py-3 sm:flex-row sm:items-center sm:justify-between">
        <button
            type="button"
            class="flex min-w-0 flex-1 items-start gap-3 text-left"
            @click="toggleArea({{ $nivel->id }}, {{ $subnivel->id }}, {{ $area->id }})"
        >
            <span
                class="mt-1 inline-flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-slate-100 text-slate-600 transition"
                :class="{ 'rotate-180 bg-indigo-100 text-indigo-700': openAreaId === {{ $area->id }} }"
            >
                <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
                </svg>
            </span>
            <span class="min-w-0">
                <span class="block font-medium text-slate-800">
                    {{ $area->nombre }}
                    @if ($area->codigo)
                        <span class="ml-1 font-normal text-slate-500">({{ $area->codigo }})</span>
                    @endif
                </span>
                <span class="mt-1 block text-sm text-slate-500">{{ $area->descripcion ?: 'Sin descripción' }}</span>
                <span class="mt-1 block text-xs text-slate-400">
                    Orden {{ $area->orden }}
                    · {{ $area->aparece_en_libreta ? 'En libreta' : 'No en libreta' }}
                    · {{ $area->asignaturas->count() }} asignaturas
                </span>
            </span>
        </button>
        <div class="flex items-center gap-3 sm:shrink-0">
            <button type="button" class="font-medium text-indigo-600 hover:text-indigo-500" @click.stop="editArea({{ $nivel->id }}, {{ $subnivel->id }}, {{ $area->id }})">
                Editar
            </button>
            <form method="POST" action="{{ route('sistemas.curriculo.areas.destroy', [$nivel, $subnivel, $area]) }}" onsubmit="return confirm('¿Eliminar esta área?')">
                @csrf
                @method('delete')
                <button type="submit" class="font-medium text-red-600 hover:text-red-500">
                    Eliminar
                </button>
            </form>
        </div>
    </div>

    <div class="space-y-4 border-t border-slate-100 bg-slate-50 px-4 py-4" x-cloak x-show="openAreaId === {{ $area->id }}">
        <form
            method="POST"
            action="{{ route('sistemas.curriculo.areas.update', [$nivel, $subnivel, $area]) }}"
            class="flex flex-col gap-4 rounded-lg border border-slate-200 bg-white p-4 md:flex-row md:flex-wrap md:items-end"
            x-show="editingAreaId === {{ $area->id }}"
            x-cloak
        >
            @csrf
            @method('patch')
            <div class="md:w-28">
                <x-input-label :for="'codigo-area-'.$area->id" value="Código" />
                <x-text-input :id="'codigo-area-'.$area->id" class="mt-1 block w-full" type="text" name="codigo" :value="$areaCodigo" maxlength="20" />
                <x-input-error class="mt-2" :messages="$areaBag->get('codigo')" />
            </div>
            <div class="md:min-w-40 md:flex-1">
                <x-input-label :for="'nombre-area-'.$area->id" value="Nombre" />
                <x-text-input :id="'nombre-area-'.$area->id" class="mt-1 block w-full" type="text" name="nombre" :value="$areaNombre" required />
                <x-input-error class="mt-2" :messages="$areaBag->get('nombre')" />
            </div>
            <div class="md:min-w-40 md:flex-1">
                <x-input-label :for="'descripcion-area-'.$area->id" value="Descripción" />
                <x-text-input :id="'descripcion-area-'.$area->id" class="mt-1 block w-full" type="text" name="descripcion" :value="$areaDescripcion" />
                <x-input-error class="mt-2" :messages="$areaBag->get('descripcion')" />
            </div>
            <div class="md:w-24">
                <x-input-label :for="'orden-area-'.$area->id" value="Orden" />
                <x-text-input :id="'orden-area-'.$area->id" class="mt-1 block w-full" type="number" name="orden" :value="$areaOrden" min="0" max="999" required />
                <x-input-error class="mt-2" :messages="$areaBag->get('orden')" />
            </div>
            <label class="flex items-center gap-2 text-sm text-slate-700 md:mb-1">
                <input type="hidden" name="aparece_en_libreta" value="0">
                <input
                    id="libreta-area-{{ $area->id }}"
                    type="checkbox"
                    name="aparece_en_libreta"
                    value="1"
                    class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500"
                    @checked($areaLibreta)
                >
                En libreta
            </label>
            <div class="flex gap-2">
                <x-primary-button>Guardar</x-primary-button>
                <x-secondary-button type="button" x-on:click="editingAreaId = null">Cancelar</x-secondary-button>
            </div>
        </form>

        <div class="overflow-x-auto rounded-lg border border-slate-200 bg-white">
            <table class="min-w-full divide-y divide-slate-200 text-left text-sm">
                <thead class="bg-slate-50 text-slate-600">
                    <tr>
                        <th class="px-4 py-3 font-medium">Asignatura</th>
                        <th class="px-4 py-3 font-medium">Código</th>
                        <th class="px-4 py-3 font-medium">Horas</th>
                        <th class="px-4 py-3 font-medium">Libreta</th>
                        <th class="sticky right-0 bg-slate-50 px-4 py-3 font-medium">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-slate-800">
                    @forelse ($area->asignaturas as $asignatura)
                        @php
                            $asignaturaBag = $errors->getBag('asignatura-'.$asignatura->id);
                            $asignaturaCodigo = $asignaturaBag->isNotEmpty() ? old('codigo', $asignatura->codigo) : $asignatura->codigo;
                            $asignaturaNombre = $asignaturaBag->isNotEmpty() ? old('nombre', $asignatura->nombre) : $asignatura->nombre;
                            $asignaturaDescripcion = $asignaturaBag->isNotEmpty() ? old('descripcion', $asignatura->descripcion) : $asignatura->descripcion;
                            $asignaturaOrden = $asignaturaBag->isNotEmpty() ? old('orden', $asignatura->orden) : $asignatura->orden;
                            $asignaturaHoras = $asignaturaBag->isNotEmpty() ? old('horas_semanales', $asignatura->horas_semanales) : $asignatura->horas_semanales;
                            $asignaturaLibreta = $asignaturaBag->isNotEmpty()
                                ? (bool) old('aparece_en_libreta', $asignatura->aparece_en_libreta)
                                : $asignatura->aparece_en_libreta;
                        @endphp
                        <tr>
                            <td class="px-4 py-3" colspan="5" x-show="editingAsignaturaId === {{ $asignatura->id }}" x-cloak>
                                <form method="POST" action="{{ route('sistemas.curriculo.asignaturas.update', [$nivel, $subnivel, $area, $asignatura]) }}" class="flex flex-col gap-4 md:flex-row md:flex-wrap md:items-end">
                                    @csrf
                                    @method('patch')
                                    <div class="md:w-28">
                                        <x-input-label :for="'codigo-asignatura-'.$asignatura->id" value="Código" />
                                        <x-text-input :id="'codigo-asignatura-'.$asignatura->id" class="mt-1 block w-full" type="text" name="codigo" :value="$asignaturaCodigo" maxlength="20" />
                                        <x-input-error class="mt-2" :messages="$asignaturaBag->get('codigo')" />
                                    </div>
                                    <div class="md:min-w-40 md:flex-1">
                                        <x-input-label :for="'nombre-asignatura-'.$asignatura->id" value="Nombre" />
                                        <x-text-input :id="'nombre-asignatura-'.$asignatura->id" class="mt-1 block w-full" type="text" name="nombre" :value="$asignaturaNombre" required />
                                        <x-input-error class="mt-2" :messages="$asignaturaBag->get('nombre')" />
                                    </div>
                                    <div class="md:min-w-40 md:flex-1">
                                        <x-input-label :for="'descripcion-asignatura-'.$asignatura->id" value="Descripción" />
                                        <x-text-input :id="'descripcion-asignatura-'.$asignatura->id" class="mt-1 block w-full" type="text" name="descripcion" :value="$asignaturaDescripcion" />
                                        <x-input-error class="mt-2" :messages="$asignaturaBag->get('descripcion')" />
                                    </div>
                                    <div class="md:w-24">
                                        <x-input-label :for="'orden-asignatura-'.$asignatura->id" value="Orden" />
                                        <x-text-input :id="'orden-asignatura-'.$asignatura->id" class="mt-1 block w-full" type="number" name="orden" :value="$asignaturaOrden" min="0" max="999" required />
                                        <x-input-error class="mt-2" :messages="$asignaturaBag->get('orden')" />
                                    </div>
                                    <div class="md:w-28">
                                        <x-input-label :for="'horas-asignatura-'.$asignatura->id" value="Horas semanales" />
                                        <x-text-input :id="'horas-asignatura-'.$asignatura->id" class="mt-1 block w-full" type="number" name="horas_semanales" :value="$asignaturaHoras" min="0" max="40" />
                                        <x-input-error class="mt-2" :messages="$asignaturaBag->get('horas_semanales')" />
                                    </div>
                                    <label class="flex items-center gap-2 text-sm text-slate-700 md:mb-1">
                                        <input type="hidden" name="aparece_en_libreta" value="0">
                                        <input
                                            id="libreta-asignatura-{{ $asignatura->id }}"
                                            type="checkbox"
                                            name="aparece_en_libreta"
                                            value="1"
                                            class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500"
                                            @checked($asignaturaLibreta)
                                        >
                                        En libreta
                                    </label>
                                    <div class="flex gap-2">
                                        <x-primary-button>Guardar</x-primary-button>
                                        <x-secondary-button type="button" x-on:click="editingAsignaturaId = null">Cancelar</x-secondary-button>
                                    </div>
                                </form>
                            </td>
                        </tr>
                        <tr x-show="editingAsignaturaId !== {{ $asignatura->id }}">
                            <td class="px-4 py-3 font-medium">{{ $asignatura->nombre }}</td>
                            <td class="px-4 py-3 text-slate-500">{{ $asignatura->codigo ?: '—' }}</td>
                            <td class="px-4 py-3 text-slate-500">{{ $asignatura->horas_semanales !== null ? $asignatura->horas_semanales.' h' : '—' }}</td>
                            <td class="px-4 py-3 text-slate-500">{{ $asignatura->aparece_en_libreta ? 'Sí' : 'No' }}</td>
                            <td class="sticky right-0 bg-white px-4 py-3">
                                <div class="flex items-center gap-3">
                                    <button type="button" class="font-medium text-indigo-600 hover:text-indigo-500" @click="editAsignatura({{ $nivel->id }}, {{ $subnivel->id }}, {{ $area->id }}, {{ $asignatura->id }})">
                                        Editar
                                    </button>
                                    <form method="POST" action="{{ route('sistemas.curriculo.asignaturas.destroy', [$nivel, $subnivel, $area, $asignatura]) }}" onsubmit="return confirm('¿Eliminar esta asignatura?')">
                                        @csrf
                                        @method('delete')
                                        <button type="submit" class="font-medium text-red-600 hover:text-red-500">
                                            Eliminar
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-6 text-center text-slate-500">
                                Esta área aún no tiene asignaturas.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <form method="POST" action="{{ route('sistemas.curriculo.asignaturas.store', [$nivel, $subnivel, $area]) }}" class="flex flex-col gap-4 rounded-lg border border-indigo-100 bg-white p-4 md:flex-row md:flex-wrap md:items-end">
            @csrf
            <div class="md:w-28">
                <x-input-label :for="'codigo-asignatura-create-'.$area->id" value="Código" />
                <x-text-input :id="'codigo-asignatura-create-'.$area->id" class="mt-1 block w-full" type="text" name="codigo" :value="$asignaturaCreateCodigo" maxlength="20" />
                <x-input-error class="mt-2" :messages="$asignaturaCreateBag->get('codigo')" />
            </div>
            <div class="md:min-w-40 md:flex-1">
                <x-input-label :for="'nombre-asignatura-create-'.$area->id" value="Nueva asignatura" />
                <x-text-input :id="'nombre-asignatura-create-'.$area->id" class="mt-1 block w-full" type="text" name="nombre" :value="$asignaturaCreateNombre" required />
                <x-input-error class="mt-2" :messages="$asignaturaCreateBag->get('nombre')" />
            </div>
            <div class="md:min-w-40 md:flex-1">
                <x-input-label :for="'descripcion-asignatura-create-'.$area->id" value="Descripción" />
                <x-text-input :id="'descripcion-asignatura-create-'.$area->id" class="mt-1 block w-full" type="text" name="descripcion" :value="$asignaturaCreateDescripcion" />
                <x-input-error class="mt-2" :messages="$asignaturaCreateBag->get('descripcion')" />
            </div>
            <div class="md:w-24">
                <x-input-label :for="'orden-asignatura-create-'.$area->id" value="Orden" />
                <x-text-input :id="'orden-asignatura-create-'.$area->id" class="mt-1 block w-full" type="number" name="orden" :value="$asignaturaCreateOrden" min="0" max="999" required />
                <x-input-error class="mt-2" :messages="$asignaturaCreateBag->get('orden')" />
            </div>
            <div class="md:w-28">
                <x-input-label :for="'horas-asignatura-create-'.$area->id" value="Horas semanales" />
                <x-text-input :id="'horas-asignatura-create-'.$area->id" class="mt-1 block w-full" type="number" name="horas_semanales" :value="$asignaturaCreateHoras" min="0" max="40" />
                <x-input-error class="mt-2" :messages="$asignaturaCreateBag->get('horas_semanales')" />
            </div>
            <label class="flex items-center gap-2 text-sm text-slate-700 md:mb-1">
                <input type="hidden" name="aparece_en_libreta" value="0">
                <input
                    id="libreta-asignatura-create-{{ $area->id }}"
                    type="checkbox"
                    name="aparece_en_libreta"
                    value="1"
                    class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500"
                    @checked($asignaturaCreateLibreta)
                >
                En libreta
            </label>
            <x-primary-button>Crear asignatura</x-primary-button>
        </form>
    </div>
</article>
