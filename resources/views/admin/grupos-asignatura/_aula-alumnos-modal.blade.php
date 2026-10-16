@php
    $aulaModal = collect($aulasMatricula)->firstWhere('id', (int) old('establecimiento_aula_id'));
    $mostrarModalAlumnos = $aulaModal !== null && $errors->has('alumno_id');
@endphp

<x-modal name="aula-alumnos" maxWidth="2xl" :show="$mostrarModalAlumnos" focusable>
    <div
        class="p-6"
        x-data="{
            etiqueta: @js($aulaModal['etiqueta'] ?? ''),
            matriculados: @js($aulaModal['alumnos'] ?? []),
        }"
        x-on:abrir-aula-alumnos.window="
            etiqueta = $event.detail.etiqueta;
            matriculados = $event.detail.alumnos;
            $dispatch('open-modal', 'aula-alumnos');
        "
    >
        <div class="flex flex-col gap-4">
            <div>
                <h3 class="text-lg font-semibold text-slate-800">Alumnos matriculados</h3>
                <p class="mt-1 text-sm text-slate-500" x-text="etiqueta">{{ $aulaModal['etiqueta'] ?? '' }}</p>
            </div>

            <div class="flex max-h-96 flex-col gap-1 overflow-y-auto">
                <template x-for="alumno in matriculados" :key="alumno.matriculaId">
                    <div class="flex items-center justify-between gap-3 rounded-lg border border-slate-200 bg-slate-50 px-3 py-2">
                        <div>
                            <p class="text-sm text-slate-800" x-text="alumno.nombre"></p>
                            <p class="text-xs text-slate-500" x-text="alumno.numero" x-show="alumno.numero"></p>
                        </div>
                        <form method="POST" x-bind:action="alumno.quitar">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-xs font-semibold text-red-700 hover:text-red-800">Quitar</button>
                        </form>
                    </div>
                </template>
                <p class="text-sm text-slate-500" x-show="matriculados.length === 0">Este paralelo todavía no tiene alumnos.</p>
            </div>

            <div class="flex justify-end">
                <x-secondary-button type="button" x-on:click="$dispatch('close')">Cerrar</x-secondary-button>
            </div>
        </div>
    </div>
</x-modal>
