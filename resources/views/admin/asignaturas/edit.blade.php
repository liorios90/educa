@php
    use App\Enums\ModoLibretaArea;
    use App\Enums\TipoCalificacion;

    $activeSubnivelId = $subniveles->first()?->id;
    $tiposCalificacion = TipoCalificacion::cases();
    $modosLibretaArea = ModoLibretaArea::cases();
@endphp

<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="text-xl font-semibold leading-tight text-slate-800">
                Asignaturas
            </h2>
            <p class="mt-1 text-sm text-slate-500">
                {{ $establecimiento->nombre }} · {{ $oferta->etiqueta() }}
            </p>
        </div>
    </x-slot>

    <div
        class="py-8"
        x-data="{
            activeSubnivelId: @js($activeSubnivelId),
            asignaturas: @js($selectedGradosPorAsignatura),
            toggleAsignatura(id, gradoIds) {
                if ((this.asignaturas[id] || []).length > 0) {
                    this.asignaturas[id] = []
                    return
                }

                this.asignaturas[id] = gradoIds.map(String)
            },
            hasAsignatura(id) {
                return (this.asignaturas[id] || []).length > 0
            },
            countSelectedInArea(asignaturaIds, gradoId) {
                const grado = String(gradoId)

                return asignaturaIds.filter((id) => (this.asignaturas[id] || []).map(String).includes(grado)).length
            },
        }"
    >
        <div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">
            @if (session('status') === 'asignaturas-updated')
                <p class="mb-4 text-sm font-medium text-green-700">Malla de la jornada guardada correctamente.</p>
            @endif

            <p class="mb-4">
                <a href="{{ route('Admin.asignaturas') }}" class="text-sm font-medium text-indigo-600 hover:text-indigo-500">
                    Volver a modalidades y jornadas
                </a>
            </p>

            <section class="mb-6 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <h3 class="text-base font-semibold text-slate-800">Catálogo nacional</h3>
                <p class="mt-2 text-sm leading-6 text-slate-600">
                    Marca la asignatura del subnivel. Por defecto se aplica a todos los grados que esta jornada ya ofertó.
                    Luego puedes quitar un grado o cambiar horas, libreta y forma de calificar.
                    Si un área tiene dos o más asignaturas en el mismo grado, también eliges si la libreta muestra cada
                    asignatura, solo el promedio del área, o ambos.
                </p>
            </section>

            <x-input-error class="mb-4" :messages="$errors->get('asignaturas')" />
            <x-input-error class="mb-4" :messages="$errors->get('areas')" />

            <form method="POST" action="{{ route('Admin.asignaturas.update', $oferta) }}">
                @csrf
                @method('PUT')

                @if ($subniveles->isEmpty())
                    <p class="rounded-2xl border border-slate-200 bg-white px-6 py-8 text-center text-slate-500">
                        Esta jornada aún no tiene grados en la estructura.
                        <a href="{{ route('Admin.estructura.edit', $oferta) }}" class="font-medium text-indigo-600 hover:text-indigo-500">
                            Definir estructura
                        </a>
                    </p>
                @else
                    <div class="mb-4 flex flex-wrap gap-2">
                        @foreach ($subniveles as $subnivel)
                            <button
                                type="button"
                                class="rounded-lg px-3 py-2 text-sm font-medium"
                                :class="activeSubnivelId === {{ $subnivel->id }} ? 'bg-slate-900 text-white' : 'bg-white text-slate-700 border border-slate-200'"
                                @click="activeSubnivelId = {{ $subnivel->id }}"
                            >
                                {{ $subnivel->nombre }}
                            </button>
                        @endforeach
                    </div>

                    @foreach ($subniveles as $subnivel)
                        @php
                            $gradosDelSubnivel = $gradosPorSubnivel->get($subnivel->id, collect());
                            $gradoIds = $gradosDelSubnivel->pluck('id')->map(fn ($id) => (string) $id)->values()->all();
                            $esInicial = $subnivel->nivel?->nombre === 'Educación Inicial';
                            $etiquetaAsignatura = $esInicial ? 'Ámbito' : 'Asignatura';
                        @endphp

                        <div x-show="activeSubnivelId === {{ $subnivel->id }}" class="space-y-4">
                            <h3 class="text-center text-lg font-semibold" style="color: #0f172a">
                                Subnivel: {{ $subnivel->nombre }}
                            </h3>

                            @forelse ($subnivel->areas as $area)
                                @php
                                    $asignaturaIdsDelArea = $area->asignaturas->pluck('id')->map(fn ($id) => (string) $id)->values()->all();
                                @endphp
                                <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                                    <h4 class="border-b pb-2 text-sm font-bold" style="border-color: #0f172a; color: #0f172a">
                                        {{ $esInicial ? 'Eje' : 'Área' }}: {{ $area->nombre }}
                                    </h4>

                                    @if ($area->asignaturas->count() >= 2)
                                        <div class="mt-4 space-y-3">
                                            @foreach ($gradosDelSubnivel as $grado)
                                                @php
                                                    $modoArea = $modosPorArea[(string) $area->id][(string) $grado->id]
                                                        ?? ModoLibretaArea::Asignaturas->value;
                                                @endphp
                                                <div
                                                    class="rounded-lg border border-slate-100 bg-slate-50 p-3"
                                                    x-show="countSelectedInArea(@js($asignaturaIdsDelArea), '{{ $grado->id }}') >= 2"
                                                >
                                                    <label
                                                        class="block text-xs font-medium text-slate-600"
                                                        for="modo-area-{{ $area->id }}-{{ $grado->id }}"
                                                    >
                                                        En la libreta ({{ $grado->pivot->nombre ?? $grado->nombre }})
                                                    </label>
                                                    <select
                                                        id="modo-area-{{ $area->id }}-{{ $grado->id }}"
                                                        name="areas[{{ $area->id }}][grados][{{ $grado->id }}][modo_libreta]"
                                                        class="mt-1 block w-full rounded-md border-slate-300 text-sm sm:max-w-sm"
                                                    >
                                                        @foreach ($modosLibretaArea as $modoLibreta)
                                                            <option
                                                                value="{{ $modoLibreta->value }}"
                                                                @selected($modoArea === $modoLibreta->value)
                                                            >
                                                                {{ $modoLibreta->label() }}
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                    <x-input-error class="mt-1" :messages="$errors->get('areas.'.$area->id.'.grados.'.$grado->id.'.modo_libreta')" />
                                                </div>
                                            @endforeach
                                        </div>
                                    @endif

                                    <div class="mt-4 space-y-4">
                                        @forelse ($area->asignaturas as $asignatura)
                                            @php
                                                $asignaturaKey = (string) $asignatura->id;
                                            @endphp
                                            <div class="rounded-xl border border-slate-100 p-4">
                                                <label class="flex items-center gap-3 text-sm font-medium text-slate-800">
                                                    <input
                                                        type="checkbox"
                                                        class="rounded border-slate-300"
                                                        :checked="hasAsignatura('{{ $asignaturaKey }}')"
                                                        @change="toggleAsignatura('{{ $asignaturaKey }}', @js($gradoIds))"
                                                    >
                                                    <span>{{ $etiquetaAsignatura }}: {{ $asignatura->nombre }}</span>
                                                    @if ($asignatura->horas_semanales)
                                                        <span class="text-xs font-normal text-slate-500">
                                                            {{ $asignatura->horas_semanales }} h/sem (catálogo)
                                                        </span>
                                                    @endif
                                                </label>

                                                <div class="mt-3 space-y-3" x-show="hasAsignatura('{{ $asignaturaKey }}')">
                                                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Grados</p>

                                                    @foreach ($gradosDelSubnivel as $grado)
                                                        @php
                                                            $gradoKey = (string) $grado->id;
                                                            $config = $configPorAsignatura[$asignaturaKey][$gradoKey] ?? [];
                                                            $horas = $config['horas'] ?? (string) ($asignatura->horas_semanales ?? '');
                                                            $libreta = array_key_exists('libreta', $config)
                                                                ? (bool) $config['libreta']
                                                                : (bool) $asignatura->aparece_en_libreta;
                                                            $tipo = ($config['tipo'] ?? '') !== ''
                                                                ? $config['tipo']
                                                                : $subnivel->tipo_calificacion?->value;
                                                        @endphp
                                                        <div class="rounded-lg bg-slate-50 p-3">
                                                            <label class="flex items-center gap-2 text-sm text-slate-800">
                                                                <input
                                                                    type="checkbox"
                                                                    class="rounded border-slate-300"
                                                                    name="asignaturas[{{ $asignatura->id }}][grados][]"
                                                                    value="{{ $grado->id }}"
                                                                    x-model="asignaturas['{{ $asignaturaKey }}']"
                                                                >
                                                                {{ $grado->pivot->nombre ?? $grado->nombre }}
                                                            </label>

                                                            <div
                                                                class="mt-3 grid gap-3 sm:grid-cols-3"
                                                                x-show="(asignaturas['{{ $asignaturaKey }}'] || []).map(String).includes('{{ $gradoKey }}')"
                                                            >
                                                                <div>
                                                                    <label class="block text-xs text-slate-500" for="horas-{{ $asignatura->id }}-{{ $grado->id }}">
                                                                        Horas semanales
                                                                    </label>
                                                                    <input
                                                                        id="horas-{{ $asignatura->id }}-{{ $grado->id }}"
                                                                        type="number"
                                                                        min="0"
                                                                        max="40"
                                                                        name="asignaturas[{{ $asignatura->id }}][horas][{{ $grado->id }}]"
                                                                        value="{{ $horas }}"
                                                                        class="mt-1 block w-full rounded-md border-slate-300 text-sm"
                                                                    >
                                                                    <x-input-error class="mt-1" :messages="$errors->get('asignaturas.'.$asignatura->id.'.horas.'.$grado->id)" />
                                                                </div>
                                                                <div>
                                                                    <label class="block text-xs text-slate-500" for="tipo-{{ $asignatura->id }}-{{ $grado->id }}">
                                                                        Forma de calificar
                                                                    </label>
                                                                    <select
                                                                        id="tipo-{{ $asignatura->id }}-{{ $grado->id }}"
                                                                        name="asignaturas[{{ $asignatura->id }}][tipo_calificacion][{{ $grado->id }}]"
                                                                        class="mt-1 block w-full rounded-md border-slate-300 text-sm"
                                                                    >
                                                                        @foreach ($tiposCalificacion as $tipoCalificacion)
                                                                            <option
                                                                                value="{{ $tipoCalificacion->value }}"
                                                                                @selected($tipo === $tipoCalificacion->value)
                                                                            >
                                                                                {{ $tipoCalificacion->label() }}
                                                                            </option>
                                                                        @endforeach
                                                                    </select>
                                                                </div>
                                                                <div class="flex items-end">
                                                                    <input type="hidden" name="asignaturas[{{ $asignatura->id }}][libreta][{{ $grado->id }}]" value="0">
                                                                    <label class="flex items-center gap-2 text-sm text-slate-700">
                                                                        <input
                                                                            type="checkbox"
                                                                            class="rounded border-slate-300"
                                                                            name="asignaturas[{{ $asignatura->id }}][libreta][{{ $grado->id }}]"
                                                                            value="1"
                                                                            @checked($libreta)
                                                                        >
                                                                        Aparece en libreta
                                                                    </label>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            </div>
                                        @empty
                                            <p class="text-sm text-slate-500">
                                                Sistemas aún no ha definido {{ $esInicial ? 'ámbitos' : 'asignaturas' }} en esta área.
                                            </p>
                                        @endforelse
                                    </div>
                                </section>
                            @empty
                                <p class="rounded-2xl border border-slate-200 bg-white px-6 py-8 text-center text-slate-500">
                                    Sistemas aún no ha definido áreas para este subnivel.
                                </p>
                            @endforelse
                        </div>
                    @endforeach

                    <div class="mt-6 flex justify-end">
                        <x-primary-button>Guardar malla</x-primary-button>
                    </div>
                @endif
            </form>
        </div>
    </div>
</x-app-layout>
