<x-modal name="aula-asignaturas" maxWidth="4xl" focusable>
    <div
        class="p-6"
        x-data="{ etiqueta: '', aulaId: '', action: '', puedeConfigurar: true, asignaturas: [] }"
        x-on:aula-asignaturas.window="
            etiqueta = $event.detail.etiqueta;
            aulaId = $event.detail.aulaId ?? '';
            action = $event.detail.action ?? '';
            puedeConfigurar = $event.detail.puedeConfigurar ?? true;
            asignaturas = ($event.detail.asignaturas ?? []).map((asignatura) => ({
                ...asignatura,
                ciclos: (asignatura.ciclos ?? []).map((ciclo) => ({ ...ciclo })),
            }));
            $dispatch('open-modal', 'aula-asignaturas');
        "
    >
        <form method="POST" x-bind:action="action" action="">
            @csrf
            @method('PUT')

            <h3 class="text-lg font-semibold text-slate-800">Configuración por asignaturas</h3>
            <p class="mt-1 text-sm text-slate-500" x-text="etiqueta"></p>

            <p class="mt-3 rounded-lg bg-indigo-50 p-3 text-sm text-indigo-800" x-show="!aulaId">
                Crea el aula de este grado para habilitar la configuración individual de sus asignaturas por ciclos.
            </p>
            <p class="mt-3 rounded-lg bg-amber-50 p-3 text-sm text-amber-800" x-show="aulaId && !puedeConfigurar">
                El periodo ya fue aperturado. La configuración por ciclos está bloqueada.
            </p>

            <div class="mt-5 max-h-[65vh] space-y-4 overflow-y-auto pr-1" x-show="asignaturas.length > 0">
                <template x-for="asignatura in asignaturas" :key="asignatura.id">
                    <section class="rounded-xl border border-slate-200 bg-white p-4">
                        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                            <h4 class="font-semibold text-slate-800" x-text="asignatura.nombre"></h4>
                            <label class="inline-flex items-center gap-2 text-sm text-slate-600" x-show="aulaId && puedeConfigurar">
                                <input
                                    type="checkbox"
                                    class="rounded border-slate-300 text-indigo-600 shadow-sm focus:ring-indigo-500"
                                    x-model="asignatura.personalizada"
                                    :disabled="asignatura.ciclos.length === 0"
                                >
                                Personalizar ciclos
                            </label>
                        </div>

                        <input
                            type="hidden"
                            :name="'configuraciones[' + asignatura.id + '][personalizada]'"
                            :value="asignatura.personalizada ? '1' : '0'"
                            :disabled="!aulaId || !puedeConfigurar"
                        >

                        <div class="mt-4 space-y-3" x-show="aulaId && puedeConfigurar && asignatura.personalizada" x-cloak>
                            <template x-for="ciclo in asignatura.ciclos" :key="ciclo.orden">
                                <div class="rounded-lg border border-slate-200 bg-slate-50 p-3">
                                    <h5 class="mb-3 text-sm font-semibold text-slate-700" x-text="ciclo.nombre"></h5>
                                    <div class="grid grid-cols-1 gap-3 md:grid-cols-3">
                                        <div>
                                            <label class="block text-xs text-slate-500" :for="'insumos-' + asignatura.id + '-' + ciclo.orden">Porcentaje de insumos</label>
                                            <input
                                                type="number" min="0" max="100" step="0.01"
                                                class="mt-1 block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                                :id="'insumos-' + asignatura.id + '-' + ciclo.orden"
                                                :name="'configuraciones[' + asignatura.id + '][ciclos][' + ciclo.orden + '][porcentaje_insumos]'"
                                                x-model="ciclo.porcentaje_insumos"
                                                :disabled="!asignatura.personalizada"
                                                required
                                            >
                                        </div>
                                        <div>
                                            <label class="block text-xs text-slate-500" :for="'examen-' + asignatura.id + '-' + ciclo.orden">Porcentaje de examen</label>
                                            <input
                                                type="number" min="0" max="100" step="0.01"
                                                class="mt-1 block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                                :id="'examen-' + asignatura.id + '-' + ciclo.orden"
                                                :name="'configuraciones[' + asignatura.id + '][ciclos][' + ciclo.orden + '][porcentaje_examen]'"
                                                x-model="ciclo.porcentaje_examen"
                                                :disabled="!asignatura.personalizada || !ciclo.tiene_examen"
                                                required
                                            >
                                            <input
                                                type="hidden"
                                                :name="'configuraciones[' + asignatura.id + '][ciclos][' + ciclo.orden + '][porcentaje_examen]'"
                                                value="0"
                                                :disabled="!asignatura.personalizada || ciclo.tiene_examen"
                                            >
                                        </div>
                                        <div>
                                            <label class="block text-xs text-slate-500" :for="'proyecto-' + asignatura.id + '-' + ciclo.orden">Porcentaje de proyecto</label>
                                            <input
                                                type="number" min="0" max="100" step="0.01"
                                                class="mt-1 block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                                :id="'proyecto-' + asignatura.id + '-' + ciclo.orden"
                                                :name="'configuraciones[' + asignatura.id + '][ciclos][' + ciclo.orden + '][porcentaje_proyecto]'"
                                                x-model="ciclo.porcentaje_proyecto"
                                                :disabled="!asignatura.personalizada || !ciclo.tiene_proyecto"
                                                required
                                            >
                                            <input
                                                type="hidden"
                                                :name="'configuraciones[' + asignatura.id + '][ciclos][' + ciclo.orden + '][porcentaje_proyecto]'"
                                                value="0"
                                                :disabled="!asignatura.personalizada || ciclo.tiene_proyecto"
                                            >
                                        </div>
                                    </div>
                                    <p class="mt-2 text-xs text-slate-500">Los componentes habilitados deben sumar 100%.</p>
                                </div>
                            </template>
                            <p class="text-sm text-amber-700" x-show="asignatura.ciclos.length === 0">
                                Define primero los ciclos del periodo para configurar esta asignatura.
                            </p>
                        </div>
                    </section>
                </template>
            </div>

            <p class="mt-5 text-sm text-slate-500" x-show="asignaturas.length === 0">
                Esta aula no tiene asignaturas con calificación cuantitativa.
            </p>

            <div class="mt-6 flex justify-end gap-2">
                <x-secondary-button type="button" x-on:click="$dispatch('close')">Cancelar</x-secondary-button>
                <x-primary-button x-show="aulaId && puedeConfigurar && asignaturas.length > 0">Guardar configuración</x-primary-button>
            </div>
        </form>
    </div>
</x-modal>
