@php
    use App\Enums\TipoCalificacion;
@endphp

<x-app-layout>
    @php
        $editingSubnivelId = null;
        $openGradosId = $openGradosId ?? null;
        $subnivelCreateBag = $errors->getBag('subnivel-create-'.$nivel->id);
        $subnivelCreateNombre = $subnivelCreateBag->isNotEmpty() ? old('nombre') : '';
        $subnivelCreateSiglas = $subnivelCreateBag->isNotEmpty() ? old('siglas') : '';
        $subnivelCreateDescripcion = $subnivelCreateBag->isNotEmpty() ? old('descripcion') : '';
        $subnivelCreateTipo = $subnivelCreateBag->isNotEmpty()
            ? old('tipo_calificacion')
            : TipoCalificacion::Calificacion->value;

        foreach ($nivel->subniveles as $subnivel) {
            if ($errors->getBag('subnivel-'.$subnivel->id)->isNotEmpty()) {
                $editingSubnivelId = $subnivel->id;
            }

            if ($errors->getBag('grado-create-'.$subnivel->id)->isNotEmpty()) {
                $openGradosId = $subnivel->id;
            }

            foreach ($subnivel->grados as $grado) {
                if ($errors->getBag('grado-'.$grado->id)->isNotEmpty()) {
                    $openGradosId = $subnivel->id;
                }
            }
        }
    @endphp

    <x-slot name="header">
        <div>
            <h2 class="text-xl font-semibold leading-tight text-slate-800">
                Subniveles
            </h2>
            <p class="mt-1 text-sm text-slate-500">
                {{ $nivel->nombre }}
            </p>
        </div>
    </x-slot>

    <div class="py-8" x-data="{ editingSubnivelId: @js($editingSubnivelId) }">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            @include('sistemas.estructura._status')

            <p class="mb-4 text-sm text-slate-500">
                <a href="{{ route('sistemas.estructura') }}" class="font-medium text-indigo-600 hover:text-indigo-500">Niveles</a>
                <span class="text-slate-400"> / </span>
                {{ $nivel->nombre }}
            </p>

            <section class="mb-6 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <h3 class="text-base font-semibold text-slate-800">Nuevo subnivel</h3>
                <form method="POST" action="{{ route('sistemas.estructura.subniveles.store', $nivel) }}" class="mt-4 flex flex-col gap-4 md:flex-row md:flex-wrap md:items-end">
                    @csrf
                    <div class="md:min-w-40 md:flex-1">
                        <x-input-label :for="'nombre-subnivel-create-'.$nivel->id" value="Nombre" />
                        <x-text-input :id="'nombre-subnivel-create-'.$nivel->id" class="mt-1 block w-full" type="text" name="nombre" :value="$subnivelCreateNombre" required />
                        <x-input-error class="mt-2" :messages="$subnivelCreateBag->get('nombre')" />
                    </div>
                    <div class="md:w-28">
                        <x-input-label :for="'siglas-subnivel-create-'.$nivel->id" value="Siglas" />
                        <x-text-input :id="'siglas-subnivel-create-'.$nivel->id" class="mt-1 block w-full" type="text" name="siglas" :value="$subnivelCreateSiglas" maxlength="10" />
                        <x-input-error class="mt-2" :messages="$subnivelCreateBag->get('siglas')" />
                    </div>
                    <div class="md:min-w-48">
                        <x-input-label :for="'tipo-calificacion-subnivel-create-'.$nivel->id" value="Tipo de calificación" />
                        <select
                            id="tipo-calificacion-subnivel-create-{{ $nivel->id }}"
                            name="tipo_calificacion"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            required
                        >
                            <option value="">Selecciona un tipo</option>
                            @foreach (TipoCalificacion::cases() as $option)
                                <option value="{{ $option->value }}" @selected((string) $subnivelCreateTipo === $option->value)>
                                    {{ $option->label() }}
                                </option>
                            @endforeach
                        </select>
                        <x-input-error class="mt-2" :messages="$subnivelCreateBag->get('tipo_calificacion')" />
                    </div>
                    <div class="md:min-w-40 md:flex-1">
                        <x-input-label :for="'descripcion-subnivel-create-'.$nivel->id" value="Descripción" />
                        <x-text-input :id="'descripcion-subnivel-create-'.$nivel->id" class="mt-1 block w-full" type="text" name="descripcion" :value="$subnivelCreateDescripcion" />
                        <x-input-error class="mt-2" :messages="$subnivelCreateBag->get('descripcion')" />
                    </div>
                    <x-primary-button>Crear subnivel</x-primary-button>
                </form>
            </section>

            <div class="flex flex-col gap-4">
                @forelse ($nivel->subniveles as $subnivel)
                    @include('sistemas.estructura._subnivel', ['nivel' => $nivel, 'subnivel' => $subnivel])
                    @include('sistemas.estructura._grados-modal', [
                        'nivel' => $nivel,
                        'subnivel' => $subnivel,
                        'openGradosId' => $openGradosId,
                    ])
                @empty
                    <p class="rounded-2xl border border-slate-200 bg-white px-6 py-8 text-center text-slate-500">
                        Este nivel aún no tiene subniveles.
                    </p>
                @endforelse
            </div>

            <div class="mt-6">
                <a
                    href="{{ route('sistemas.estructura') }}"
                    class="inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-xs font-semibold uppercase tracking-widest text-gray-700 shadow-sm hover:bg-gray-50"
                >
                    Volver
                </a>
            </div>
        </div>
    </div>
</x-app-layout>
