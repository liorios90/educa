<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="text-xl font-semibold leading-tight text-slate-800">
                Estructura educativa
            </h2>
            <p class="mt-1 text-sm text-slate-500">
                {{ $establecimiento->nombre }}
            </p>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">
            <p class="rounded-2xl border border-slate-200 bg-white px-6 py-8 text-center text-slate-500">
                Sistemas aún no ha asignado modalidades y jornadas a este establecimiento.
                Cuando las asigne, podrás definir la estructura de grados de la oferta de esta sesión.
            </p>
        </div>
    </div>
</x-app-layout>
