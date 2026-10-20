@php
    use App\Enums\ModoLibretaArea;
    use App\Enums\TipoAsignatura;
    use App\Enums\TipoCalificacion;

    $niveles = $subniveles->pluck('nivel')->filter()->unique('id')->values();
    $activeNivelId = $niveles->first()?->id;
    $nivelBachilleratoId = $niveles->firstWhere('nombre', 'Bachillerato')?->id;
    $activeSubnivelId = $subniveles->first(fn ($subnivel) => (int) $subnivel->nivel_id === (int) $activeNivelId)?->id;
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
            activeNivelId: @js($activeNivelId),
            nivelBachilleratoId: @js($nivelBachilleratoId),
            activeSubnivelId: @js($activeSubnivelId),
            pantallaAsignaturasArea: null,
            configuracionArea: null,
            asignaturas: @js($selectedGradosPorAsignatura),
            nuevas: {},
            figurasProfesionales: @js($figurasProfesionales->map(fn ($figura) => ['id' => $figura->id, 'nombre' => $figura->nombre])->values()),
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
                this.nuevas[areaId].push({ clave, nombre: '', tipo: 'optativa', figura_profesional_id: '' })
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
            seleccionarNivel(nivelId, subnivelId) {
                this.activeNivelId = nivelId
                this.activeSubnivelId = subnivelId
            },
            get nivelEsBachillerato() {
                return this.nivelBachilleratoId !== null && Number(this.activeNivelId) === Number(this.nivelBachilleratoId)
            },
        }"
    >
        <div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">
            @if ($periodo !== null && $establecimiento->bachillerato_tecnico)
                <section x-show="nivelEsBachillerato" x-cloak class="mb-5 rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                    <h3 class="font-semibold text-slate-800">Figuras profesionales del Bachillerato Técnico</h3>
                    <p class="mt-1 text-sm text-slate-500">El Bachillerato Técnico es una opción dentro del BGU: conserva los tres cursos y el tronco común, y añade los módulos de la figura profesional elegida.</p>
                    @if (session('status') === 'figura-profesional-created')
                        <p class="mt-3 text-sm font-medium text-green-700">Especialidad creada.</p>
                    @endif
                    @if (session('status') === 'figura-profesional-deleted')
                        <p class="mt-3 text-sm font-medium text-green-700">Especialidad eliminada.</p>
                    @endif
                    @if ($errors->has('figura_profesional'))
                        <p class="mt-3 text-sm font-medium text-red-700">{{ $errors->first('figura_profesional') }}</p>
                    @endif
                    <form method="POST" action="{{ route('Admin.asignaturas.figuras-profesionales.store', $oferta) }}" class="mt-4 flex flex-wrap items-end gap-3">
                        @csrf
                        <div class="flex min-w-64 flex-1 flex-col gap-1">
                            <x-input-label for="figura-profesional-nombre" value="Nueva especialidad" />
                            <x-text-input id="figura-profesional-nombre" name="nombre" maxlength="150" placeholder="Ej.: Electrónica, Informática, Electromecánica Automotriz" required />
                        </div>
                        <x-primary-button>Agregar especialidad</x-primary-button>
                    </form>
                    @if ($figurasProfesionales->isNotEmpty())
                        <ul class="mt-4 flex flex-wrap gap-2">
                            @foreach ($figurasProfesionales as $figura)
                                <li class="inline-flex items-center gap-2 rounded-lg bg-slate-100 px-3 py-2 text-sm text-slate-700">
                                    {{ $figura->nombre }}
                                    <form method="POST" action="{{ route('Admin.asignaturas.figuras-profesionales.destroy', [$oferta, $figura]) }}">
                                        @csrf
                                        @method('DELETE')
                                        <button class="font-semibold text-red-600" aria-label="Quitar {{ $figura->nombre }}">&times;</button>
                                    </form>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </section>
            @endif
            @if (session('status') === 'asignaturas-updated')
                <p class="mb-4 text-sm font-medium text-green-700">Malla de la jornada guardada correctamente.</p>
            @endif

            <section class="mb-6 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <h3 class="text-base font-semibold text-slate-800">Áreas por nivel y subnivel</h3>
                <p class="mt-2 text-sm leading-6 text-slate-600">
                    Selecciona un nivel y luego un subnivel para administrar sus áreas, asignaturas y presentación en la libreta.
                </p>
            </section>

            @if ($establecimiento->bachillerato_tecnico && $areasTecnicasDisponibles->isNotEmpty())
                <section x-show="nivelEsBachillerato" x-cloak class="mb-6 rounded-2xl border border-amber-200 bg-amber-50 p-6 shadow-sm">
                    <h3 class="font-semibold text-slate-800">Áreas técnicas ofrecidas por este establecimiento</h3>
                    <p class="mt-1 text-sm text-slate-600">Activa las áreas técnicas que ofrece esta jornada. Se administran por separado del tronco común y solo aparecerán aquí después de guardarlas.</p>
                    <x-input-error class="mt-3" :messages="$errors->get('areas_tecnicas')" />
                    @if (session('status') === 'areas-tecnicas-updated')
                        <p class="mt-3 text-sm font-medium text-green-700">Oferta de áreas técnicas actualizada.</p>
                    @endif
                    <form method="POST" action="{{ route('Admin.asignaturas.areas-tecnicas.update', $oferta) }}" class="mt-4 flex flex-col gap-3">
                        @csrf
                        @method('PUT')
                        @foreach ($areasTecnicasDisponibles as $areaTecnica)
                            <label class="flex items-start gap-3 rounded-lg border border-amber-100 bg-white p-3 text-sm text-slate-700">
                                <input type="checkbox" name="areas_tecnicas[]" value="{{ $areaTecnica->id }}" class="mt-0.5 rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500" @checked(in_array($areaTecnica->id, $areasTecnicasSeleccionadas, true))>
                                <span><span class="font-medium">{{ $areaTecnica->nombre }}</span>@if ($areaTecnica->descripcion)<span class="block text-slate-500">{{ $areaTecnica->descripcion }}</span>@endif</span>
                            </label>
                        @endforeach
                        <div><x-primary-button>Guardar áreas técnicas</x-primary-button></div>
                    </form>
                </section>
            @elseif ($establecimiento->bachillerato_tecnico)
                <section x-show="nivelEsBachillerato" x-cloak class="mb-6 rounded-2xl border border-amber-200 bg-amber-50 p-6 shadow-sm">
                    <h3 class="font-semibold text-slate-800">Áreas técnicas</h3>
                    <p class="mt-1 text-sm text-slate-600">Sistemas todavía no ha creado áreas técnicas para los niveles de esta oferta. El catálogo se configura en Sistemas → Currículo, en el subnivel Bachillerato General Unificado, marcando “Área técnica”.</p>
                </section>
            @endif

            <x-input-error class="mb-4" :messages="$errors->get('asignaturas')" />
            <x-input-error class="mb-4" :messages="$errors->get('areas')" />

            <form method="POST" action="{{ route('Admin.asignaturas.update', $oferta) }}">
                @csrf
                @method('PUT')

                @if ($subniveles->isEmpty())
                    <p class="rounded-2xl border border-slate-200 bg-white px-6 py-8 text-center text-slate-500">
                        Sin grados en la estructura para esta jornada.
                        <a href="{{ route('Admin.estructura.edit', $oferta) }}" class="font-medium text-indigo-600 hover:text-indigo-500">
                            Definir estructura
                        </a>
                    </p>
                @else
                    <div x-show="pantallaAsignaturasArea === null" class="mb-3">
                        <label for="nivel-select" class="mb-1 block text-sm font-medium text-slate-700">Nivel</label>
                        <select
                            id="nivel-select"
                            class="w-full max-w-md rounded-lg border-slate-300 text-sm text-slate-700 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            x-model.number="activeNivelId"
                            @change="seleccionarNivel(Number($event.target.value), Number($event.target.selectedOptions[0].dataset.subnivelId))"
                        >
                            @foreach ($niveles as $nivel)
                                @php
                                    $primerSubnivelDelNivel = $subniveles->first(fn ($subnivel) => (int) $subnivel->nivel_id === (int) $nivel->id);
                                @endphp
                                <option value="{{ $nivel->id }}" data-subnivel-id="{{ $primerSubnivelDelNivel?->id }}">
                                    {{ $nivel->nombre }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div x-show="pantallaAsignaturasArea === null" class="mb-4 flex flex-wrap gap-2">
                        @foreach ($subniveles as $subnivel)
                            <button
                                type="button"
                                class="rounded-lg px-3 py-2 text-sm font-medium"
                                :class="activeSubnivelId === {{ $subnivel->id }} ? 'bg-slate-900 text-white' : 'bg-white text-slate-700 border border-slate-200'"
                                x-show="activeNivelId === {{ $subnivel->nivel_id }}"
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
                            <h3 class="text-sm font-semibold text-slate-700">Subnivel: {{ $subnivel->nombre }}</h3>
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
                                        <div class="sticky bottom-0 -mx-5 mt-5 flex justify-end border-t border-slate-200 bg-white/95 px-5 py-4 backdrop-blur-sm sm:-mx-6 sm:px-6">
                                            <x-primary-button type="submit">Guardar malla</x-primary-button>
                                        </div>
                                      </div>
                                    </div>

                                    <div x-show="pantallaAsignaturasArea === '{{ $area->id }}'" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/50 p-4" @keydown.escape.window="pantallaAsignaturasArea = null">
                                      <div class="max-h-[92vh] w-full max-w-6xl overflow-y-auto rounded-2xl bg-white p-5 shadow-xl sm:p-6" @click.outside="pantallaAsignaturasArea = null">
                                        <div class="mb-5 flex items-center justify-between gap-4">
                                            <div><h5 class="text-lg font-semibold text-slate-900">Asignaturas · {{ $area->nombre }}</h5><p class="mt-1 text-sm text-slate-500">Selecciona las asignaturas, grados y opciones para la libreta.</p></div>
                                            <button type="button" class="rounded-lg px-3 py-2 text-sm text-slate-600 hover:bg-slate-100" @click="pantallaAsignaturasArea = null">Cerrar</button>
                                        </div>
                                        <div class="overflow-x-auto rounded-xl border border-slate-200">
                                            <table class="min-w-full divide-y divide-slate-200 text-sm">
                                                <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                                    <tr><th class="px-4 py-3">{{ $etiquetaAsignatura }}</th><th class="px-4 py-3">Grado</th><th class="px-4 py-3">Seleccionar</th><th class="px-4 py-3">En libreta</th><th class="px-4 py-3">Calificación</th><th class="px-4 py-3">Con Equivalencia</th></tr>
                                                </thead>
                                                <tbody class="divide-y divide-slate-100">
                                                @forelse ($area->asignaturas as $asignatura)
                                                    @php
                                                        $asignaturaKey = (string) $asignatura->id;
                                                        $gradosObligatoriosIds = $asignatura->gradosCurriculares
                                                            ->filter(fn ($gradoCurricular): bool => (bool) $gradoCurricular->pivot->obligatoria)
                                                            ->pluck('id')
                                                            ->map(strval(...))
                                                            ->all();
                                                    @endphp
                                                    @foreach ($gradosDelSubnivel as $grado)
                                                        @php
                                                            $gradoKey = (string) $grado->id;
                                                            $esObligatoria = in_array($gradoKey, $gradosObligatoriosIds, true);
                                                            $config = $configPorAsignatura[$asignaturaKey][$gradoKey] ?? [];
                                                            $libreta = array_key_exists('libreta', $config) ? (bool) $config['libreta'] : (bool) $asignatura->aparece_en_libreta;
                                                            $tipo = ($config['tipo'] ?? '') !== '' ? $config['tipo'] : ($subnivel->tipo_calificacion?->value ?? TipoCalificacion::Cuantitativa->value);
                                                            $destrezas = array_key_exists('destrezas', $config) ? (bool) $config['destrezas'] : ($tipo === TipoCalificacion::Cualitativa->value && (bool) $subnivel->califica_por_destrezas);
                                                            $equivalencias = (bool) ($config['equivalencias'] ?? false);
                                                            $porcentaje = $config['porcentaje'] ?? '';
                                                            $figuraProfesionalId = (string) ($config['figura_profesional_id'] ?? '');
                                                        @endphp
                                                        <tr x-data="{ escala: @js($tipo) }" class="align-top">
                                                            <td class="px-4 py-3 font-medium text-slate-800">{{ $asignatura->nombre }} <span class="block text-xs font-normal text-slate-500">Catálogo de Sistemas</span>
                                                                @if ($area->es_tecnica && $establecimiento->bachillerato_tecnico)
                                                                    <label class="mt-2 block text-xs font-normal text-slate-500" for="asignatura-figura-{{ $asignatura->id }}-{{ $grado->id }}">Figura profesional</label>
                                                                    <select id="asignatura-figura-{{ $asignatura->id }}-{{ $grado->id }}" name="asignaturas[{{ $asignatura->id }}][figura_profesional_id][{{ $grado->id }}]" class="mt-1 block w-52 rounded-md border-slate-300 text-xs">
                                                                        <option value="">Sin figura profesional</option>
                                                                        @foreach ($figurasProfesionales as $figura)
                                                                            <option value="{{ $figura->id }}" @selected($figuraProfesionalId === (string) $figura->id)>{{ $figura->nombre }}</option>
                                                                        @endforeach
                                                                    </select>
                                                                    <x-input-error class="mt-1" :messages="$errors->get('asignaturas.'.$asignatura->id.'.figura_profesional_id.'.$grado->id)" />
                                                                @endif
                                                            </td>
                                                            <td class="px-4 py-3 text-slate-700">{{ $grado->pivot->nombre ?? $grado->nombre }} @if ($esObligatoria)<span class="block text-xs text-indigo-700">Tronco común</span>@endif</td>
                                                            <td class="px-4 py-3">
                                                                <input type="checkbox" class="rounded border-slate-300" name="asignaturas[{{ $asignatura->id }}][grados][]" value="{{ $grado->id }}" @checked($esObligatoria) @disabled($esObligatoria) x-model="asignaturas['{{ $asignaturaKey }}']" aria-label="Seleccionar {{ $asignatura->nombre }} para {{ $grado->nombre }}">
                                                                @if ($esObligatoria)<input type="hidden" name="asignaturas[{{ $asignatura->id }}][grados][]" value="{{ $grado->id }}">@endif
                                                            </td>
                                                            <td class="px-4 py-3">
                                                                <input type="hidden" name="asignaturas[{{ $asignatura->id }}][libreta][{{ $grado->id }}]" value="0">
                                                                <input type="checkbox" class="rounded border-slate-300" name="asignaturas[{{ $asignatura->id }}][libreta][{{ $grado->id }}]" value="1" @checked($libreta) aria-label="Mostrar {{ $asignatura->nombre }} en libreta">
                                                            </td>
                                                            <td class="min-w-48 px-4 py-3">
                                                                <select name="asignaturas[{{ $asignatura->id }}][tipo_calificacion][{{ $grado->id }}]" class="block w-full rounded-md border-slate-300 text-sm" x-model="escala" aria-label="Tipo de calificación de {{ $asignatura->nombre }}">
                                                                    @foreach ($tiposCalificacion as $tipoCalificacion)<option value="{{ $tipoCalificacion->value }}" @selected($tipo === $tipoCalificacion->value)>{{ $tipoCalificacion->label() }}</option>@endforeach
                                                                </select>
                                                                <label class="mt-2 flex items-center gap-2 text-xs text-slate-600" x-show="escala === 'cualitativa'" x-cloak>
                                                                    <input type="checkbox" class="rounded border-slate-300" name="asignaturas[{{ $asignatura->id }}][califica_por_destrezas][{{ $grado->id }}]" value="1" @checked($destrezas) :disabled="escala !== 'cualitativa'"> Califica por destrezas
                                                                </label>
                                                                <input type="number" min="0" max="100" step="0.01" name="asignaturas[{{ $asignatura->id }}][porcentaje][{{ $grado->id }}]" value="{{ $porcentaje }}" placeholder="Porcentaje opcional" class="mt-2 block w-full rounded-md border-slate-300 text-xs" x-show="escala === 'cuantitativa' && usaPorcentaje('{{ $area->id }}', '{{ $grado->id }}', @js($asignaturaIdsDelArea))" :disabled="escala !== 'cuantitativa' || !usaPorcentaje('{{ $area->id }}', '{{ $grado->id }}', @js($asignaturaIdsDelArea))" x-cloak>
                                                            </td>
                                                            <td class="px-4 py-3">
                                                                <input type="checkbox" class="rounded border-slate-300" name="asignaturas[{{ $asignatura->id }}][usa_tabla_equivalencias][{{ $grado->id }}]" value="1" @checked($equivalencias) :disabled="escala !== 'cuantitativa'" x-show="escala === 'cuantitativa'" x-cloak aria-label="{{ $asignatura->nombre }} con equivalencia para {{ $grado->pivot->nombre ?? $grado->nombre }}">
                                                            </td>
                                                        </tr>
                                                    @endforeach
                                                @empty
                                                    <tr><td colspan="6" class="px-4 py-6 text-center text-slate-500">Sistemas aún no ha definido asignaturas en esta área.</td></tr>
                                                @endforelse
                                                </tbody>
                                            </table>
                                        </div>

                                        @if ($propiasDelArea !== [])
                                            <div class="mt-5 space-y-4">
                                                <h6 class="text-sm font-semibold text-slate-800">Asignaturas creadas por el establecimiento</h6>
                                        @foreach ($propiasDelArea as $propia)
                                            <div class="rounded-xl border border-slate-200 p-4" x-init="asignaturas['{{ $propia['alpine'] }}'] = asignaturas['{{ $propia['alpine'] }}'] || []">
                                                <div class="grid gap-3 sm:grid-cols-3">
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
                                                    @if ($establecimiento->bachillerato_tecnico)
                                                    <div>
                                                        <label class="block text-xs text-slate-500" for="propia-figura-{{ $propia['clave'] }}">Figura profesional (solo módulos técnicos)</label>
                                                        <select id="propia-figura-{{ $propia['clave'] }}" name="propias[{{ $propia['clave'] }}][figura_profesional_id]" class="mt-1 block w-full rounded-md border-slate-300 text-sm">
                                                            <option value="">Asignatura común / sin especialidad</option>
                                                            @foreach ($figurasProfesionales as $figura)
                                                                <option value="{{ $figura->id }}" @selected((string) $propia['figura_profesional_id'] === (string) $figura->id)>{{ $figura->nombre }}</option>
                                                            @endforeach
                                                        </select>
                                                    </div>
                                                    @endif
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
                                            </div>
                                        @endif

                                        <template x-for="item in (nuevas['{{ $area->id }}'] || [])" :key="item.clave">
                                            <div class="rounded-xl border border-dashed border-slate-300 p-4">
                                                <div class="grid gap-3 sm:grid-cols-3">
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
                                                    @if ($establecimiento->bachillerato_tecnico)
                                                    <div>
                                                        <label class="block text-xs text-slate-500">Figura profesional (solo módulos técnicos)</label>
                                                        <select class="mt-1 block w-full rounded-md border-slate-300 text-sm" x-model="item.figura_profesional_id" :name="`propias[${item.clave}][figura_profesional_id]`">
                                                            <option value="">Asignatura común / sin especialidad</option>
                                                            <template x-for="figura in figurasProfesionales" :key="figura.id">
                                                                <option :value="figura.id" x-text="figura.nombre"></option>
                                                            </template>
                                                        </select>
                                                    </div>
                                                    @endif
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
                                            class="mt-5 rounded-lg border border-slate-300 px-3 py-2 text-sm font-medium text-indigo-700 hover:bg-slate-50"
                                            @click="agregarAsignatura('{{ $area->id }}', @js($gradoIds))"
                                        >
                                            Crear nueva asignatura
                                        </button>
                                        <div class="sticky bottom-0 -mx-5 mt-5 flex justify-end border-t border-slate-200 bg-white/95 px-5 py-4 backdrop-blur-sm sm:-mx-6 sm:px-6">
                                            <x-primary-button type="submit">Guardar malla</x-primary-button>
                                        </div>
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

                @endif
            </form>
        </div>
    </div>
</x-app-layout>
