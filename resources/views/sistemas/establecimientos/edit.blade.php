<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-slate-800">
            Editar establecimiento
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
            <form method="POST" action="{{ route('sistemas.establecimientos.update', $establecimiento) }}" enctype="multipart/form-data" class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                @csrf
                @method('patch')

                @include('sistemas.establecimientos._form', ['establecimiento' => $establecimiento, 'administrador' => $administrador])

                <div class="mt-6 flex items-center justify-end gap-4">
                    <a href="{{ route('sistemas.establecimientos') }}" class="text-sm text-slate-600 hover:text-slate-900">Cancelar</a>
                    <x-primary-button>Guardar cambios</x-primary-button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
