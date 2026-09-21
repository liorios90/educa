<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-slate-800">
            Importar representantes
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
            <form method="POST" action="{{ route('Admin.padres.import.store') }}" enctype="multipart/form-data" class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                @csrf

                <div class="space-y-4">
                    <p class="text-sm text-slate-600">
                        Sube un Excel (.xlsx) o CSV con los representantes del instituto. Las columnas deben ser:
                        <span class="font-medium text-slate-800">tipo_identificacion</span>,
                        <span class="font-medium text-slate-800">identificacion</span>,
                        <span class="font-medium text-slate-800">email</span>,
                        <span class="font-medium text-slate-800">nombres</span> y
                        <span class="font-medium text-slate-800">apellidos</span>.
                    </p>
                    <p class="text-xs text-slate-500">
                        En tipo_identificacion usa C (cédula), P (pasaporte) o R (RUC). La contraseña inicial de cada cuenta será la identificación. Los campos que no vienen en el archivo se guardan como pendientes para completarlos después.
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
                    <a href="{{ route('Admin.padres') }}" class="text-sm text-slate-600 hover:text-slate-900">Cancelar</a>
                    <x-primary-button>Importar</x-primary-button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
