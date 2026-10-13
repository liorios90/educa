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

            @if (session('status') === 'periodo-aperturado')
                <p class="mb-4 text-sm font-medium text-green-700">El periodo se aperturó. La configuración quedó bloqueada y se creó un cierre por cada parcial.</p>
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
                        más el examen y/o el proyecto también deben sumar 100%. Estas ponderaciones porcentuales aplican solo
                        a los grados, cursos y asignaturas que se califican cuantitativamente. Si la evaluación es cualitativa,
                        se registra con la escala correspondiente y no se configura con porcentajes.
                    </p>
                    <p class="mt-2 text-sm font-medium text-slate-800">
                        {{ $oferta->etiqueta() }}
                        · {{ $periodo->nombre }}
                    </p>
                </section>

                @if ($periodo->estaAbierto())
                    <div class="mb-6 rounded-xl border-2 border-amber-600 bg-amber-50 px-5 py-4" role="alert">
                        <p class="text-base font-semibold text-amber-950">Este periodo ya fue aperturado</p>
                        <p class="mt-1 text-sm leading-6 text-amber-950">
                            La configuración quedó bloqueada. Puedes consultarla, pero no modificar la forma de trabajo, los ciclos ni la ponderación final.
                        </p>
                    </div>
                @endif

                <x-input-error class="mb-4" :messages="$errors->get('periodo')" />

                <form
                    method="POST"
                    action="{{ route('Admin.periodo.update') }}"
                    class="flex flex-col gap-6"
                    x-data="{
                        pestana: @js(collect($errors->keys())->contains(fn (string $campo): bool => $campo === 'ciclos' || (str_starts_with($campo, 'ciclos.') && ! str_ends_with($campo, '.porcentaje'))) ? 'ciclos' : 'periodo'),
                        esquema: @js($esquemaSeleccionado),
                        plantillas: @js($plantillas),
                        nombresPorDefecto: @js($nombresPorDefecto),
                        nombreEsquema: @js($nombreEsquema),
                        numeroCiclos: @js($numeroCiclos),
                        ciclos: @js($ciclosFormulario),
                        tieneExamenFinal: @js((bool) $tieneExamenFinal),
                        porcentajeExamenFinal: @js((string) $porcentajeExamenFinal),
                        tieneProyectoFinal: @js((bool) $tieneProyectoFinal),
                        porcentajeProyectoFinal: @js((string) $porcentajeProyectoFinal),
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
                        alCambiarNumeroCiclos(evento) {
                            const cantidad = Number.parseInt(evento.target.value, 10)

                            if (! Number.isFinite(cantidad)) {
                                this.numeroCiclos = this.ciclos.length

                                return
                            }

                            const tope = Math.min(6, Math.max(1, cantidad))
                            const esquemaNuevo = tope === 2 ? 'quimestres' : (tope === 3 ? 'trimestres' : 'otro')

                            if (tope === this.ciclos.length && esquemaNuevo === this.esquema) {
                                this.numeroCiclos = tope

                                return
                            }

                            const esquemaAnterior = this.esquema
                            this.esquema = esquemaNuevo

                            if (esquemaNuevo === 'quimestres' || esquemaNuevo === 'trimestres') {
                                this.nombreEsquema = this.nombresPorDefecto[esquemaNuevo] ?? ''
                                this.ciclos = JSON.parse(JSON.stringify(this.plantillas[esquemaNuevo]))
                                this.numeroCiclos = this.ciclos.length

                                return
                            }

                            if (['Quimestre', 'Trimestre', 'Quimestres', 'Trimestres'].includes(this.nombreEsquema)) {
                                this.nombreEsquema = ''
                            }

                            if (esquemaAnterior !== 'otro') {
                                this.ciclos = []
                            }

                            while (this.ciclos.length < tope) {
                                this.ciclos.push(this.cicloVacio())
                            }

                            if (this.ciclos.length > tope) {
                                this.ciclos.splice(tope)
                            }

                            this.numeroCiclos = this.ciclos.length
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
                            const partes = this.ciclos.map((ciclo, indice) => (ciclo.nombre || ('Ciclo ' + (indice + 1))) + ' ' + this.numero(ciclo.porcentaje).toFixed(2) + '%')

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

                    <input type="hidden" name="esquema_ciclo" :value="esquema" @disabled($periodo->estaAbierto())>
                    <input type="hidden" name="tiene_examen_final" :value="tieneExamenFinal ? 1 : 0" @disabled($periodo->estaAbierto())>
                    <input type="hidden" name="tiene_proyecto_final" :value="tieneProyectoFinal ? 1 : 0" @disabled($periodo->estaAbierto())>

                    <div>
                        <style>
                            .estructura-folders {
                                display: flex;
                                align-items: flex-end;
                                gap: 0.25rem;
                                overflow-x: auto;
                                overflow-y: hidden;
                                padding: 0 0.5rem;
                            }

                            .estructura-folder {
                                position: relative;
                                display: flex;
                                flex-shrink: 0;
                                align-items: center;
                                gap: 0.5rem;
                                margin-bottom: -1px;
                                border: 1px solid #e2e8f0;
                                border-bottom: 0;
                                border-radius: 0.75rem 0.75rem 0 0;
                                background: #f1f5f9;
                                padding: 0.5rem 1rem 0.625rem;
                                font-size: 0.875rem;
                                font-weight: 600;
                                color: #64748b;
                                clip-path: polygon(10px 0, calc(100% - 10px) 0, 100% 100%, 0 100%);
                                transition: background-color 150ms ease, color 150ms ease, box-shadow 150ms ease;
                            }

                            .estructura-folder:hover {
                                background: #e2e8f0;
                                color: #334155;
                            }

                            .estructura-folder.is-filled {
                                background: #e2e8f0;
                                border-color: #cbd5e1;
                                color: #1e293b;
                            }

                            .estructura-folder.is-active {
                                z-index: 1;
                                background: #fff;
                                border-color: #cbd5e1;
                                color: #0f172a;
                                padding-top: 0.75rem;
                                padding-bottom: 0.75rem;
                                box-shadow: 0 -6px 14px rgb(15 23 42 / 0.08);
                            }

                            .estructura-folder.is-active::after {
                                content: "";
                                position: absolute;
                                right: 0;
                                bottom: -1px;
                                left: 0;
                                height: 2px;
                                background: #fff;
                            }

                            .estructura-folder:focus {
                                outline: none;
                            }

                            .estructura-folder:focus-visible {
                                box-shadow: 0 0 0 2px #0f172a;
                            }

                            .estructura-folder-count {
                                border-radius: 9999px;
                                background: #fff;
                                padding: 0.125rem 0.5rem;
                                font-size: 0.75rem;
                                font-weight: 500;
                                color: #64748b;
                                box-shadow: inset 0 0 0 1px #e2e8f0;
                            }

                            .estructura-folder.is-filled .estructura-folder-count {
                                background: #0f172a;
                                color: #fff;
                                box-shadow: none;
                            }

                            .estructura-folder.is-active.is-filled .estructura-folder-count {
                                background: #c9a227;
                                color: #fff;
                                box-shadow: none;
                            }

                            .estructura-folder-panel {
                                overflow: hidden;
                                border: 1px solid #cbd5e1;
                                border-radius: 1rem;
                                background: #fff;
                                box-shadow: 0 1px 2px rgb(15 23 42 / 0.05);
                            }

                            .estructura-nivel-title {
                                margin: 0;
                                color: navy;
                                font-size: 1.125rem;
                                font-weight: 700;
                                line-height: 1.4;
                                text-align: center;
                            }

                            .estructura-subniveles {
                                display: flex;
                                flex-direction: column;
                                gap: 0.75rem;
                                padding: 0.75rem;
                                background: #f1f5f9;
                            }

                            .estructura-subnivel {
                                border: 1px solid #cbd5e1;
                                border-radius: 0.75rem;
                                background: #fff;
                                padding: 1rem 1.25rem;
                            }
                        </style>

                        <nav class="estructura-folders" role="tablist" aria-label="Configuración del periodo">
                            <button
                                type="button"
                                role="tab"
                                class="estructura-folder"
                                :class="{ 'is-active': pestana === 'periodo' }"
                                :aria-selected="pestana === 'periodo'"
                                @click="pestana = 'periodo'"
                            >
                                <svg class="h-4 w-4 shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12.75V12A2.25 2.25 0 0 1 4.5 9.75h15A2.25 2.25 0 0 1 21.75 12v.75m-8.69-6.44-2.12-2.12a1.5 1.5 0 0 0-1.061-.44H4.5A2.25 2.25 0 0 0 2.25 6v12a2.25 2.25 0 0 0 2.25 2.25h15A2.25 2.25 0 0 0 21.75 18V9a2.25 2.25 0 0 0-2.25-2.25h-5.379a1.5 1.5 0 0 1-1.06-.44Z" />
                                </svg>
                                <span>Configuración de periodo</span>
                            </button>
                            <button
                                type="button"
                                role="tab"
                                class="estructura-folder"
                                :class="{ 'is-active': pestana === 'ciclos', 'is-filled': ciclos.length > 0 }"
                                :aria-selected="pestana === 'ciclos'"
                                @click="pestana = 'ciclos'"
                            >
                                <svg class="h-4 w-4 shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12.75V12A2.25 2.25 0 0 1 4.5 9.75h15A2.25 2.25 0 0 1 21.75 12v.75m-8.69-6.44-2.12-2.12a1.5 1.5 0 0 0-1.061-.44H4.5A2.25 2.25 0 0 0 2.25 6v12a2.25 2.25 0 0 0 2.25 2.25h15A2.25 2.25 0 0 0 21.75 18V9a2.25 2.25 0 0 0-2.25-2.25h-5.379a1.5 1.5 0 0 1-1.06-.44Z" />
                                </svg>
                                <span>Configuración de ciclos</span>
                                <span class="estructura-folder-count" x-text="ciclos.length + (ciclos.length === 1 ? ' ciclo' : ' ciclos')"></span>
                            </button>
                        </nav>

                        <div class="estructura-folder-panel">
                        <fieldset class="min-w-0 w-full border-0 p-0" @disabled($periodo->estaAbierto())>
                    <div role="tabpanel" x-show="pestana === 'periodo'">
                        <header class="border-b border-slate-100 px-5 py-4">
                            <h3 class="estructura-nivel-title">Configuración de periodo</h3>
                        </header>
                        <div class="estructura-subniveles">
                    <section class="estructura-subnivel">
                        <h3 class="text-base font-semibold text-slate-800">Esquema de trabajo</h3>
                        <div class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-2">
                            <div>
                                <x-input-label for="numero-ciclos" value="Número de ciclos" />
                                <x-text-input
                                    id="numero-ciclos"
                                    class="mt-1 block w-full"
                                    type="number"
                                    min="1"
                                    max="6"
                                    x-model.number="numeroCiclos"
                                    @change="alCambiarNumeroCiclos($event)"
                                />
                                <p class="mt-2 text-sm text-slate-500">Con 2 el nombre es Quimestre y con 3 es Trimestre; puedes cambiarlo. Con otro número, escribe el nombre que prefieras.</p>
                                <x-input-error class="mt-2" :messages="$errors->get('esquema_ciclo')" />
                            </div>
                            <div>
                                <x-input-label for="nombre-esquema" value="Nombre" />
                                <x-text-input
                                    id="nombre-esquema"
                                    class="mt-1 block w-full"
                                    type="text"
                                    name="nombre_esquema"
                                    x-model="nombreEsquema"
                                    maxlength="255"
                                    required
                                    placeholder="Nombre de la forma de trabajo"
                                />
                                <x-input-error class="mt-2" :messages="$errors->get('nombre_esquema')" />
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
                    </section>

                    <section class="estructura-subnivel">
                        <h3 class="text-base font-semibold text-slate-800">Ponderación de los ciclos</h3>
                        <p class="mt-1 text-sm text-slate-500">
                            Para grados, cursos y asignaturas con calificación cuantitativa, este es el peso de cada ciclo en el periodo.
                            Junto con el examen final y el proyecto final debe sumar 100%. No aplica a evaluaciones cualitativas.
                        </p>
                        <div class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-2">
                            <template x-for="(ciclo, indice) in ciclos" :key="'peso-' + indice">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700" :for="'ciclo-porcentaje-' + indice">
                                        <span x-text="ciclo.nombre || ('Ciclo ' + (indice + 1))"></span>
                                    </label>
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
                            </template>
                        </div>
                        @foreach ($errors->getMessages() as $campo => $mensajes)
                            @if (str_ends_with($campo, '.porcentaje'))
                                <x-input-error class="mt-2" :messages="$mensajes" />
                            @endif
                        @endforeach
                    </section>

                    <section class="estructura-subnivel">
                        <h3 class="text-base font-semibold text-slate-800">Ponderación final</h3>
                        <p class="mt-1 text-sm text-slate-500">
                            Para grados, cursos y asignaturas con calificación cuantitativa, puedes marcar examen, proyecto, ambos o ninguno.
                            Junto con los ciclos deben sumar 100%. No aplica a evaluaciones cualitativas.
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
                        <p class="mt-4 text-sm" :class="sumaPeriodoOk() ? 'text-slate-600' : 'font-medium text-red-600'">
                            Suma de los ciclos, el examen final y el proyecto final:
                            <span x-text="desglosePeriodo()"></span>
                        </p>
                        <x-input-error class="mt-2" :messages="$errors->get('totales')" />
                    </section>
                        </div>
                    </div>

                    <div role="tabpanel" x-show="pestana === 'ciclos'" x-cloak>
                        <header class="border-b border-slate-100 px-5 py-4">
                            <h3 class="estructura-nivel-title">Configuración de ciclos</h3>
                        </header>
                        <div class="estructura-subniveles">
                    <x-input-error :messages="$errors->get('ciclos')" />
                    @foreach ($errors->getMessages() as $campo => $mensajes)
                        @if (str_starts_with($campo, 'ciclos.') && ! str_ends_with($campo, '.porcentaje'))
                            <x-input-error :messages="$mensajes" />
                        @endif
                    @endforeach
                    <div class="flex flex-col gap-4">
                        <template x-for="(ciclo, indice) in ciclos" :key="indice">
                            <section class="estructura-subnivel">
                                <h3 class="text-base font-semibold text-slate-800" x-text="'Ciclo ' + (indice + 1)"></h3>
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
                        </div>
                    </div>

                        </fieldset>
                        </div>
                    </div>

                    <div class="flex flex-wrap items-center justify-between gap-4">
                        <div class="flex flex-wrap items-center gap-4">
                            <p class="text-sm" :class="sumaPeriodoOk() ? 'text-slate-600' : 'font-medium text-red-600'">
                                Suma del periodo:
                                <span x-text="desglosePeriodo()"></span>
                            </p>
                        </div>
                        @unless ($periodo->estaAbierto())
                            <x-primary-button>Guardar configuración</x-primary-button>
                        @endunless
                    </div>
                </form>

                @unless ($periodo->estaAbierto())
                    <section class="mt-6 rounded-2xl border-2 border-amber-500 bg-amber-50 p-6">
                        <h3 class="text-base font-semibold text-amber-950">Aperturar periodo</h3>
                        <p class="mt-2 text-sm leading-6 text-amber-950">
                            Al aperturar, esta configuración se bloquea y se crea un cierre por cada parcial de cada ciclo. Después solo se puede consultar. Esa acción no se puede deshacer.
                        </p>
                        @if ($periodo->ciclos->isNotEmpty() && $periodo->numero_parciales !== null)
                            <form method="POST" action="{{ route('Admin.periodo.aperturar') }}" class="mt-4" onsubmit="return confirm('Al aperturar, la configuración se bloquea y no se puede modificar. ¿Continuar?');">
                                @csrf
                                <x-danger-button>Aperturar periodo</x-danger-button>
                            </form>
                        @else
                            <p class="mt-3 text-sm font-medium text-amber-950">Guarda la configuración antes de aperturar el periodo.</p>
                        @endif
                    </section>
                @endunless
            @endif
        </div>
    </div>
</x-app-layout>
