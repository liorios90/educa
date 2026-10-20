<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-slate-800">
            Asistente
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-xl px-4 sm:px-6 lg:px-8">
            @unless ($asistenteListo)
                <p class="mb-4 text-sm font-medium text-amber-800">Este instituto aún no tiene una clave de Gemini. El administrador debe configurarla en Configuración → Asistente.</p>
            @endunless

            <div class="mb-4 grid grid-cols-1 gap-3 sm:grid-cols-2">
                <div class="rounded-2xl border border-slate-200 bg-white p-4">
                    <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Preguntas · {{ $uso->mesEtiqueta }}</p>
                    <p class="mt-2 text-2xl font-semibold text-slate-800">{{ $uso->preguntasUsadas }}</p>
                    <p class="mt-1 text-sm text-slate-600">Quedan {{ $uso->preguntasRestantes }} de {{ $uso->preguntasLimite }}</p>
                </div>
                <div class="rounded-2xl border border-slate-200 bg-white p-4">
                    <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Tokens · {{ $uso->mesEtiqueta }}</p>
                    <p class="mt-2 text-2xl font-semibold text-slate-800">{{ number_format($uso->tokensUsados, 0, ',', '.') }}</p>
                    <p class="mt-1 text-sm text-slate-600">Quedan {{ number_format($uso->tokensRestantes, 0, ',', '.') }} de {{ number_format($uso->tokensLimite, 0, ',', '.') }}</p>
                </div>
            </div>

            @if ($uso->agotado())
                <p class="mb-4 text-sm font-medium text-red-700">Este instituto ya usó el cupo mensual del asistente.</p>
            @endif

            <form method="POST" action="{{ $storeRoute }}" class="space-y-6 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                @csrf

                <div>
                    <x-input-label for="prompt" value="Pregunta" />
                    <textarea
                        id="prompt"
                        name="prompt"
                        rows="5"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        required
                        @disabled($uso->agotado() || ! $asistenteListo)
                    >{{ old('prompt') }}</textarea>
                    <x-input-error class="mt-2" :messages="$errors->get('prompt')" />
                </div>

                <x-primary-button :disabled="$uso->agotado() || ! $asistenteListo">Enviar</x-primary-button>
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
