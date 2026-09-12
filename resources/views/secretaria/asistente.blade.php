<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-slate-800">
            Asistente
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-xl px-4 sm:px-6 lg:px-8">
            <form method="POST" action="{{ route('secretaria.asistente.store') }}" class="space-y-6 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                @csrf

                <div>
                    <x-input-label for="prompt" value="Pregunta" />
                    <textarea
                        id="prompt"
                        name="prompt"
                        rows="5"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        required
                    >{{ old('prompt') }}</textarea>
                    <x-input-error class="mt-2" :messages="$errors->get('prompt')" />
                </div>

                <x-primary-button>Enviar</x-primary-button>
            </form>

            @if (session('error'))
                <p class="mt-4 text-sm font-medium text-red-700">{{ session('error') }}</p>
            @endif

            @if (session('respuesta'))
                <div class="mt-6 rounded-2xl border border-slate-200 bg-white p-6 text-slate-700">
                    {{ session('respuesta') }}
                </div>
            @endif
        </div>
    </div>
</x-app-layout>