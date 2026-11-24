<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="text-xl font-semibold leading-tight text-slate-800">Habilidades de Cívica</h2>
            <p class="mt-1 text-sm text-slate-500">{{ $establecimiento->nombre }}</p>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
            @if (session('status') === 'habilidades-civica-updated')
                <p class="mb-4 text-sm font-medium text-green-700">Las nueve habilidades de Cívica quedaron definidas.</p>
            @endif

            <form method="POST" action="{{ route('Admin.habilidades-civica.update') }}" class="space-y-6 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                @csrf
                @method('PUT')

                <p class="text-sm text-slate-600">
                    El Ministerio pide evaluar nueve habilidades socioemocionales. Cuatro son obligatorias. La institución elige una cognitiva, tres sociales y una emocional. Cada habilidad se registra con A, EP, I o NE. Esa letra no entra al promedio ni se reemplaza por la más baja.
                </p>

                <div>
                    <h3 class="text-sm font-semibold text-slate-800">Obligatorias</h3>
                    <ul class="mt-2 space-y-1 text-sm text-slate-600">
                        @foreach ($obligatorias as $habilidad)
                            <li>{{ $habilidad['codigo'] }} · {{ $habilidad['nombre'] }}</li>
                        @endforeach
                    </ul>
                </div>

                @foreach ([
                    'cognitivas' => 'Elige una habilidad cognitiva',
                    'sociales' => 'Elige tres habilidades sociales',
                    'emocionales' => 'Elige una habilidad emocional',
                ] as $grupo => $titulo)
                    <fieldset>
                        <legend class="text-sm font-semibold text-slate-800">{{ $titulo }}</legend>
                        <div class="mt-2 space-y-2">
                            @foreach ($opciones[$grupo] as $habilidad)
                                <label class="flex items-center gap-2 text-sm text-slate-700">
                                    <input
                                        type="checkbox"
                                        name="habilidades[]"
                                        value="{{ $habilidad['codigo'] }}"
                                        class="rounded border-slate-300 text-indigo-600 shadow-sm focus:ring-indigo-500"
                                        @checked(in_array($habilidad['codigo'], old('habilidades', $elegidas), true))
                                    >
                                    {{ $habilidad['codigo'] }} · {{ $habilidad['nombre'] }}
                                </label>
                            @endforeach
                        </div>
                    </fieldset>
                @endforeach

                <x-input-error :messages="$errors->get('habilidades')" />
                <x-input-error :messages="$errors->get('habilidades.*')" />

                <x-primary-button>Guardar habilidades</x-primary-button>
            </form>
        </div>
    </div>
</x-app-layout>
