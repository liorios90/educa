@php
    $tipo = old('tipo', $tipo);
@endphp

<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-slate-800">
            Importar padres o alumnos
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
            <form
                method="POST"
                action="{{ route('Admin.padres.import.store') }}"
                enctype="multipart/form-data"
                class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm"
                x-data="{ tipo: @js($tipo) }"
            >
                @csrf

                <div class="space-y-4">
                    <fieldset>
                        <legend class="block text-sm font-medium text-slate-700">Qué deseas importar</legend>
                        <div class="mt-3 flex flex-wrap gap-6">
                            <label class="flex items-center gap-2 text-sm text-slate-700">
                                <input
                                    type="radio"
                                    name="tipo"
                                    value="padres"
                                    class="border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500"
                                    x-model="tipo"
                                    @checked($tipo === 'padres')
                                >
                                Padres
                            </label>
                            <label class="flex items-center gap-2 text-sm text-slate-700">
                                <input
                                    type="radio"
                                    name="tipo"
                                    value="alumnos"
                                    class="border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500"
                                    x-model="tipo"
                                    @checked($tipo === 'alumnos')
                                >
                                Alumnos
                            </label>
                        </div>
                        <x-input-error class="mt-2" :messages="$errors->get('tipo')" />
                    </fieldset>

                    <p class="text-sm text-slate-600">
                        Sube un Excel (.xlsx) o CSV del instituto. Las columnas deben ser:
                        <span class="font-medium text-slate-800">tipo_identificacion</span>,
                        <span class="font-medium text-slate-800">identificacion</span>,
                        <span class="font-medium text-slate-800">email</span>,
                        <span class="font-medium text-slate-800">nombres</span>,
                        <span class="font-medium text-slate-800">apellidos</span><span x-show="tipo === 'alumnos'"> y
                        <span class="font-medium text-slate-800">identificacion_representante</span></span>.
                    </p>
                    <p class="text-xs text-slate-500" x-show="tipo === 'padres'">
                        En tipo_identificacion usa C (cédula), P (pasaporte) o R (RUC). La contraseña inicial de cada cuenta será la identificación. Los campos que no vienen en el archivo se guardan como pendientes para completarlos después.
                    </p>
                    <p class="text-xs text-slate-500" x-show="tipo === 'alumnos'">
                        En tipo_identificacion usa C (cédula), P (pasaporte) o R (RUC). identificacion_representante debe coincidir con un padre ya registrado en este instituto. La contraseña inicial de cada cuenta será la identificación del alumno.
                    </p>

                    <div>
                        <x-input-label for="archivo" value="Archivo" />
                        <input
                            id="archivo"
                            name="archivo"
                            type="file"
                            accept=".xlsx,.csv,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,text/csv"
                            class="mt-1 block w-full text-sm text-slate-700 file:mr-4 file:rounded-md file:border-0 file:bg-slate-900 file:px-3 file:py-2 file:text-sm file:font-medium file:text-white hover:file:bg-slate-800"
                            required
                        >
                        <x-input-error class="mt-2" :messages="$errors->get('archivo')" />
                    </div>
                </div>

                <div class="mt-6 flex items-center justify-end gap-4">
                    <a
                        :href="tipo === 'alumnos' ? @js(route('Admin.alumnos')) : @js(route('Admin.padres'))"
                        href="{{ $tipo === 'alumnos' ? route('Admin.alumnos') : route('Admin.padres') }}"
                        class="text-sm text-slate-600 hover:text-slate-900"
                    >Cancelar</a>
                    <x-primary-button>Importar</x-primary-button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
