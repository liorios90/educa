<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-slate-800">{{ $tipoInsumo->exists ? 'Modificar tipo de Insumo' : 'Nuevo tipo de Insumo' }}</h2>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-xl px-4 sm:px-6 lg:px-8">
            <form method="POST" action="{{ $tipoInsumo->exists ? route('Admin.tipos-insumos.update', $tipoInsumo) : route('Admin.tipos-insumos.store') }}" class="space-y-6 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                @csrf
                @if ($tipoInsumo->exists)
                    @method('patch')
                @endif

                <div>
                    <x-input-label for="nombre" value="Nombre" />
                    <x-text-input id="nombre" name="nombre" type="text" maxlength="255" required autofocus class="mt-1 block w-full" :value="old('nombre', $tipoInsumo->nombre)" />
                    <x-input-error class="mt-2" :messages="$errors->get('nombre')" />
                </div>

                <div>
                    <x-input-label for="descripcion" value="Descripción" />
                    <textarea id="descripcion" name="descripcion" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">{{ old('descripcion', $tipoInsumo->descripcion) }}</textarea>
                    <x-input-error class="mt-2" :messages="$errors->get('descripcion')" />
                </div>

                <div>
                    <x-input-label for="tipo_calificacion" value="Tipo de calificación" />
                    <select id="tipo_calificacion" name="tipo_calificacion" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        @foreach (\App\Enums\TipoCalificacion::cases() as $tipo)
                            <option value="{{ $tipo->value }}" @selected(old('tipo_calificacion', $tipoInsumo->tipo_calificacion) === $tipo->value)>{{ $tipo->label() }}</option>
                        @endforeach
                    </select>
                    <x-input-error class="mt-2" :messages="$errors->get('tipo_calificacion')" />
                </div>

                <label for="activo" class="flex items-center gap-2 text-sm text-slate-700">
                    <input type="hidden" name="activo" value="0">
                    <input id="activo" type="checkbox" name="activo" value="1" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500" @checked(old('activo', $tipoInsumo->activo))>
                    Activo
                </label>

                <div class="flex items-center justify-end gap-4">
                    <a href="{{ route('Admin.tipos-insumos.index') }}" class="text-sm text-slate-600 hover:text-slate-900">Cancelar</a>
                    <x-primary-button>{{ $tipoInsumo->exists ? 'Guardar cambios' : 'Crear tipo' }}</x-primary-button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
