<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-slate-800">
            Nuevo submenú de {{ $parent->label }}
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-xl px-4 sm:px-6 lg:px-8">
            <form method="POST" action="{{ route('sistemas.navigation-items.submenus.store', $parent) }}" class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                @csrf

                @include('sistemas.navigation-items._form', ['asSubmenu' => true])

                <div class="mt-6 flex items-center justify-end gap-4">
                    <a href="{{ route('sistemas.navigation-items.submenus.index', $parent) }}" class="text-sm text-slate-600 hover:text-slate-900">Cancelar</a>
                    <x-primary-button>Crear submenú</x-primary-button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
