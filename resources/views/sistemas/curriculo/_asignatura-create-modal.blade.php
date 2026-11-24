@php
    $asignaturaCreateBag = $errors->getBag('asignatura-create-'.$area->id);
    $asignaturaCreateCodigo = $asignaturaCreateBag->isNotEmpty() ? old('codigo') : '';
    $asignaturaCreateNombre = $asignaturaCreateBag->isNotEmpty() ? old('nombre') : '';
    $asignaturaCreateDescripcion = $asignaturaCreateBag->isNotEmpty() ? old('descripcion') : '';
    $asignaturaCreateOrden = $asignaturaCreateBag->isNotEmpty() ? old('orden', 0) : 0;
    $asignaturaCreateHoras = $asignaturaCreateBag->isNotEmpty() ? old('horas_semanales') : '';
    $asignaturaCreateLibreta = $asignaturaCreateBag->isNotEmpty()
        ? (bool) old('aparece_en_libreta', true)
        : true;
    $asignaturaCreateEscala = $asignaturaCreateBag->isNotEmpty() ? old('tipo_calificacion', 'cuantitativa') : 'cuantitativa';
    $asignaturaCreateCriterios = $asignaturaCreateBag->isNotEmpty()
        && filter_var(old('usa_criterios'), FILTER_VALIDATE_BOOLEAN);
@endphp

<x-modal name="asignatura-create-{{ $area->id }}" maxWidth="2xl" :show="$asignaturaCreateBag->isNotEmpty()" focusable>
    <form method="POST" action="{{ route('sistemas.curriculo.asignaturas.store', [$nivel, $subnivel, $area]) }}" class="p-6">
        @csrf

        <h3 class="text-lg font-semibold text-slate-800">{{ $subnivel->usa_ambitos ? 'Nuevo ámbito' : 'Nueva asignatura' }}</h3>
        <p class="mt-1 text-sm text-slate-500">
            {{ $area->nombre }}
        </p>

        <div class="mt-4 flex flex-col gap-4">
            <div>
                <x-input-label :for="'nombre-asignatura-create-'.$area->id" value="Nombre" />
                <x-text-input :id="'nombre-asignatura-create-'.$area->id" class="mt-1 block w-full" type="text" name="nombre" :value="$asignaturaCreateNombre" required />
                <x-input-error class="mt-2" :messages="$asignaturaCreateBag->get('nombre')" />
            </div>
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <div>
                    <x-input-label :for="'codigo-asignatura-create-'.$area->id" value="Código" />
                    <x-text-input :id="'codigo-asignatura-create-'.$area->id" class="mt-1 block w-full" type="text" name="codigo" :value="$asignaturaCreateCodigo" maxlength="20" />
                    <x-input-error class="mt-2" :messages="$asignaturaCreateBag->get('codigo')" />
                </div>
                <div>
                    <x-input-label :for="'orden-asignatura-create-'.$area->id" value="Orden" />
                    <x-text-input :id="'orden-asignatura-create-'.$area->id" class="mt-1 block w-full" type="number" name="orden" :value="$asignaturaCreateOrden" min="0" max="999" required />
                    <x-input-error class="mt-2" :messages="$asignaturaCreateBag->get('orden')" />
                </div>
            </div>
            <div>
                <x-input-label :for="'descripcion-asignatura-create-'.$area->id" value="Descripción" />
                <x-text-input :id="'descripcion-asignatura-create-'.$area->id" class="mt-1 block w-full" type="text" name="descripcion" :value="$asignaturaCreateDescripcion" />
                <x-input-error class="mt-2" :messages="$asignaturaCreateBag->get('descripcion')" />
            </div>
            <div>
                <x-input-label :for="'horas-asignatura-create-'.$area->id" value="Horas semanales" />
                <x-text-input :id="'horas-asignatura-create-'.$area->id" class="mt-1 block w-full" type="number" name="horas_semanales" :value="$asignaturaCreateHoras" min="0" max="40" />
                <x-input-error class="mt-2" :messages="$asignaturaCreateBag->get('horas_semanales')" />
            </div>
            @unless ($subnivel->usa_ambitos)
                <div x-data="{ escala: @js((string) $asignaturaCreateEscala) }">
                    <x-input-label :for="'escala-asignatura-create-'.$area->id" value="Calificación" />
                    <select
                        id="escala-asignatura-create-{{ $area->id }}"
                        name="tipo_calificacion"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        x-model="escala"
                        required
                    >
                        @foreach (\App\Enums\TipoCalificacion::cases() as $opcion)
                            <option value="{{ $opcion->value }}" @selected((string) $asignaturaCreateEscala === $opcion->value)>{{ $opcion->label() }}</option>
                        @endforeach
                    </select>
                    <x-input-error class="mt-2" :messages="$asignaturaCreateBag->get('tipo_calificacion')" />
                    <label class="mt-2 flex items-center gap-2 text-sm text-slate-700" x-show="escala === 'cualitativa'" x-cloak>
                        <input type="checkbox" class="rounded border-gray-300" name="usa_criterios" value="1" @checked($asignaturaCreateCriterios)>
                        Utiliza criterios
                    </label>
                </div>
            @endunless
            <label class="flex items-center gap-2 text-sm text-slate-700">
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
        </div>

        <div class="mt-6 flex justify-end gap-2">
            <x-secondary-button x-on:click="$dispatch('close')">Cancelar</x-secondary-button>
            <x-primary-button>{{ $subnivel->usa_ambitos ? 'Crear ámbito' : 'Crear asignatura' }}</x-primary-button>
        </div>
    </form>
</x-modal>
