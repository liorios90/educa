<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="text-xl font-semibold leading-tight text-slate-800">Criterios de comportamiento</h2>
            <p class="mt-1 text-sm text-slate-500">{{ $periodo?->nombre ?? 'Sin periodo activo' }}</p>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-3xl space-y-4 px-4 sm:px-6 lg:px-8">
            @if (session('status') === 'criterios-comportamiento-updated')
                <p class="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm font-medium text-green-800">Los criterios de comportamiento del periodo se guardaron correctamente.</p>
            @endif

            @if ($errors->any())
                <p class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">{{ $errors->first() }}</p>
            @endif

            @if ($periodo === null)
                <p class="rounded-2xl border border-slate-200 bg-white px-6 py-8 text-center text-slate-500">Selecciona una oferta con un periodo activo para elegir los criterios de comportamiento.</p>
            @elseif ($grupos->flatten()->isEmpty())
                <p class="rounded-2xl border border-slate-200 bg-white px-6 py-8 text-center text-slate-500">No hay criterios activos en el catálogo. Pide a Sistemas que active las habilidades del Acuerdo 082-A.</p>
            @else
                <form method="POST" action="{{ route('Admin.criterios-comportamiento.update') }}" class="space-y-6">
                    @csrf
                    @method('PUT')

                    <p class="text-sm text-slate-600">
                        El Acuerdo Ministerial MINEDEC-MINEDEC-2026-00082-A describe el comportamiento a partir de las habilidades priorizadas para el acompañamiento socioemocional. Este periodo incluye siempre los criterios obligatorios del catálogo y, además, 1 habilidad cognitiva, 3 sociales y 1 emocional.
                    </p>

                    @foreach (\App\Enums\TipoCriterioComportamiento::cases() as $tipo)
                        @php
                            $grupo = $grupos->get($tipo->value, collect());
                        @endphp
                        @if ($grupo->isNotEmpty())
                            <section class="space-y-3 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                                <div>
                                    <h3 class="text-base font-semibold text-slate-800">{{ $tipo->grupo() }}</h3>
                                    <p class="mt-1 text-sm text-slate-500">Elige {{ $cupos[$tipo->value] }} además de las obligatorias.</p>
                                </div>

                                <div class="space-y-3">
                                    @foreach ($grupo as $criterio)
                                        <label class="flex items-start gap-3 rounded-xl border px-4 py-3 {{ $criterio->obligatorio ? 'border-indigo-200 bg-indigo-50' : 'border-slate-200' }}">
                                            <input
                                                type="checkbox"
                                                name="criterios[]"
                                                value="{{ $criterio->id }}"
                                                class="mt-1 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500 disabled:opacity-70"
                                                @checked($criterio->obligatorio || in_array($criterio->id, $marcados, true))
                                                @disabled($criterio->obligatorio)
                                            >
                                            <span>
                                                <span class="font-medium text-slate-800">{{ $criterio->codigo }}</span>
                                                <span class="mt-1 block text-sm text-slate-600">{{ $criterio->descripcion }}</span>
                                                @if ($criterio->obligatorio)
                                                    <span class="mt-2 inline-flex rounded-full bg-indigo-100 px-2 py-0.5 text-xs font-semibold text-indigo-700">Obligatorio</span>
                                                @endif
                                            </span>
                                        </label>
                                    @endforeach
                                </div>
                            </section>
                        @endif
                    @endforeach

                    <x-primary-button>Guardar criterios</x-primary-button>
                </form>
            @endif
        </div>
    </div>
</x-app-layout>
