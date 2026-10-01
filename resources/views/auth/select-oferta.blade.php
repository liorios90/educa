<x-guest-layout>
    <div
        class="space-y-6"
        x-data="{
            modalidadId: '{{ old('establecimiento_modalidad_id', $activeOferta?->establecimiento_modalidad_id) }}',
            ofertaId: '{{ old('oferta', $activeOferta?->id) }}',
        }"
    >
        <div class="text-center">
            <h1 class="text-lg font-semibold text-slate-900">¿En qué modalidad y jornada quieres entrar?</h1>
            <p class="mt-2 text-sm text-slate-500">
                @if ($establecimiento)
                    Elige la oferta configurada para {{ $establecimiento->nombre }}.
                @else
                    Elige la oferta configurada en tu establecimiento.
                @endif
            </p>
        </div>

        @if ($sinPeriodoActivo || $errors->has('periodo'))
            <div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-center text-sm text-amber-950" role="alert">
                {{ $errors->first('periodo') ?: 'No existe ningún periodo activo.' }}
            </div>
        @endif

        <form method="POST" action="{{ route('oferta.store') }}" class="flex flex-col gap-4">
            @csrf

            <div>
                <x-input-label for="establecimiento_modalidad_id" value="Modalidad" />
                <select
                    id="establecimiento_modalidad_id"
                    name="establecimiento_modalidad_id"
                    x-model="modalidadId"
                    @change="ofertaId = ''"
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                    required
                >
                    <option value="">Selecciona una modalidad</option>
                    @foreach ($modalidades as $establecimientoModalidad)
                        <option value="{{ $establecimientoModalidad->id }}">
                            {{ $establecimientoModalidad->modalidad?->nombre }}
                        </option>
                    @endforeach
                </select>
                <x-input-error class="mt-2" :messages="$errors->get('establecimiento_modalidad_id')" />
            </div>

            <div>
                <x-input-label for="oferta" value="Jornada" />
                <select
                    id="oferta"
                    name="oferta"
                    x-model="ofertaId"
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                    required
                >
                    <option value="">Selecciona una jornada</option>
                    @foreach ($modalidades as $establecimientoModalidad)
                        <optgroup label="{{ $establecimientoModalidad->modalidad?->nombre }}">
                            @foreach ($establecimientoModalidad->establecimientoJornadas as $oferta)
                                <option
                                    value="{{ $oferta->id }}"
                                    x-show="modalidadId === '' || String(modalidadId) === '{{ $establecimientoModalidad->id }}'"
                                >
                                    {{ $oferta->jornada?->nombre }}
                                </option>
                            @endforeach
                        </optgroup>
                    @endforeach
                </select>
                <x-input-error class="mt-2" :messages="$errors->get('oferta')" />
            </div>

            <x-primary-button class="justify-center" :disabled="$sinPeriodoActivo">
                Entrar
            </x-primary-button>
        </form>

        <form method="POST" action="{{ route('logout') }}" class="text-center">
            @csrf
            <button type="submit" class="text-sm text-slate-500 underline hover:text-slate-800">
                Cerrar sesión
            </button>
        </form>
    </div>
</x-guest-layout>
