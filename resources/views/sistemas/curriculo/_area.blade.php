@php
    $areaBag = $errors->getBag('area-'.$area->id);
    $areaCodigo = $areaBag->isNotEmpty() ? old('codigo', $area->codigo) : $area->codigo;
    $areaNombre = $areaBag->isNotEmpty() ? old('nombre', $area->nombre) : $area->nombre;
    $areaDescripcion = $areaBag->isNotEmpty() ? old('descripcion', $area->descripcion) : $area->descripcion;
    $areaOrden = $areaBag->isNotEmpty() ? old('orden', $area->orden) : $area->orden;
    $areaLibreta = $areaBag->isNotEmpty()
        ? (bool) old('aparece_en_libreta', $area->aparece_en_libreta)
        : $area->aparece_en_libreta;
    $areaTecnica = $areaBag->isNotEmpty()
        ? (bool) old('es_tecnica', $area->es_tecnica)
        : $area->es_tecnica;
    $porAmbitos = (bool) $subnivel->usa_ambitos;
    $etiquetaGrupo = $porAmbitos ? 'eje' : 'área';
    $etiquetaUnidad = $porAmbitos ? 'ámbito' : 'asignatura';
    $etiquetaUnidades = $porAmbitos ? 'ámbitos' : 'asignaturas';
    $preguntaGrupo = $porAmbitos ? 'este eje' : 'esta área';
    $preguntaUnidad = $porAmbitos ? 'este ámbito' : 'esta asignatura';
    $abrirAsignaturas = (int) ($openAreaId ?? 0) === $area->id && (int) ($editingAreaId ?? 0) !== $area->id;
@endphp

<article class="overflow-hidden rounded-lg border border-slate-200 bg-white">
    <div class="flex flex-col gap-3 px-4 py-3 sm:flex-row sm:items-center sm:justify-between">
        <div class="min-w-0">
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
                    · {{ $area->es_tecnica ? 'Catálogo técnico' : ($porAmbitos ? 'Eje curricular' : 'Área curricular') }}
                    · {{ $area->asignaturas->count() }} {{ $etiquetaUnidades }}
                </span>
        </div>
        <div class="flex items-center gap-3 sm:shrink-0">
            <button type="button" class="font-medium text-indigo-600 hover:text-indigo-500" x-on:click="$dispatch('open-modal', 'asignaturas-{{ $area->id }}')">
                {{ $porAmbitos ? 'Ámbitos' : 'Asignaturas' }}
            </button>
            <button type="button" class="font-medium text-indigo-600 hover:text-indigo-500" @click="editArea({{ $nivel->id }}, {{ $subnivel->id }}, {{ $area->id }})">
                Editar
            </button>
            <form method="POST" action="{{ route('sistemas.curriculo.areas.destroy', [$nivel, $subnivel, $area]) }}" onsubmit="return confirm('¿Eliminar {{ $preguntaGrupo }}?')">
                @csrf
                @method('delete')
                <button type="submit" class="font-medium text-red-600 hover:text-red-500">
                    Eliminar
                </button>
            </form>
        </div>
    </div>

    <form
            method="POST"
            action="{{ route('sistemas.curriculo.areas.update', [$nivel, $subnivel, $area]) }}"
            class="flex flex-col gap-4 border-t border-slate-100 bg-slate-50 px-4 py-4 md:flex-row md:flex-wrap md:items-end sm:px-6"
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
            <input type="hidden" name="es_tecnica" value="0">
            <label class="flex items-center gap-2 text-sm text-slate-700 md:mb-1">
                <input type="checkbox" name="es_tecnica" value="1" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500" @checked($areaTecnica)>
                {{ $porAmbitos ? 'Eje técnico' : 'Área técnica' }}
            </label>
            <div class="flex gap-2">
                <x-primary-button>Guardar</x-primary-button>
                <x-secondary-button type="button" x-on:click="editingAreaId = null">Cancelar</x-secondary-button>
            </div>
    </form>
</article>

<template x-teleport="body">
    <x-modal name="asignaturas-{{ $area->id }}" maxWidth="4xl" :show="$abrirAsignaturas">
        <div class="max-h-[90vh] overflow-y-auto p-6">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <h2 class="text-lg font-semibold text-slate-800">{{ $porAmbitos ? 'Ámbitos' : 'Asignaturas' }}</h2>
                    <p class="mt-1 text-sm text-slate-500">{{ $subnivel->nombre }} · {{ $area->nombre }}</p>
                </div>
                <x-secondary-button type="button" x-on:click="$dispatch('close')">Cerrar</x-secondary-button>
            </div>

            <div class="mt-4 flex justify-end">
            <x-primary-button type="button" x-on:click="$dispatch('open-modal', 'asignatura-create-{{ $area->id }}')">
                Crear {{ $etiquetaUnidad }}
            </x-primary-button>
        </div>

        <div class="overflow-x-auto rounded-lg border border-slate-200 bg-white">
            <table class="min-w-full divide-y divide-slate-200 text-left text-sm">
                <thead class="bg-slate-50 text-slate-600">
                    <tr>
                        <th class="px-4 py-3 font-medium">{{ $porAmbitos ? 'Ámbito' : 'Asignatura' }}</th>
                        <th class="px-4 py-3 font-medium">Código</th>
                        <th class="px-4 py-3 font-medium">Horas</th>
                        @unless ($porAmbitos)
                            <th class="px-4 py-3 font-medium">Calificación</th>
                        @endunless
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
                            $asignaturaEscala = $asignaturaBag->isNotEmpty()
                                ? old('tipo_calificacion', $asignatura->tipo_calificacion?->value)
                                : $asignatura->tipo_calificacion?->value;
                            $asignaturaCriterios = $asignaturaBag->isNotEmpty()
                                ? filter_var(old('usa_criterios'), FILTER_VALIDATE_BOOLEAN)
                                : (bool) $asignatura->usa_criterios;
                        @endphp
                        <tr>
                            <td class="px-4 py-3" colspan="{{ $porAmbitos ? 5 : 6 }}" x-show="editingAsignaturaId === {{ $asignatura->id }}" x-cloak>
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
                                    @unless ($porAmbitos)
                                        @if ($asignatura->nombre === \App\Services\AcompanamientoCivica::ASIGNATURA)
                                            <div class="md:min-w-56 md:max-w-sm">
                                                <x-input-label value="Calificación" />
                                                <p class="mt-1 text-sm text-slate-600">Cualitativa. El Ministerio pide A, EP, I o NE y esta letra no entra al promedio.</p>
                                                <input type="hidden" name="tipo_calificacion" value="{{ \App\Enums\TipoCalificacion::Cualitativa->value }}">
                                            </div>
                                        @else
                                            <div class="md:w-44" x-data="{ escala: @js((string) $asignaturaEscala) }">
                                                <x-input-label :for="'escala-asignatura-'.$asignatura->id" value="Calificación" />
                                                <select
                                                    id="escala-asignatura-{{ $asignatura->id }}"
                                                    name="tipo_calificacion"
                                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                                    x-model="escala"
                                                    required
                                                >
                                                    @foreach (\App\Enums\TipoCalificacion::cases() as $opcion)
                                                        <option value="{{ $opcion->value }}" @selected((string) $asignaturaEscala === $opcion->value)>{{ $opcion->label() }}</option>
                                                    @endforeach
                                                </select>
                                                <x-input-error class="mt-2" :messages="$asignaturaBag->get('tipo_calificacion')" />
                                                <label class="mt-2 flex items-center gap-2 text-sm text-slate-700" x-show="escala === 'cualitativa'" x-cloak>
                                                    <input type="checkbox" class="rounded border-gray-300" name="usa_criterios" value="1" @checked($asignaturaCriterios)>
                                                    Utiliza criterios
                                                </label>
                                            </div>
                                        @endif
                                    @endunless
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
                            @unless ($porAmbitos)
                                <td class="px-4 py-3 text-slate-500">
                                    @if ($asignatura->nombre === \App\Services\AcompanamientoCivica::ASIGNATURA)
                                        Cualitativa
                                        <span class="mt-1 block text-xs text-slate-400">A, EP, I o NE · no entra al promedio</span>
                                    @else
                                        {{ $asignatura->tipo_calificacion?->label() ?? 'Cuantitativa' }}
                                        @if ($asignatura->tipo_calificacion === \App\Enums\TipoCalificacion::Cualitativa && $asignatura->usa_criterios)
                                            · Criterios
                                        @endif
                                    @endif
                                </td>
                            @endunless
                            <td class="px-4 py-3 text-slate-500">{{ $asignatura->aparece_en_libreta ? 'Sí' : 'No' }}</td>
                            <td class="sticky right-0 bg-white px-4 py-3">
                                <div class="flex items-center gap-3">
                                    <button type="button" class="font-medium text-indigo-600 hover:text-indigo-500" @click="editAsignatura({{ $asignatura->id }})">
                                        Editar
                                    </button>
                                    <form method="POST" action="{{ route('sistemas.curriculo.asignaturas.destroy', [$nivel, $subnivel, $area, $asignatura]) }}" onsubmit="return confirm('¿Eliminar {{ $preguntaUnidad }}?')">
                                        @csrf
                                        @method('delete')
                                        <button type="submit" class="font-medium text-red-600 hover:text-red-500">
                                            Eliminar
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @if ($subnivel->usa_ambitos || $subnivel->califica_por_destrezas || $asignatura->usa_criterios || $asignatura->tipo_calificacion === \App\Enums\TipoCalificacion::Cualitativa || $asignatura->destrezas->isNotEmpty())
                            @include('sistemas.curriculo._destrezas-asignatura', [
                                'nivel' => $nivel,
                                'subnivel' => $subnivel,
                                'area' => $area,
                                'asignatura' => $asignatura,
                            ])
                        @endif
                    @empty
                        <tr>
                            <td colspan="{{ $porAmbitos ? 5 : 6 }}" class="px-4 py-6 text-center text-slate-500">
                                {{ $porAmbitos ? 'Este eje aún no tiene ámbitos.' : 'Esta área aún no tiene asignaturas.' }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
            </div>
        </div>
    </x-modal>
</template>

<template x-teleport="body">
    @include('sistemas.curriculo._asignatura-create-modal', [
        'nivel' => $nivel,
        'subnivel' => $subnivel,
        'area' => $area,
    ])
</template>
