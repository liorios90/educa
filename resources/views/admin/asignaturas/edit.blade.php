@php
    use App\Enums\ModoLibretaArea;
    use App\Enums\TipoAsignatura;
    use App\Enums\TipoCalificacion;

    $activeSubnivelId = $subniveles->first()?->id;
    $tiposCalificacion = TipoCalificacion::cases();
    $tiposAsignatura = TipoAsignatura::cases();
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
            pantallaAsignaturasArea: null,
            modalAsignatura: null,
            configuracionArea: null,
            asignaturas: @js($selectedGradosPorAsignatura),
            nuevas: {},
            modosArea: @js($modosPorArea),
            promedioPorcentual: @js($promedioPorcentualPorArea),
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
            idsArea(asignaturaIds, areaId) {
                const agregadas = (this.nuevas[areaId] || []).map((item) => item.clave)

                return [...asignaturaIds, ...agregadas]
            },
            countSelectedInArea(asignaturaIds, gradoId) {
                const grado = String(gradoId)

                return asignaturaIds.filter((id) => (this.asignaturas[id] || []).map(String).includes(grado)).length
            },
            agregarAsignatura(areaId, gradoIds) {
                this.nuevas[areaId] = this.nuevas[areaId] || []
                const clave = 'new_' + areaId + '_' + Date.now()
                this.nuevas[areaId].push({ clave, nombre: '', tipo: 'optativa' })
                this.asignaturas[clave] = gradoIds.map(String)
            },
            quitarNueva(areaId, clave) {
                this.nuevas[areaId] = (this.nuevas[areaId] || []).filter((item) => item.clave !== clave)
                delete this.asignaturas[clave]
            },
            promediaElArea(areaId, gradoId) {
                const modo = (this.modosArea[areaId] || {})[gradoId]

                return modo === 'area_promedio' || modo === 'area_y_asignaturas'
            },
            usaPorcentaje(areaId, gradoId, asignaturaIds) {
                if (this.countSelectedInArea(this.idsArea(asignaturaIds, areaId), gradoId) < 2) {
                    return false
                }

                return true
            },
            tieneMultiplesPorGrado(asignaturaIds, areaId, gradoIds) {
                return gradoIds.some((gradoId) => this.countSelectedInArea(this.idsArea(asignaturaIds, areaId), gradoId) > 1)
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
                <h3 class="text-base font-semibold text-slate-800">Áreas por subnivel</h3>
                <p class="mt-2 text-sm leading-6 text-slate-600">
                    Selecciona un subnivel para administrar sus áreas, asignaturas y presentación en la libreta.
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
                    <div x-show="pantallaAsignaturasArea === null" class="mb-4 flex flex-wrap gap-2">
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
                            <h3 x-show="pantallaAsignaturasArea === null" class="text-center text-lg font-semibold" style="color: #0f172a">
                                Subnivel: {{ $subnivel->nombre }}
                            </h3>

                            @forelse ($subnivel->areas as $area)
                                @php
                                    $propiasDelArea = $propiasPorArea[(string) $area->id] ?? [];
                                    $asignaturaIdsDelArea = array_merge(
                                        $area->asignaturas->pluck('id')->map(fn ($id) => (string) $id)->all(),
                                        array_map(fn (array $propia): string => $propia['alpine'], $propiasDelArea),
                                    );
                                @endphp
                                <section x-show="pantallaAsignaturasArea === null || pantallaAsignaturasArea === '{{ $area->id }}'" class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                                    <div x-show="pantallaAsignaturasArea === null" class="flex flex-wrap items-center justify-between gap-3 pb-3">
                                        <h4 class="text-sm font-bold text-slate-900">{{ $esInicial ? 'Eje' : 'Área' }}: {{ $area->nombre }}</h4>
                                        <div class="flex gap-2">
                                            <button type="button" class="rounded-lg bg-slate-900 px-3 py-2 text-sm font-medium text-white hover:bg-slate-800" @click="pantallaAsignaturasArea = '{{ $area->id }}'">Asignaturas</button>
                                            <button type="button" class="rounded-lg border border-slate-300 px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50" x-show="tieneMultiplesPorGrado(@js($asignaturaIdsDelArea), '{{ $area->id }}', @js($gradoIds))" x-cloak @click="configuracionArea = '{{ $area->id }}'">Configuración</button>
                                        </div>
                                    </div>

                                    <div x-show="configuracionArea === '{{ $area->id }}'" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/50 p-4" @keydown.escape.window="configuracionArea = null">
                                      <div class="max-h-[90vh] w-full max-w-2xl overflow-y-auto rounded-2xl bg-white p-5 shadow-xl sm:p-6" @click.outside="configuracionArea = null">
                                        <div class="mb-4 flex items-center justify-between gap-4">
                                            <div><h5 class="text-lg font-semibold text-slate-900">Configuración · {{ $area->nombre }}</h5><p class="mt-1 text-sm text-slate-500">Presentación del área en las libretas.</p></div>
                                            <button type="button" class="rounded-lg px-3 py-2 text-sm text-slate-600 hover:bg-slate-100" @click="configuracionArea = null">Cerrar</button>
                                        </div>
                                        <div class="space-y-3">
                                            @foreach ($gradosDelSubnivel as $grado)
                                                @php
                                                    $modoArea = $modosPorArea[(string) $area->id][(string) $grado->id]
                                                        ?? ModoLibretaArea::Asignaturas->value;
                                                    $porcentual = (bool) ($promedioPorcentualPorArea[(string) $area->id][(string) $grado->id] ?? false);
                                                @endphp
                                                <div
                                                    class="rounded-lg border border-slate-100 bg-slate-50 p-3"
                                                    x-show="countSelectedInArea(idsArea(@js($asignaturaIdsDelArea), '{{ $area->id }}'), '{{ $grado->id }}') >= 2"
                                                    x-init="
                                                        modosArea['{{ $area->id }}'] = modosArea['{{ $area->id }}'] || {}
                                                        if (modosArea['{{ $area->id }}']['{{ $grado->id }}'] === undefined) {
                                                            modosArea['{{ $area->id }}']['{{ $grado->id }}'] = @js($modoArea)
                                                        }
                                                        promedioPorcentual['{{ $area->id }}'] = promedioPorcentual['{{ $area->id }}'] || {}
                                                        if (promedioPorcentual['{{ $area->id }}']['{{ $grado->id }}'] === undefined) {
                                                            promedioPorcentual['{{ $area->id }}']['{{ $grado->id }}'] = @js($porcentual)
                                                        }
                                                    "
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
                                                        x-model="modosArea['{{ $area->id }}']['{{ $grado->id }}']"
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
                                                    <input type="hidden" name="areas[{{ $area->id }}][grados][{{ $grado->id }}][promedio_porcentual]" value="1" :disabled="!promediaElArea('{{ $area->id }}', '{{ $grado->id }}')">
                                                    <x-input-error class="mt-1" :messages="$errors->get('areas.'.$area->id.'.grados.'.$grado->id.'.promedio_porcentual')" />
                                                </div>
                                            @endforeach
                                        </div>
                                      </div>
                                    </div>

                                    <div x-show="pantallaAsignaturasArea === '{{ $area->id }}'" x-cloak class="mt-2 rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                                        <div class="mb-4 flex items-center justify-between gap-4">
                                            <div><h5 class="text-lg font-semibold text-slate-900">Asignaturas · {{ $area->nombre }}</h5><p class="mt-1 text-sm text-slate-500">Selecciona las asignaturas y define en qué grados se imparten.</p></div>
                                            <button type="button" class="rounded-lg px-3 py-2 text-sm font-medium text-indigo-600 hover:bg-slate-100" @click="pantallaAsignaturasArea = null">Volver a áreas</button>
                                        </div>
                                        <div class="space-y-4">
                                        @forelse ($area->asignaturas as $asignatura)
                                            @php
                                                $asignaturaKey = (string) $asignatura->id;
                                                $gradosObligatoriosIds = $asignatura->gradosCurriculares
                                                    ->filter(fn ($gradoCurricular): bool => (bool) $gradoCurricular->pivot->obligatoria)
                                                    ->pluck('id')
                                                    ->map(strval(...))
                                                    ->all();
                                            @endphp
                                            <div class="rounded-xl border border-slate-100 p-4">
                                                <label class="flex items-center gap-3 text-sm font-medium text-slate-800">
                                                    <input
                                                        type="checkbox"
                                                        class="rounded border-slate-300"
                                                        :checked="hasAsignatura('{{ $asignaturaKey }}')"
                                                        @disabled($gradosObligatoriosIds !== [])
                                                        @change="toggleAsignatura('{{ $asignaturaKey }}', @js($gradoIds))"
                                                    >
                                                    <span>{{ $etiquetaAsignatura }}: {{ $asignatura->nombre }}</span>
                                                    <span class="rounded-full bg-slate-100 px-2 py-0.5 text-xs font-normal text-slate-600">
                                                        Catálogo nacional
                                                    </span>
                                                </label>

                                                <button type="button" class="mt-3 rounded-lg border border-slate-300 px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50" @click="modalAsignatura = '{{ $asignaturaKey }}'">Grados y opciones</button>

                                                <div x-show="modalAsignatura === '{{ $asignaturaKey }}'" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/50 p-4" @keydown.escape.window="modalAsignatura = null">
                                                  <div class="max-h-[90vh] w-full max-w-3xl overflow-y-auto rounded-2xl bg-white p-5 shadow-xl sm:p-6" @click.outside="modalAsignatura = null">
                                                <div class="mb-4 flex items-center justify-between gap-4"><div><h5 class="text-lg font-semibold text-slate-900">Grados · {{ $asignatura->nombre }}</h5><p class="mt-1 text-sm text-slate-500">Marca los grados donde se imparte esta asignatura.</p></div><button type="button" class="rounded-lg px-3 py-2 text-sm text-slate-600 hover:bg-slate-100" @click="modalAsignatura = null">Cerrar</button></div>
                                                <div class="space-y-3" x-show="hasAsignatura('{{ $asignaturaKey }}')">
                                                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Grados</p>

                                                    @foreach ($gradosDelSubnivel as $grado)
                                                        @php
                                                            $gradoKey = (string) $grado->id;
                                                            $esObligatoria = in_array($gradoKey, $gradosObligatoriosIds, true);
                                                            $config = $configPorAsignatura[$asignaturaKey][$gradoKey] ?? [];
                                                            $libreta = array_key_exists('libreta', $config)
                                                                ? (bool) $config['libreta']
                                                                : (bool) $asignatura->aparece_en_libreta;
                                                            $tipo = ($config['tipo'] ?? '') !== ''
                                                                ? $config['tipo']
                                                                : ($subnivel->tipo_calificacion?->value ?? TipoCalificacion::Cuantitativa->value);
                                                            $destrezas = array_key_exists('destrezas', $config)
                                                                ? (bool) $config['destrezas']
                                                                : ($tipo === TipoCalificacion::Cualitativa->value && (bool) $subnivel->califica_por_destrezas);
                                                            $equivalencias = (bool) ($config['equivalencias'] ?? false);
                                                            $porcentaje = $config['porcentaje'] ?? '';
                                                        @endphp
                                                        <div class="rounded-lg bg-slate-50 p-3">
                                                            <label class="flex items-center gap-2 text-sm text-slate-800">
                                                                <input
                                                                    type="checkbox"
                                                                    class="rounded border-slate-300"
                                                                    name="asignaturas[{{ $asignatura->id }}][grados][]"
                                                                    value="{{ $grado->id }}"
                                                                    @checked($esObligatoria)
                                                                    @disabled($esObligatoria)
                                                                    x-model="asignaturas['{{ $asignaturaKey }}']"
                                                                >
                                                                {{ $grado->pivot->nombre ?? $grado->nombre }}
                                                                <span class="ml-2 rounded-full px-2 py-0.5 text-xs {{ $esObligatoria ? 'bg-indigo-50 text-indigo-700' : 'bg-slate-100 text-slate-600' }}">
                                                                    {{ $esObligatoria ? 'Obligatoria · tronco común' : 'Optativa o adicional' }}
                                                                </span>
                                                            </label>
                                                            @if ($esObligatoria)
                                                                <input type="hidden" name="asignaturas[{{ $asignatura->id }}][grados][]" value="{{ $grado->id }}">
                                                            @endif

                                                            <div
                                                                class="mt-3 grid items-start gap-3 sm:grid-cols-2"
                                                                x-data="{ escala: @js($tipo) }"
                                                                x-show="(asignaturas['{{ $asignaturaKey }}'] || []).map(String).includes('{{ $gradoKey }}')"
                                                            >
                                                                <div>
                                                                    <label class="block text-xs text-slate-500" for="tipo-{{ $asignatura->id }}-{{ $grado->id }}">
                                                                        Escala de calificación
                                                                    </label>
                                                                    <select
                                                                        id="tipo-{{ $asignatura->id }}-{{ $grado->id }}"
                                                                        name="asignaturas[{{ $asignatura->id }}][tipo_calificacion][{{ $grado->id }}]"
                                                                        class="mt-1 block w-full rounded-md border-slate-300 text-sm"
                                                                        x-model="escala"
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
                                                                    <x-input-error class="mt-1" :messages="$errors->get('asignaturas.'.$asignatura->id.'.tipo_calificacion.'.$grado->id)" />
                                                                    <label
                                                                        class="mt-2 flex items-center gap-2 text-sm text-slate-700"
                                                                        x-show="escala === 'cualitativa'"
                                                                        x-cloak
                                                                    >
                                                                        <input
                                                                            type="checkbox"
                                                                            class="rounded border-slate-300"
                                                                            name="asignaturas[{{ $asignatura->id }}][califica_por_destrezas][{{ $grado->id }}]"
                                                                            value="1"
                                                                            @checked($destrezas)
                                                                            :disabled="escala !== 'cualitativa'"
                                                                        >
                                                                        Califica por destrezas
                                                                    </label>
                                                                    <label
                                                                        class="mt-2 flex items-start gap-2 text-sm text-slate-700"
                                                                        x-show="escala === 'cuantitativa'"
                                                                        x-cloak
                                                                    >
                                                                        <input
                                                                            type="checkbox"
                                                                            class="mt-0.5 rounded border-slate-300"
                                                                            name="asignaturas[{{ $asignatura->id }}][usa_tabla_equivalencias][{{ $grado->id }}]"
                                                                            value="1"
                                                                            @checked($equivalencias)
                                                                            :disabled="escala !== 'cuantitativa'"
                                                                        >
                                                                        <span>
                                                                            Tabla de equivalencias
                                                                            <span class="block text-xs text-slate-500">Para presentar letras al ministerio.</span>
                                                                        </span>
                                                                    </label>
                                                                    <div
                                                                        class="mt-3"
                                                                        x-show="escala === 'cuantitativa' && usaPorcentaje('{{ $area->id }}', '{{ $grado->id }}', @js($asignaturaIdsDelArea))"
                                                                        x-cloak
                                                                    >
                                                                        <label class="block text-xs text-slate-500" for="porcentaje-{{ $asignatura->id }}-{{ $grado->id }}">
                                                                            Porcentaje (opcional; vacío usa promedio simple)
                                                                        </label>
                                                                        <input
                                                                            id="porcentaje-{{ $asignatura->id }}-{{ $grado->id }}"
                                                                            type="number"
                                                                            min="0"
                                                                            max="100"
                                                                            step="0.01"
                                                                            name="asignaturas[{{ $asignatura->id }}][porcentaje][{{ $grado->id }}]"
                                                                            value="{{ $porcentaje }}"
                                                                            class="mt-1 block w-full rounded-md border-slate-300 text-sm"
                                                                            :disabled="escala !== 'cuantitativa' || !usaPorcentaje('{{ $area->id }}', '{{ $grado->id }}', @js($asignaturaIdsDelArea))"
                                                                        >
                                                                        <x-input-error class="mt-1" :messages="$errors->get('asignaturas.'.$asignatura->id.'.porcentaje.'.$grado->id)" />
                                                                    </div>
                                                                </div>
                                                                <div class="flex items-center pt-5">
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
                                                </div>
                                            </div>
                                        @empty
                                            <p class="text-sm text-slate-500">
                                                Sistemas aún no ha definido {{ $esInicial ? 'ámbitos' : 'asignaturas' }} en esta área.
                                            </p>
                                        @endforelse

                                        @foreach ($propiasDelArea as $propia)
                                            <div class="rounded-xl border border-slate-200 p-4" x-init="asignaturas['{{ $propia['alpine'] }}'] = asignaturas['{{ $propia['alpine'] }}'] || []">
                                                <div class="grid gap-3 sm:grid-cols-[minmax(0,1fr)_12rem]">
                                                    <div>
                                                        <label class="block text-xs text-slate-500" for="propia-nombre-{{ $propia['clave'] }}">Nombre</label>
                                                        <input
                                                            id="propia-nombre-{{ $propia['clave'] }}"
                                                            type="text"
                                                            name="propias[{{ $propia['clave'] }}][nombre]"
                                                            value="{{ $propia['nombre'] }}"
                                                            maxlength="150"
                                                            class="mt-1 block w-full rounded-md border-slate-300 text-sm"
                                                        >
                                                        <x-input-error class="mt-1" :messages="$errors->get('propias.'.$propia['clave'].'.nombre')" />
                                                    </div>
                                                    <div>
                                                        <label class="block text-xs text-slate-500" for="propia-tipo-{{ $propia['clave'] }}">Tipo</label>
                                                        <select
                                                            id="propia-tipo-{{ $propia['clave'] }}"
                                                            name="propias[{{ $propia['clave'] }}][tipo]"
                                                            class="mt-1 block w-full rounded-md border-slate-300 text-sm"
                                                        >
                                                            @foreach ($tiposAsignatura as $tipoAsignatura)
                                                                <option value="{{ $tipoAsignatura->value }}" @selected($propia['tipo'] === $tipoAsignatura->value)>
                                                                    {{ $tipoAsignatura->label() }}
                                                                </option>
                                                            @endforeach
                                                        </select>
                                                        <x-input-error class="mt-1" :messages="$errors->get('propias.'.$propia['clave'].'.tipo')" />
                                                    </div>
                                                </div>
                                                <input type="hidden" name="propias[{{ $propia['clave'] }}][area_id]" value="{{ $area->id }}">
                                                <label class="mt-3 flex items-center gap-2 text-sm text-slate-600">
                                                    <input type="checkbox" class="rounded border-slate-300" name="propias[{{ $propia['clave'] }}][eliminar]" value="1" @checked($propia['eliminar'])>
                                                    Quitar esta asignatura
                                                </label>
                                                <x-input-error class="mt-1" :messages="$errors->get('propias.'.$propia['clave'].'.grados')" />

                                                <div class="mt-3 space-y-3">
                                                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Grados</p>
                                                    @foreach ($gradosDelSubnivel as $grado)
                                                        @php
                                                            $gradoKey = (string) $grado->id;
                                                            $config = $configPorAsignatura[$propia['alpine']][$gradoKey] ?? [];
                                                            $libreta = array_key_exists('libreta', $config) ? (bool) $config['libreta'] : true;
                                                            $tipo = ($config['tipo'] ?? '') !== ''
                                                                ? $config['tipo']
                                                                : ($subnivel->tipo_calificacion?->value ?? TipoCalificacion::Cuantitativa->value);
                                                            $destrezas = (bool) ($config['destrezas'] ?? false);
                                                            $equivalencias = (bool) ($config['equivalencias'] ?? false);
                                                            $porcentaje = $config['porcentaje'] ?? '';
                                                        @endphp
                                                        <div class="rounded-lg bg-slate-50 p-3">
                                                            <label class="flex items-center gap-2 text-sm text-slate-800">
                                                                <input
                                                                    type="checkbox"
                                                                    class="rounded border-slate-300"
                                                                    name="propias[{{ $propia['clave'] }}][grados][]"
                                                                    value="{{ $grado->id }}"
                                                                    x-model="asignaturas['{{ $propia['alpine'] }}']"
                                                                >
                                                                {{ $grado->pivot->nombre ?? $grado->nombre }}
                                                            </label>
                                                            <div
                                                                class="mt-3 grid items-start gap-3 sm:grid-cols-2"
                                                                x-data="{ escala: @js($tipo) }"
                                                                x-show="(asignaturas['{{ $propia['alpine'] }}'] || []).map(String).includes('{{ $gradoKey }}')"
                                                            >
                                                                <div>
                                                                    <label class="block text-xs text-slate-500">Escala de calificación</label>
                                                                    <select
                                                                        name="propias[{{ $propia['clave'] }}][tipo_calificacion][{{ $grado->id }}]"
                                                                        class="mt-1 block w-full rounded-md border-slate-300 text-sm"
                                                                        x-model="escala"
                                                                    >
                                                                        @foreach ($tiposCalificacion as $tipoCalificacion)
                                                                            <option value="{{ $tipoCalificacion->value }}" @selected($tipo === $tipoCalificacion->value)>{{ $tipoCalificacion->label() }}</option>
                                                                        @endforeach
                                                                    </select>
                                                                    <label class="mt-2 flex items-center gap-2 text-sm text-slate-700" x-show="escala === 'cualitativa'" x-cloak>
                                                                        <input type="checkbox" class="rounded border-slate-300" name="propias[{{ $propia['clave'] }}][califica_por_destrezas][{{ $grado->id }}]" value="1" @checked($destrezas) :disabled="escala !== 'cualitativa'">
                                                                        Califica por destrezas
                                                                    </label>
                                                                    <label class="mt-2 flex items-start gap-2 text-sm text-slate-700" x-show="escala === 'cuantitativa'" x-cloak>
                                                                        <input type="checkbox" class="mt-0.5 rounded border-slate-300" name="propias[{{ $propia['clave'] }}][usa_tabla_equivalencias][{{ $grado->id }}]" value="1" @checked($equivalencias) :disabled="escala !== 'cuantitativa'">
                                                                        <span>Tabla de equivalencias <span class="block text-xs text-slate-500">Para presentar letras al ministerio.</span></span>
                                                                    </label>
                                                                    <div class="mt-3" x-show="escala === 'cuantitativa' && usaPorcentaje('{{ $area->id }}', '{{ $grado->id }}', @js($asignaturaIdsDelArea))" x-cloak>
                                                                        <label class="block text-xs text-slate-500">Porcentaje (opcional; vacío usa promedio simple)</label>
                                                                        <input type="number" min="0" max="100" step="0.01" name="propias[{{ $propia['clave'] }}][porcentaje][{{ $grado->id }}]" value="{{ $porcentaje }}" class="mt-1 block w-full rounded-md border-slate-300 text-sm" :disabled="escala !== 'cuantitativa' || !usaPorcentaje('{{ $area->id }}', '{{ $grado->id }}', @js($asignaturaIdsDelArea))">
                                                                        <x-input-error class="mt-1" :messages="$errors->get('propias.'.$propia['clave'].'.porcentaje.'.$grado->id)" />
                                                                    </div>
                                                                </div>
                                                                <div class="flex items-center pt-5">
                                                                    <input type="hidden" name="propias[{{ $propia['clave'] }}][libreta][{{ $grado->id }}]" value="0">
                                                                    <label class="flex items-center gap-2 text-sm text-slate-700">
                                                                        <input type="checkbox" class="rounded border-slate-300" name="propias[{{ $propia['clave'] }}][libreta][{{ $grado->id }}]" value="1" @checked($libreta)>
                                                                        Aparece en libreta
                                                                    </label>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            </div>
                                        @endforeach

                                        <template x-for="item in (nuevas['{{ $area->id }}'] || [])" :key="item.clave">
                                            <div class="rounded-xl border border-dashed border-slate-300 p-4">
                                                <div class="grid gap-3 sm:grid-cols-[minmax(0,1fr)_12rem]">
                                                    <div>
                                                        <label class="block text-xs text-slate-500">Nombre</label>
                                                        <input type="text" maxlength="150" class="mt-1 block w-full rounded-md border-slate-300 text-sm" x-model="item.nombre" :name="`propias[${item.clave}][nombre]`">
                                                    </div>
                                                    <div>
                                                        <label class="block text-xs text-slate-500">Tipo</label>
                                                        <select class="mt-1 block w-full rounded-md border-slate-300 text-sm" x-model="item.tipo" :name="`propias[${item.clave}][tipo]`">
                                                            @foreach ($tiposAsignatura as $tipoAsignatura)
                                                                <option value="{{ $tipoAsignatura->value }}">{{ $tipoAsignatura->label() }}</option>
                                                            @endforeach
                                                        </select>
                                                    </div>
                                                </div>
                                                <input type="hidden" :name="`propias[${item.clave}][area_id]`" value="{{ $area->id }}">
                                                <button type="button" class="mt-3 text-sm font-medium text-slate-500 hover:text-slate-800" @click="quitarNueva('{{ $area->id }}', item.clave)">Quitar</button>
                                                <div class="mt-3 space-y-3">
                                                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Grados</p>
                                                    @foreach ($gradosDelSubnivel as $grado)
                                                        @php
                                                            $tipoNueva = $subnivel->tipo_calificacion?->value ?? TipoCalificacion::Cuantitativa->value;
                                                        @endphp
                                                        <div class="rounded-lg bg-slate-50 p-3">
                                                            <label class="flex items-center gap-2 text-sm text-slate-800">
                                                                <input type="checkbox" class="rounded border-slate-300" value="{{ $grado->id }}" :name="`propias[${item.clave}][grados][]`" x-model="asignaturas[item.clave]">
                                                                {{ $grado->pivot->nombre ?? $grado->nombre }}
                                                            </label>
                                                            <div class="mt-3 grid items-start gap-3 sm:grid-cols-2" x-data="{ escala: @js($tipoNueva) }" x-show="(asignaturas[item.clave] || []).map(String).includes('{{ $grado->id }}')">
                                                                <div>
                                                                    <label class="block text-xs text-slate-500">Escala de calificación</label>
                                                                    <select class="mt-1 block w-full rounded-md border-slate-300 text-sm" x-model="escala" :name="`propias[${item.clave}][tipo_calificacion][{{ $grado->id }}]`">
                                                                        @foreach ($tiposCalificacion as $tipoCalificacion)
                                                                            <option value="{{ $tipoCalificacion->value }}">{{ $tipoCalificacion->label() }}</option>
                                                                        @endforeach
                                                                    </select>
                                                                    <label class="mt-2 flex items-center gap-2 text-sm text-slate-700" x-show="escala === 'cualitativa'" x-cloak>
                                                                        <input type="checkbox" class="rounded border-slate-300" value="1" :name="`propias[${item.clave}][califica_por_destrezas][{{ $grado->id }}]`" :disabled="escala !== 'cualitativa'">
                                                                        Califica por destrezas
                                                                    </label>
                                                                    <label class="mt-2 flex items-start gap-2 text-sm text-slate-700" x-show="escala === 'cuantitativa'" x-cloak>
                                                                        <input type="checkbox" class="mt-0.5 rounded border-slate-300" value="1" :name="`propias[${item.clave}][usa_tabla_equivalencias][{{ $grado->id }}]`" :disabled="escala !== 'cuantitativa'">
                                                                        <span>Tabla de equivalencias <span class="block text-xs text-slate-500">Para presentar letras al ministerio.</span></span>
                                                                    </label>
                                                                    <div class="mt-3" x-show="escala === 'cuantitativa' && usaPorcentaje('{{ $area->id }}', '{{ $grado->id }}', @js($asignaturaIdsDelArea))" x-cloak>
                                                                        <label class="block text-xs text-slate-500">Porcentaje (opcional; vacío usa promedio simple)</label>
                                                                        <input type="number" min="0" max="100" step="0.01" class="mt-1 block w-full rounded-md border-slate-300 text-sm" :name="`propias[${item.clave}][porcentaje][{{ $grado->id }}]`" :disabled="escala !== 'cuantitativa' || !usaPorcentaje('{{ $area->id }}', '{{ $grado->id }}', @js($asignaturaIdsDelArea))">
                                                                    </div>
                                                                </div>
                                                                <div class="flex items-center pt-5">
                                                                    <input type="hidden" value="0" :name="`propias[${item.clave}][libreta][{{ $grado->id }}]`">
                                                                    <label class="flex items-center gap-2 text-sm text-slate-700">
                                                                        <input type="checkbox" class="rounded border-slate-300" value="1" checked :name="`propias[${item.clave}][libreta][{{ $grado->id }}]`">
                                                                        Aparece en libreta
                                                                    </label>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            </div>
                                        </template>

                                        <button
                                            type="button"
                                            class="text-sm font-medium text-indigo-600 hover:text-indigo-500"
                                            @click="agregarAsignatura('{{ $area->id }}', @js($gradoIds))"
                                        >
                                            Agregar asignatura
                                        </button>
                                        </div>
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
