@php
    $docentesBag = $errors->getBag('aula-docentes');
    $aulaDocentesId = (int) old('aula_id');
    $aulaDocentes = $aulas->firstWhere('id', $aulaDocentesId);
    $aulasSeleccionadas = collect(old('aulas', $aulaDocentes === null ? [] : [$aulaDocentes->id]))
        ->map(fn (mixed $id): string => (string) $id)
        ->filter()
        ->values()
        ->all();
    $materiasIniciales = [];
    $gradoInicialId = $aulaDocentes === null ? '' : (string) $aulaDocentes->establecimiento_grado_id;

    if ($aulaDocentes !== null) {
        $docentesAnteriores = (array) old('docentes', []);
        $materiasIniciales = collect($materiasPorGrado[$aulaDocentes->establecimiento_grado_id] ?? [])
            ->map(function (array $materia) use ($docentesAnteriores, $aulaDocentes): array {
                $asignacion = $aulaDocentes->asignaciones->firstWhere('establecimiento_asignatura_id', $materia['id']);
                $empleadoId = array_key_exists($materia['id'], $docentesAnteriores) || array_key_exists((string) $materia['id'], $docentesAnteriores)
                    ? (string) ($docentesAnteriores[$materia['id']] ?? $docentesAnteriores[(string) $materia['id']] ?? '')
                    : ($asignacion === null ? '' : (string) $asignacion->empleado_id);

                return [
                    ...$materia,
                    'empleadoId' => $empleadoId,
                ];
            })
            ->values()
            ->all();
    }
@endphp

<x-modal name="aula-docentes" maxWidth="2xl" :show="$docentesBag->isNotEmpty() && $aulaDocentes !== null" focusable>
    <div
        class="p-6"
        x-data="{
            grados: @js($gradosParaDocentes),
            gradoId: @js($gradoInicialId),
            aulaId: @js($aulaDocentes?->id ?? ''),
            aulaIds: @js($aulasSeleccionadas),
            action: @js($aulaDocentes === null ? '' : route('Admin.aulas.docentes.update', $aulaDocentes)),
            materias: @js($materiasIniciales),
            docentesPorAsignatura: @js($docentesPorAsignatura),
            mostrarErrores: @js($docentesBag->isNotEmpty()),
            get paralelos() {
                return this.gradoActual()?.aulas ?? [];
            },
            gradoActual() {
                return this.grados.find((grado) => String(grado.id) === String(this.gradoId)) ?? null;
            },
            docentesDe(materia) {
                return this.docentesPorAsignatura[materia.asignaturaId] || [];
            },
            aulasDelGrado(gradoId) {
                return (this.grados.find((grado) => String(grado.id) === String(gradoId))?.aulas) ?? [];
            },
            syncAction() {
                const seleccionada = this.paralelos.find((aula) => this.aulaIds.map(String).includes(String(aula.id)));

                if (! seleccionada) {
                    return;
                }

                this.aulaId = seleccionada.id;
                this.action = seleccionada.action;
            },
            cambiarGrado() {
                const grado = this.gradoActual();
                this.aulaIds = [];
                this.materias = (grado?.materias ?? []).map((materia) => ({ ...materia, empleadoId: '' }));
                this.syncAction();
            },
            abrir(detalle) {
                this.gradoId = String(detalle.gradoId);
                this.aulaIds = [String(detalle.id)];
                const aula = this.aulasDelGrado(detalle.gradoId).find((item) => String(item.id) === String(detalle.id));
                this.materias = (aula?.materias ?? this.gradoActual()?.materias ?? []).map((materia) => ({
                    ...materia,
                    empleadoId: String(materia.empleadoId ?? ''),
                }));
                this.mostrarErrores = false;
                this.syncAction();
                $dispatch('open-modal', 'aula-docentes');
            },
        }"
        x-on:aula-docentes.window="abrir($event.detail)"
    >
        <form method="POST" x-bind:action="action" action="{{ $aulaDocentes === null ? '' : route('Admin.aulas.docentes.update', $aulaDocentes) }}">
            @csrf
            @method('PATCH')
            <input type="hidden" name="aula_id" x-bind:value="aulaId" value="{{ $aulaDocentes?->id }}">
            <template x-for="id in aulaIds" :key="id">
                <input type="hidden" name="aulas[]" :value="id">
            </template>

            <h3 class="text-lg font-semibold text-slate-800">Docentes</h3>
            <p class="mt-1 text-sm text-slate-500">
                Elige el grado y los paralelos. Si no marcas otros paralelos, se guarda solo en este aula.
            </p>

            <div class="mt-4 flex flex-col gap-4">
                <div>
                    <label for="aula-docentes-grado" class="block text-sm font-medium text-slate-700">Grado</label>
                    <select
                        id="aula-docentes-grado"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        x-model="gradoId"
                        @change="cambiarGrado()"
                    >
                        <template x-for="grado in grados" :key="grado.id">
                            <option :value="String(grado.id)" x-text="grado.nombre"></option>
                        </template>
                    </select>
                </div>

                <div>
                    <p class="text-sm font-medium text-slate-700">Paralelos</p>
                    <div class="mt-2 flex flex-wrap gap-3">
                        <template x-for="aula in paralelos" :key="aula.id">
                            <label class="inline-flex items-center gap-2 text-sm text-slate-700">
                                <input
                                    type="checkbox"
                                    class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500"
                                    :value="String(aula.id)"
                                    x-model="aulaIds"
                                    @change="syncAction()"
                                >
                                <span x-text="aula.paralelo"></span>
                            </label>
                        </template>
                    </div>
                    <p class="mt-1 text-xs text-slate-500" x-show="aulaIds.length === 0">
                        Selecciona al menos un paralelo.
                    </p>
                </div>
            </div>

            <div class="mt-4 space-y-2" x-show="mostrarErrores">
                @foreach ($docentesBag->all() as $mensaje)
                    <p class="text-sm text-red-600">{{ $mensaje }}</p>
                @endforeach
            </div>

            <div class="mt-4 flex flex-col gap-4" x-show="materias.length > 0">
                <template x-for="materia in materias" :key="materia.id">
                    <div>
                        <label class="block text-sm font-medium text-slate-700" :for="'docente-materia-' + materia.id" x-text="materia.nombre"></label>
                        <select
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            :id="'docente-materia-' + materia.id"
                            :name="'docentes[' + materia.id + ']'"
                            x-model="materia.empleadoId"
                        >
                            <option value="">Sin docente</option>
                            <template x-for="docente in docentesDe(materia)" :key="docente.id">
                                <option :value="String(docente.id)" x-text="docente.nombre"></option>
                            </template>
                        </select>
                        <p class="mt-1 text-xs text-slate-500" x-show="docentesDe(materia).length === 0">
                            No hay docentes activos.
                        </p>
                    </div>
                </template>
            </div>

            <p class="mt-4 text-sm text-slate-500" x-show="materias.length === 0">
                Este grado no tiene asignaturas en la malla.
            </p>

            <div class="mt-6 flex justify-end gap-2">
                <x-secondary-button type="button" x-on:click="$dispatch('close')">Cancelar</x-secondary-button>
                <x-primary-button x-show="materias.length > 0 && aulaIds.length > 0">Guardar</x-primary-button>
            </div>
        </form>
    </div>
</x-modal>
