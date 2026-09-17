@php
    use App\Enums\TipoCalificacion;

    $subnivelBag = $errors->getBag('subnivel-'.$subnivel->id);
    $gradoCreateBag = $errors->getBag('grado-create-'.$subnivel->id);
    $subnivelNombre = $subnivelBag->isNotEmpty() ? old('nombre', $subnivel->nombre) : $subnivel->nombre;
    $subnivelSiglas = $subnivelBag->isNotEmpty() ? old('siglas', $subnivel->siglas) : $subnivel->siglas;
    $subnivelDescripcion = $subnivelBag->isNotEmpty() ? old('descripcion', $subnivel->descripcion) : $subnivel->descripcion;
    $subnivelTipo = $subnivelBag->isNotEmpty()
        ? old('tipo_calificacion', $subnivel->tipo_calificacion?->value)
        : $subnivel->tipo_calificacion?->value;
    $gradoCreateNombre = $gradoCreateBag->isNotEmpty() ? old('nombre') : '';
    $gradoCreateSiglas = $gradoCreateBag->isNotEmpty() ? old('siglas') : '';
    $gradoCreateDescripcion = $gradoCreateBag->isNotEmpty() ? old('descripcion') : '';
@endphp

<section class="overflow-hidden rounded-xl border border-slate-200 bg-white">
    <div class="flex flex-col gap-3 px-4 py-3 sm:flex-row sm:items-center sm:justify-between">
        <button
            type="button"
            class="flex min-w-0 flex-1 items-start gap-3 text-left"
            @click="toggleSubnivel({{ $nivel->id }}, {{ $subnivel->id }})"
        >
            <span
                class="mt-1 inline-flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-slate-100 text-slate-600 transition"
                :class="{ 'rotate-180 bg-indigo-100 text-indigo-700': openSubnivelId === {{ $subnivel->id }} }"
            >
                <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
                </svg>
            </span>
            <span class="min-w-0">
                <span class="block font-medium text-slate-800">
                    {{ $subnivel->nombre }}
                    @if ($subnivel->siglas)
                        <span class="ml-1 font-normal text-slate-500">({{ $subnivel->siglas }})</span>
                    @endif
                </span>
                <span class="mt-1 block text-sm text-slate-500">{{ $subnivel->descripcion ?: 'Sin descripción' }}</span>
                <span class="mt-1 block text-xs text-slate-400">
                    {{ $subnivel->tipo_calificacion?->label() ?? 'Sin tipo de calificación' }}
                    · {{ $subnivel->grados->count() }} grados
                </span>
            </span>
        </button>
        <div class="flex items-center gap-3 sm:shrink-0">
            <button type="button" class="font-medium text-indigo-600 hover:text-indigo-500" @click.stop="editSubnivel({{ $nivel->id }}, {{ $subnivel->id }})">
                Editar
            </button>
            <form method="POST" action="{{ route('sistemas.estructura.subniveles.destroy', [$nivel, $subnivel]) }}" onsubmit="return confirm('¿Eliminar este subnivel?')">
                @csrf
                @method('delete')
                <button type="submit" class="font-medium text-red-600 hover:text-red-500">
                    Eliminar
                </button>
            </form>
        </div>
    </div>

    <div class="space-y-4 border-t border-slate-100 bg-slate-50 px-4 py-4" x-cloak x-show="openSubnivelId === {{ $subnivel->id }}">
        <form
            method="POST"
            action="{{ route('sistemas.estructura.subniveles.update', [$nivel, $subnivel]) }}"
            class="flex flex-col gap-4 rounded-lg border border-slate-200 bg-white p-4 md:flex-row md:flex-wrap md:items-end"
            x-show="editingSubnivelId === {{ $subnivel->id }}"
            x-cloak
        >
            @csrf
            @method('patch')
            <div class="md:min-w-40 md:flex-1">
                <x-input-label :for="'nombre-subnivel-'.$subnivel->id" value="Nombre" />
                <x-text-input :id="'nombre-subnivel-'.$subnivel->id" class="mt-1 block w-full" type="text" name="nombre" :value="$subnivelNombre" required />
                <x-input-error class="mt-2" :messages="$subnivelBag->get('nombre')" />
            </div>
            <div class="md:w-28">
                <x-input-label :for="'siglas-subnivel-'.$subnivel->id" value="Siglas" />
                <x-text-input :id="'siglas-subnivel-'.$subnivel->id" class="mt-1 block w-full" type="text" name="siglas" :value="$subnivelSiglas" maxlength="10" />
                <x-input-error class="mt-2" :messages="$subnivelBag->get('siglas')" />
            </div>
            <div class="md:min-w-48">
                <x-input-label :for="'tipo-calificacion-subnivel-'.$subnivel->id" value="Tipo de calificación" />
                <select
                    id="tipo-calificacion-subnivel-{{ $subnivel->id }}"
                    name="tipo_calificacion"
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                    required
                >
                    <option value="">Selecciona un tipo</option>
                    @foreach (TipoCalificacion::cases() as $option)
                        <option value="{{ $option->value }}" @selected((string) $subnivelTipo === $option->value)>
                            {{ $option->label() }}
                        </option>
                    @endforeach
                </select>
                <x-input-error class="mt-2" :messages="$subnivelBag->get('tipo_calificacion')" />
            </div>
            <div class="md:min-w-40 md:flex-1">
                <x-input-label :for="'descripcion-subnivel-'.$subnivel->id" value="Descripción" />
                <x-text-input :id="'descripcion-subnivel-'.$subnivel->id" class="mt-1 block w-full" type="text" name="descripcion" :value="$subnivelDescripcion" />
                <x-input-error class="mt-2" :messages="$subnivelBag->get('descripcion')" />
            </div>
            <div class="flex gap-2">
                <x-primary-button>Guardar</x-primary-button>
                <x-secondary-button type="button" x-on:click="editingSubnivelId = null">Cancelar</x-secondary-button>
            </div>
        </form>

        <div class="overflow-hidden rounded-lg border border-slate-200 bg-white">
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
                        @php
                            $gradoBag = $errors->getBag('grado-'.$grado->id);
                            $gradoNombre = $gradoBag->isNotEmpty() ? old('nombre', $grado->nombre) : $grado->nombre;
                            $gradoSiglas = $gradoBag->isNotEmpty() ? old('siglas', $grado->siglas) : $grado->siglas;
                            $gradoDescripcion = $gradoBag->isNotEmpty() ? old('descripcion', $grado->descripcion) : $grado->descripcion;
                        @endphp
                        <tr>
                            <td class="px-4 py-3" colspan="4" x-show="editingGradoId === {{ $grado->id }}" x-cloak>
                                <form method="POST" action="{{ route('sistemas.estructura.grados.update', [$nivel, $subnivel, $grado]) }}" class="flex flex-col gap-4 md:flex-row md:flex-wrap md:items-end">
                                    @csrf
                                    @method('patch')
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
                                    <div class="flex gap-2">
                                        <x-primary-button>Guardar</x-primary-button>
                                        <x-secondary-button type="button" x-on:click="editingGradoId = null">Cancelar</x-secondary-button>
                                    </div>
                                </form>
                            </td>
                        </tr>
                        <tr x-show="editingGradoId !== {{ $grado->id }}">
                            <td class="px-4 py-3 font-medium">{{ $grado->nombre }}</td>
                            <td class="px-4 py-3 text-slate-500">{{ $grado->siglas ?: '—' }}</td>
                            <td class="px-4 py-3 text-slate-500">{{ $grado->descripcion ?: '—' }}</td>
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-3">
                                    <button type="button" class="font-medium text-indigo-600 hover:text-indigo-500" @click="editGrado({{ $nivel->id }}, {{ $subnivel->id }}, {{ $grado->id }})">
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

        <form method="POST" action="{{ route('sistemas.estructura.grados.store', [$nivel, $subnivel]) }}" class="flex flex-col gap-4 rounded-lg border border-indigo-100 bg-white p-4 md:flex-row md:flex-wrap md:items-end">
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
</section>
