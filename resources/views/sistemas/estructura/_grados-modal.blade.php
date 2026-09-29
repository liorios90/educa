@php
    $gradoCreateBag = $errors->getBag('grado-create-'.$subnivel->id);
    $gradoCreateNombre = $gradoCreateBag->isNotEmpty() ? old('nombre') : '';
    $gradoCreateSiglas = $gradoCreateBag->isNotEmpty() ? old('siglas') : '';
    $gradoCreateDescripcion = $gradoCreateBag->isNotEmpty() ? old('descripcion') : '';
    $editingGradoId = null;

    foreach ($subnivel->grados as $grado) {
        if ($errors->getBag('grado-'.$grado->id)->isNotEmpty()) {
            $editingGradoId = $grado->id;
        }
    }
@endphp

<x-modal name="grados-{{ $subnivel->id }}" maxWidth="4xl" :show="$openGradosId === $subnivel->id">
    <div
        class="max-h-[90vh] overflow-y-auto p-6"
        x-data="{ editingGradoId: @js($editingGradoId) }"
        x-on:keydown.escape.window="editingGradoId = null"
    >
        <div class="flex items-start justify-between gap-4">
            <div>
                <h2 class="text-lg font-semibold text-slate-800" x-show="! editingGradoId">Grados</h2>
                <h2 class="text-lg font-semibold text-slate-800" x-show="editingGradoId" x-cloak>Editar grado</h2>
                <p class="mt-1 text-sm text-slate-500">{{ $nivel->nombre }} · {{ $subnivel->nombre }}</p>
            </div>
            <x-secondary-button type="button" x-on:click="editingGradoId = null; $dispatch('close')">Cerrar</x-secondary-button>
        </div>

        <div class="mt-4 flex flex-col gap-4" x-show="! editingGradoId">
            <div class="overflow-hidden rounded-xl border border-slate-200">
                <table class="min-w-full divide-y divide-slate-200 text-left text-sm">
                    <thead class="bg-slate-50 text-slate-600">
                        <tr>
                            <th class="px-4 py-3 font-medium">Grado</th>
                            <th class="px-4 py-3 font-medium">Siglas</th>
                            <th class="px-4 py-3 font-medium">Descripción</th>
                            <th class="px-4 py-3 font-medium">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-slate-800">
                        @forelse ($subnivel->grados as $grado)
                            <tr>
                                <td class="px-4 py-3 font-medium">{{ $grado->nombre }}</td>
                                <td class="px-4 py-3 text-slate-500">{{ $grado->siglas ?: '—' }}</td>
                                <td class="px-4 py-3 text-slate-500">{{ $grado->descripcion ?: '—' }}</td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-3">
                                        <button type="button" class="font-medium text-indigo-600 hover:text-indigo-500" @click="editingGradoId = {{ $grado->id }}">
                                            Editar
                                        </button>
                                        <form method="POST" action="{{ route('sistemas.estructura.grados.destroy', [$nivel, $subnivel, $grado]) }}" onsubmit="return confirm('¿Eliminar este grado?')">
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
                                <td colspan="4" class="px-4 py-6 text-center text-slate-500">
                                    Este subnivel aún no tiene grados.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <form method="POST" action="{{ route('sistemas.estructura.grados.store', [$nivel, $subnivel]) }}" class="flex flex-col gap-4 rounded-xl border border-indigo-100 bg-slate-50 p-4 md:flex-row md:flex-wrap md:items-end">
                @csrf
                <div class="md:min-w-40 md:flex-1">
                    <x-input-label :for="'nombre-grado-create-'.$subnivel->id" value="Nuevo grado" />
                    <x-text-input :id="'nombre-grado-create-'.$subnivel->id" class="mt-1 block w-full" type="text" name="nombre" :value="$gradoCreateNombre" required />
                    <x-input-error class="mt-2" :messages="$gradoCreateBag->get('nombre')" />
                </div>
                <div class="md:w-28">
                    <x-input-label :for="'siglas-grado-create-'.$subnivel->id" value="Siglas" />
                    <x-text-input :id="'siglas-grado-create-'.$subnivel->id" class="mt-1 block w-full" type="text" name="siglas" :value="$gradoCreateSiglas" maxlength="10" />
                    <x-input-error class="mt-2" :messages="$gradoCreateBag->get('siglas')" />
                </div>
                <div class="md:min-w-40 md:flex-1">
                    <x-input-label :for="'descripcion-grado-create-'.$subnivel->id" value="Descripción" />
                    <x-text-input :id="'descripcion-grado-create-'.$subnivel->id" class="mt-1 block w-full" type="text" name="descripcion" :value="$gradoCreateDescripcion" />
                    <x-input-error class="mt-2" :messages="$gradoCreateBag->get('descripcion')" />
                </div>
                <x-primary-button>Crear grado</x-primary-button>
            </form>
        </div>

        @foreach ($subnivel->grados as $grado)
            @php
                $gradoBag = $errors->getBag('grado-'.$grado->id);
                $gradoNombre = $gradoBag->isNotEmpty() ? old('nombre', $grado->nombre) : $grado->nombre;
                $gradoSiglas = $gradoBag->isNotEmpty() ? old('siglas', $grado->siglas) : $grado->siglas;
                $gradoDescripcion = $gradoBag->isNotEmpty() ? old('descripcion', $grado->descripcion) : $grado->descripcion;
            @endphp
            <form
                method="POST"
                action="{{ route('sistemas.estructura.grados.update', [$nivel, $subnivel, $grado]) }}"
                class="mt-4 flex flex-col gap-4 rounded-xl border border-slate-200 bg-slate-50 p-4"
                x-show="editingGradoId === {{ $grado->id }}"
                x-cloak
            >
                @csrf
                @method('patch')
                <div class="flex flex-col gap-4 md:flex-row md:flex-wrap md:items-end">
                    <div class="md:min-w-40 md:flex-1">
                        <x-input-label :for="'nombre-grado-'.$grado->id" value="Nombre" />
                        <x-text-input :id="'nombre-grado-'.$grado->id" class="mt-1 block w-full" type="text" name="nombre" :value="$gradoNombre" required />
                        <x-input-error class="mt-2" :messages="$gradoBag->get('nombre')" />
                    </div>
                    <div class="md:w-28">
                        <x-input-label :for="'siglas-grado-'.$grado->id" value="Siglas" />
                        <x-text-input :id="'siglas-grado-'.$grado->id" class="mt-1 block w-full" type="text" name="siglas" :value="$gradoSiglas" maxlength="10" />
                        <x-input-error class="mt-2" :messages="$gradoBag->get('siglas')" />
                    </div>
                    <div class="md:min-w-40 md:flex-1">
                        <x-input-label :for="'descripcion-grado-'.$grado->id" value="Descripción" />
                        <x-text-input :id="'descripcion-grado-'.$grado->id" class="mt-1 block w-full" type="text" name="descripcion" :value="$gradoDescripcion" />
                        <x-input-error class="mt-2" :messages="$gradoBag->get('descripcion')" />
                    </div>
                </div>
                <div class="flex gap-2">
                    <x-primary-button>Guardar</x-primary-button>
                    <x-secondary-button type="button" x-on:click="editingGradoId = null">Cancelar</x-secondary-button>
                </div>
            </form>
        @endforeach
    </div>
</x-modal>
