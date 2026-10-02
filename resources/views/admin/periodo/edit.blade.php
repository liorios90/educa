<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="text-xl font-semibold leading-tight text-slate-800">
                Periodo
            </h2>
            <p class="mt-1 text-sm text-slate-500">
                {{ $establecimiento->nombre }}
            </p>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">
            @if (session('status') === 'periodo-evaluacion-updated')
                <p class="mb-4 text-sm font-medium text-green-700">La configuración del periodo se guardó correctamente.</p>
            @endif

            @if ($oferta === null)
                <p class="rounded-2xl border border-slate-200 bg-white px-6 py-8 text-center text-slate-500">
                    Sistemas aún no ha asignado modalidades y jornadas a este establecimiento.
                </p>
            @elseif ($periodo === null)
                <p class="rounded-2xl border border-slate-200 bg-white px-6 py-8 text-center text-slate-500">
                    No existe ningún periodo activo.
                </p>
            @else
                <section class="mb-6 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                    <p class="text-sm text-slate-600">
                        Configura cómo se evalúa este periodo según la legislación: quimestres, trimestres u otro esquema.
                        Los ciclos más el examen final y/o el proyecto final deben sumar 100%. Por ejemplo, en quimestres:
                        primer ciclo 30%, segundo ciclo 30%, examen final 20% y proyecto final 20%. En cada ciclo, los insumos
                        más el examen y/o el proyecto también deben sumar 100%.
                    </p>
                    <p class="mt-2 text-sm font-medium text-slate-800">
                        {{ $oferta->etiqueta() }}
                        · {{ $periodo->nombre }}
                    </p>
                </section>

                <form
                    method="POST"
                    action="{{ route('Admin.periodo.update') }}"
                    class="flex flex-col gap-6"
                    x-data="{
                        esquema: @js($esquemaSeleccionado),
                        plantillas: @js($plantillas),
                        ciclos: @js($ciclosFormulario),
                        tieneExamenFinal: @js((bool) $tieneExamenFinal),
                        porcentajeExamenFinal: @js((string) $porcentajeExamenFinal),
                        tieneProyectoFinal: @js((bool) $tieneProyectoFinal),
                        porcentajeProyectoFinal: @js((string) $porcentajeProyectoFinal),
                        aplicarEsquema(tipo) {
                            this.esquema = tipo
                            this.ciclos = JSON.parse(JSON.stringify(this.plantillas[tipo] ?? this.plantillas.otro))
                        },
                        cicloVacio() {
                            return {
                                nombre: 'Ciclo ' + (this.ciclos.length + 1),
                                porcentaje: '0.00',
                                porcentaje_insumos: '70.00',
                                tiene_examen: true,
                                porcentaje_examen: '30.00',
                                tiene_proyecto: false,
                                porcentaje_proyecto: '0.00',
                            }
                        },
                        agregarCiclo() {
                            if (this.esquema !== 'otro' || this.ciclos.length >= 6) {
                                return
                            }

                            this.ciclos.push(this.cicloVacio())
                        },
                        quitarCiclo(indice) {
                            if (this.esquema !== 'otro' || this.ciclos.length <= 1) {
                                return
                            }

                            this.ciclos.splice(indice, 1)
                        },
                        alCambiarExamen(ciclo) {
                            if (! ciclo.tiene_examen) {
                                ciclo.porcentaje_examen = '0.00'
                                return
                            }

                            if (Number(ciclo.porcentaje_examen || 0) === 0) {
                                ciclo.porcentaje_examen = '30.00'
                            }
                        },
                        alCambiarProyecto(ciclo) {
                            if (! ciclo.tiene_proyecto) {
                                ciclo.porcentaje_proyecto = '0.00'
                                return
                            }

                            if (Number(ciclo.porcentaje_proyecto || 0) === 0) {
                                ciclo.porcentaje_proyecto = '20.00'
                            }
                        },
                        alCambiarExamenFinal() {
                            if (! this.tieneExamenFinal) {
                                this.porcentajeExamenFinal = '0.00'
                                return
                            }

                            if (Number(this.porcentajeExamenFinal || 0) === 0) {
                                this.porcentajeExamenFinal = '20.00'
                            }
                        },
                        alCambiarProyectoFinal() {
                            if (! this.tieneProyectoFinal) {
                                this.porcentajeProyectoFinal = '0.00'
                                return
                            }

                            if (Number(this.porcentajeProyectoFinal || 0) === 0) {
                                this.porcentajeProyectoFinal = '20.00'
                            }
                        },
                        numero(valor) {
                            return Number(valor || 0)
                        },
                        sumaCiclos() {
                            return this.ciclos.reduce((total, ciclo) => total + this.numero(ciclo.porcentaje), 0)
                        },
                        sumaCierrePeriodo() {
                            return this.numero(this.tieneExamenFinal ? this.porcentajeExamenFinal : 0)
                                + this.numero(this.tieneProyectoFinal ? this.porcentajeProyectoFinal : 0)
                        },
                        sumaPeriodo() {
                            return this.sumaCiclos() + this.sumaCierrePeriodo()
                        },
                        sumaPeriodoOk() {
                            return Math.abs(this.sumaPeriodo() - 100) < 0.01
                        },
                        desglosePeriodo() {
                            const partes = this.ciclos.map((ciclo) => this.numero(ciclo.porcentaje).toFixed(2) + '%')

                            if (this.tieneExamenFinal) {
                                partes.push(this.numero(this.porcentajeExamenFinal).toFixed(2) + '% examen')
                            }

                            if (this.tieneProyectoFinal) {
                                partes.push(this.numero(this.porcentajeProyectoFinal).toFixed(2) + '% proyecto')
                            }

                            return partes.join(' + ') + ' = ' + this.sumaPeriodo().toFixed(2) + '%'
                        },
                        sumaCiclo(ciclo) {
                            return this.numero(ciclo.porcentaje_insumos)
                                + this.numero(ciclo.tiene_examen ? ciclo.porcentaje_examen : 0)
                                + this.numero(ciclo.tiene_proyecto ? ciclo.porcentaje_proyecto : 0)
                        },
                        sumaCicloOk(ciclo) {
                            return Math.abs(this.sumaCiclo(ciclo) - 100) < 0.01
                        },
                    }"
                >
                    @csrf
                    @method('PUT')

                    <input type="hidden" name="esquema_ciclo" :value="esquema">
                    <input type="hidden" name="tiene_examen_final" :value="tieneExamenFinal ? 1 : 0">
                    <input type="hidden" name="tiene_proyecto_final" :value="tieneProyectoFinal ? 1 : 0">

                    <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                        <h3 class="text-base font-semibold text-slate-800">Esquema de trabajo</h3>
                        <div class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-2">
                            <div>
                                <x-input-label for="esquema-ciclo" value="Forma de trabajo" />
                                <select
                                    id="esquema-ciclo"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                    x-model="esquema"
                                    @change="aplicarEsquema(esquema)"
                                >
                                    @foreach (\App\Enums\EsquemaCiclo::cases() as $opcionEsquema)
                                        <option value="{{ $opcionEsquema->value }}">{{ $opcionEsquema->label() }}</option>
                                    @endforeach
                                </select>
                                <x-input-error class="mt-2" :messages="$errors->get('esquema_ciclo')" />
                            </div>
                            <div>
                                <x-input-label for="numero-parciales" value="Parciales por ciclo" />
                                <x-text-input
                                    id="numero-parciales"
                                    class="mt-1 block w-full"
                                    type="number"
                                    name="numero_parciales"
                                    min="1"
                                    max="6"
                                    :value="$parcialesSeleccionados"
                                    required
                                />
                                <x-input-error class="mt-2" :messages="$errors->get('numero_parciales')" />
                            </div>
                        </div>
                        <x-input-error class="mt-4" :messages="$errors->get('ciclos')" />
                        <x-input-error class="mt-2" :messages="$errors->get('totales')" />
                        @foreach ($errors->getMessages() as $campo => $mensajes)
                            @if (str_starts_with($campo, 'ciclos.'))
                                <x-input-error class="mt-2" :messages="$mensajes" />
                            @endif
                        @endforeach
                    </section>

                    <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                        <h3 class="text-base font-semibold text-slate-800">Cierre del periodo</h3>
                        <p class="mt-1 text-sm text-slate-500">
                            Puedes marcar examen, proyecto, ambos o ninguno. Junto con los ciclos deben sumar 100%.
                        </p>
                        <div class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-2">
                            <div class="rounded-xl border border-slate-200 p-4">
                                <label class="flex items-center gap-2 text-sm font-medium text-slate-700">
                                    <input
                                        type="checkbox"
                                        class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500"
                                        x-model="tieneExamenFinal"
                                        @change="alCambiarExamenFinal()"
                                    >
                                    Examen final
                                </label>
                                <div class="mt-3" x-show="tieneExamenFinal" x-cloak>
                                    <label class="block text-sm font-medium text-gray-700" for="porcentaje-examen-final">Porcentaje del periodo</label>
                                    <input
                                        id="porcentaje-examen-final"
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                        type="number"
                                        min="0"
                                        max="100"
                                        step="0.01"
                                        name="porcentaje_examen_final"
                                        x-model="porcentajeExamenFinal"
                                        x-bind:disabled="! tieneExamenFinal"
                                    >
                                </div>
                                <input type="hidden" name="porcentaje_examen_final" :value="'0.00'" x-bind:disabled="tieneExamenFinal">
                                <x-input-error class="mt-2" :messages="$errors->get('porcentaje_examen_final')" />
                            </div>
                            <div class="rounded-xl border border-slate-200 p-4">
                                <label class="flex items-center gap-2 text-sm font-medium text-slate-700">
                                    <input
                                        type="checkbox"
                                        class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500"
                                        x-model="tieneProyectoFinal"
                                        @change="alCambiarProyectoFinal()"
                                    >
                                    Proyecto final
                                </label>
                                <div class="mt-3" x-show="tieneProyectoFinal" x-cloak>
                                    <label class="block text-sm font-medium text-gray-700" for="porcentaje-proyecto-final">Porcentaje del periodo</label>
                                    <input
                                        id="porcentaje-proyecto-final"
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                        type="number"
                                        min="0"
                                        max="100"
                                        step="0.01"
                                        name="porcentaje_proyecto_final"
                                        x-model="porcentajeProyectoFinal"
                                        x-bind:disabled="! tieneProyectoFinal"
                                    >
                                </div>
                                <input type="hidden" name="porcentaje_proyecto_final" :value="'0.00'" x-bind:disabled="tieneProyectoFinal">
                                <x-input-error class="mt-2" :messages="$errors->get('porcentaje_proyecto_final')" />
                            </div>
                        </div>
                    </section>

                    <div class="flex flex-col gap-4">
                        <template x-for="(ciclo, indice) in ciclos" :key="indice">
                            <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                                <div class="flex flex-wrap items-center justify-between gap-3">
                                    <h3 class="text-base font-semibold text-slate-800" x-text="'Ciclo ' + (indice + 1)"></h3>
                                    <button
                                        type="button"
                                        class="text-sm font-medium text-red-600 hover:text-red-500"
                                        x-show="esquema === 'otro' && ciclos.length > 1"
                                        @click="quitarCiclo(indice)"
                                    >
                                        Quitar
                                    </button>
                                </div>
                                <input type="hidden" :name="'ciclos[' + indice + '][tiene_examen]'" :value="ciclo.tiene_examen ? 1 : 0">
                                <input type="hidden" :name="'ciclos[' + indice + '][tiene_proyecto]'" :value="ciclo.tiene_proyecto ? 1 : 0">
                                <div class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-2">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700" :for="'ciclo-nombre-' + indice">Nombre</label>
                                        <input
                                            :id="'ciclo-nombre-' + indice"
                                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                            type="text"
                                            :name="'ciclos[' + indice + '][nombre]'"
                                            x-model="ciclo.nombre"
                                            required
                                            maxlength="255"
                                        >
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700" :for="'ciclo-porcentaje-' + indice">Porcentaje del periodo</label>
                                        <input
                                            :id="'ciclo-porcentaje-' + indice"
                                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                            type="number"
                                            min="0"
                                            max="100"
                                            step="0.01"
                                            :name="'ciclos[' + indice + '][porcentaje]'"
                                            x-model="ciclo.porcentaje"
                                            required
                                        >
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700" :for="'ciclo-insumos-' + indice">Porcentaje de insumos</label>
                                        <input
                                            :id="'ciclo-insumos-' + indice"
                                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                            type="number"
                                            min="0"
                                            max="100"
                                            step="0.01"
                                            :name="'ciclos[' + indice + '][porcentaje_insumos]'"
                                            x-model="ciclo.porcentaje_insumos"
                                            required
                                        >
                                    </div>
                                    <div class="rounded-xl border border-slate-200 p-4">
                                        <label class="flex items-center gap-2 text-sm font-medium text-slate-700">
                                            <input
                                                type="checkbox"
                                                class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500"
                                                x-model="ciclo.tiene_examen"
                                                @change="alCambiarExamen(ciclo)"
                                            >
                                            Examen
                                        </label>
                                        <div class="mt-3" x-show="ciclo.tiene_examen" x-cloak>
                                            <label class="block text-sm font-medium text-gray-700" :for="'ciclo-examen-' + indice">Porcentaje del ciclo</label>
                                            <input
                                                :id="'ciclo-examen-' + indice"
                                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                                type="number"
                                                min="0"
                                                max="100"
                                                step="0.01"
                                                :name="ciclo.tiene_examen ? ('ciclos[' + indice + '][porcentaje_examen]') : ''"
                                                x-model="ciclo.porcentaje_examen"
                                            >
                                        </div>
                                        <input
                                            type="hidden"
                                            :name="ciclo.tiene_examen ? '' : ('ciclos[' + indice + '][porcentaje_examen]')"
                                            value="0"
                                            x-show="! ciclo.tiene_examen"
                                        >
                                    </div>
                                    <div class="rounded-xl border border-slate-200 p-4">
                                        <label class="flex items-center gap-2 text-sm font-medium text-slate-700">
                                            <input
                                                type="checkbox"
                                                class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500"
                                                x-model="ciclo.tiene_proyecto"
                                                @change="alCambiarProyecto(ciclo)"
                                            >
                                            Proyecto
                                        </label>
                                        <div class="mt-3" x-show="ciclo.tiene_proyecto" x-cloak>
                                            <label class="block text-sm font-medium text-gray-700" :for="'ciclo-proyecto-' + indice">Porcentaje del ciclo</label>
                                            <input
                                                :id="'ciclo-proyecto-' + indice"
                                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                                type="number"
                                                min="0"
                                                max="100"
                                                step="0.01"
                                                :name="ciclo.tiene_proyecto ? ('ciclos[' + indice + '][porcentaje_proyecto]') : ''"
                                                x-model="ciclo.porcentaje_proyecto"
                                            >
                                        </div>
                                        <input
                                            type="hidden"
                                            :name="ciclo.tiene_proyecto ? '' : ('ciclos[' + indice + '][porcentaje_proyecto]')"
                                            value="0"
                                            x-show="! ciclo.tiene_proyecto"
                                        >
                                    </div>
                                </div>
                                <p class="mt-4 text-sm" :class="sumaCicloOk(ciclo) ? 'text-slate-600' : 'font-medium text-red-600'">
                                    Suma del ciclo:
                                    <span x-text="sumaCiclo(ciclo).toFixed(2) + '%'"></span>
                                </p>
                            </section>
                        </template>
                    </div>

                    <div class="flex flex-wrap items-center justify-between gap-4">
                        <div class="flex flex-wrap items-center gap-4">
                            <p class="text-sm" :class="sumaPeriodoOk() ? 'text-slate-600' : 'font-medium text-red-600'">
                                Suma del periodo:
                                <span x-text="desglosePeriodo()"></span>
                            </p>
                            <button
                                type="button"
                                class="text-sm font-medium text-indigo-600 hover:text-indigo-500"
                                x-show="esquema === 'otro' && ciclos.length < 6"
                                @click="agregarCiclo()"
                            >
                                Agregar ciclo
                            </button>
                        </div>
                        <x-primary-button>Guardar configuración</x-primary-button>
                    </div>
                </form>
            @endif
        </div>
    </div>
</x-app-layout>
