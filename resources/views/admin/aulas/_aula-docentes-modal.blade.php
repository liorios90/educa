@php
    $docentesBag = $errors->getBag('aula-docentes');
    $aulaDocentesId = (int) old('aula_id');
    $aulaDocentes = $aulas->firstWhere('id', $aulaDocentesId);
    $materiasIniciales = [];

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
            aulaId: @js($aulaDocentes?->id ?? ''),
            etiqueta: @js($aulaDocentes?->etiqueta() ?? ''),
            action: @js($aulaDocentes === null ? '' : route('Admin.aulas.docentes.update', $aulaDocentes)),
            materias: @js($materiasIniciales),
            docentesPorAsignatura: @js($docentesPorAsignatura),
            mostrarErrores: @js($docentesBag->isNotEmpty()),
            docentesDe(materia) {
                return this.docentesPorAsignatura[materia.asignaturaId] || [];
            },
        }"
        x-on:aula-docentes.window="
            aulaId = $event.detail.id;
            etiqueta = $event.detail.etiqueta;
            action = $event.detail.action;
            materias = $event.detail.materias.map((materia) => ({ ...materia, empleadoId: String(materia.empleadoId ?? '') }));
            mostrarErrores = false;
            $dispatch('open-modal', 'aula-docentes');
        "
    >
        <form method="POST" x-bind:action="action" action="{{ $aulaDocentes === null ? '' : route('Admin.aulas.docentes.update', $aulaDocentes) }}">
            @csrf
            @method('PATCH')
            <input type="hidden" name="aula_id" x-bind:value="aulaId" value="{{ $aulaDocentes?->id }}">

            <h3 class="text-lg font-semibold text-slate-800">Docentes</h3>
            <p class="mt-1 text-sm text-slate-500" x-text="etiqueta">{{ $aulaDocentes?->etiqueta() }}</p>

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
                            Ningún docente activo tiene esta especialidad.
                        </p>
                    </div>
                </template>
            </div>

            <p class="mt-4 text-sm text-slate-500" x-show="materias.length === 0">
                Este grado no tiene asignaturas en la malla.
            </p>

            <div class="mt-6 flex justify-end gap-2">
                <x-secondary-button type="button" x-on:click="$dispatch('close')">Cancelar</x-secondary-button>
                <x-primary-button x-show="materias.length > 0">Guardar</x-primary-button>
            </div>
        </form>
    </div>
</x-modal>
