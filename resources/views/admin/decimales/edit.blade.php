<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="text-xl font-semibold leading-tight text-slate-800">Decimales</h2>
            <p class="mt-1 text-sm text-slate-500">{{ $establecimiento->nombre }}</p>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-xl px-4 sm:px-6 lg:px-8">
            @if (session('status') === 'decimales-updated')
                <p class="mb-4 text-sm font-medium text-green-700">La configuración de decimales se guardó correctamente.</p>
            @endif

            <form method="POST" action="{{ route('Admin.decimales.update') }}" class="space-y-6 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                @csrf
                @method('PUT')

                <p class="text-sm text-slate-600">
                    Esta configuración se usa en todos los cálculos de calificaciones del instituto: insumos, exámenes, proyectos y promedios.
                </p>

                <div>
                    <x-input-label for="decimales_calificacion" value="Cantidad de decimales" />
                    <select
                        id="decimales_calificacion"
                        name="decimales_calificacion"
                        required
                        class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                    >
                        @foreach (range(0, 4) as $cantidad)
                            <option value="{{ $cantidad }}" @selected((int) old('decimales_calificacion', $establecimiento->decimales_calificacion) === $cantidad)>{{ $cantidad }}</option>
                        @endforeach
                    </select>
                    <x-input-error class="mt-2" :messages="$errors->get('decimales_calificacion')" />
                </div>

                <div>
                    <x-input-label for="modo_decimal" value="Tratamiento de decimales" />
                    <select
                        id="modo_decimal"
                        name="modo_decimal"
                        required
                        class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                    >
                        @foreach ($modos as $modo)
                            <option value="{{ $modo->value }}" @selected(old('modo_decimal', $establecimiento->modo_decimal?->value) === $modo->value)>{{ $modo->label() }}</option>
                        @endforeach
                    </select>
                    <p class="mt-2 text-sm text-slate-500">Ejemplo con 5.256 y dos decimales: aproximar da 5.26 y truncar da 5.25.</p>
                    <x-input-error class="mt-2" :messages="$errors->get('modo_decimal')" />
                </div>

                <x-primary-button>Guardar configuración</x-primary-button>
            </form>
        </div>
    </div>
</x-app-layout>
