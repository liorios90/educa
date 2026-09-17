<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-slate-800">
            Nuevo padre de familia
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
            <form method="POST" action="{{ route('Admin.padres.store') }}" class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                @csrf

                @include('admin.padres._form')

                <div class="mt-6 flex items-center justify-end gap-4">
                    <a href="{{ route('Admin.padres') }}" class="text-sm text-slate-600 hover:text-slate-900">Cancelar</a>
                    <x-primary-button>Crear padre</x-primary-button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
