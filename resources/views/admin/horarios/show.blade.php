@php
    $horarioBag = $errors->getBag('horario');
@endphp

<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-xl font-semibold leading-tight text-slate-800">Horario de clases</h2>
                <p class="mt-1 text-sm text-slate-500">Aula: {{ $aula->etiqueta() }}</p>
            </div>
            <a href="{{ route('Admin.aulas') }}" class="text-sm font-medium text-indigo-600 hover:text-indigo-500">Volver a aulas</a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto flex max-w-7xl flex-col gap-6 px-4 sm:px-6 lg:px-8">
            @if (session('status') === 'horario-created')
                <p class="rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm font-medium text-green-800">
                    Se agregaron {{ (int) session('clases_creadas') }} clases al horario.
                </p>
            @endif

            @if (session('status') === 'horario-deleted')
                <p class="rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm font-medium text-green-800">Clase eliminada del horario.</p>
            @endif

            @if (session('status') === 'horario-updated')
                <p class="rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm font-medium text-green-800">La clase se movió al nuevo horario.</p>
            @endif

            @if ($horarioBag->isNotEmpty())
                <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800" role="alert">
                    <p class="font-semibold">No se pudo guardar el cambio del horario.</p>
                    <ul class="mt-2 list-inside list-disc space-y-1">
                        @foreach ($horarioBag->all() as $mensaje)
                            <li>{{ $mensaje }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @if ($materias->isEmpty())
                <p class="rounded-2xl border border-amber-200 bg-amber-50 p-5 text-sm text-amber-900">
                    Esta aula todavía no tiene asignaturas con docente. Asígnalos primero desde
                    <a class="font-semibold underline" href="{{ route('Admin.aulas') }}">Aulas → Asignar docentes</a>.
                </p>
            @else
                <form
                    method="POST"
                    action="{{ route('Admin.aulas.horario.store', $aula) }}"
                    class="flex flex-col gap-5"
                    x-data="{
                        dias: @js($dias),
                        materias: @js($materias),
                        aulaActualId: @js((int) $aula->id),
                        existentes: @js($horariosVisuales),
                        bloqueosDocentes: @js($bloqueosDocentes),
                        bloqueosAulas: @js($bloqueosAulas),
                        csrfToken: @js(csrf_token()),
                        moverUrlBase: @js(route('Admin.aulas.horario.update', [$aula, '__HORARIO__'])),
                        eliminarUrlBase: @js(route('Admin.aulas.horario.destroy', [$aula, '__HORARIO__'])),
                        pendientes: [],
                        moviendoId: null,
                        guardandoMovimiento: false,
                        bloquearClickHasta: 0,
                        errorMovimiento: '',
                        editor: { abierto: false, dia: 1, inicio: '07:00', fin: '08:00', asignaturaId: '', aulaId: @js((int) $aula->id) },
                        horaInicio: @js($horaInicio),
                        horaFin: @js($horaFin),
                        aulaActual: @js($aula->etiqueta()),
                        minutos(hora) {
                            if (!hora || !hora.includes(':')) return 0
                            const [horas, minutos] = hora.split(':').map(Number)
                            return horas * 60 + minutos
                        },
                        hora(minutos) {
                            const horas = String(Math.floor(minutos / 60)).padStart(2, '0')
                            const resto = String(minutos % 60).padStart(2, '0')
                            return horas + ':' + resto
                        },
                        filasHora() {
                            const filas = []
                            for (let minuto = this.minutos(this.horaInicio); minuto < this.minutos(this.horaFin); minuto += 15) {
                                filas.push({ minuto, hora: this.hora(minuto) })
                            }
                            return filas
                        },
                        fila(hora) {
                            return Math.floor((this.minutos(hora) - this.minutos(this.horaInicio)) / 15) + 2
                        },
                        filasClase(clase) {
                            return Math.max(1, Math.ceil((this.minutos(clase.fin) - this.minutos(clase.inicio)) / 15))
                        },
                        estiloCuadricula() {
                            return {
                                gridTemplateColumns: '5rem repeat(7, minmax(8.5rem, 1fr))',
                                gridTemplateRows: '2.75rem repeat(' + this.filasHora().length + ', 1.75rem)',
                            }
                        },
                        estiloClase(clase) {
                            const clasesDia = [...this.existentes, ...this.pendientes]
                                .filter((item) => Number(item.dia) === Number(clase.dia) && !this.fueraDeRango(item))
                                .sort((primera, segunda) => this.minutos(primera.inicio) - this.minutos(segunda.inicio))
                            const carriles = []
                            const posiciones = new Map()

                            clasesDia.forEach((item) => {
                                let carril = carriles.findIndex((fin) => fin <= this.minutos(item.inicio))
                                if (carril === -1) {
                                    carril = carriles.length
                                }
                                carriles[carril] = this.minutos(item.fin)
                                posiciones.set(String(item.id), carril)
                            })

                            const cantidadCarriles = Math.max(carriles.length, 1)
                            const carril = posiciones.get(String(clase.id)) || 0

                            return 'grid-column: ' + (Number(clase.dia) + 1)
                                + '; grid-row: ' + this.fila(clase.inicio) + ' / span ' + this.filasClase(clase)
                                + '; width: calc((100% - 0.5rem) / ' + cantidadCarriles + ')'
                                + '; margin-left: calc(100% * ' + carril + ' / ' + cantidadCarriles + ')'
                                + '; justify-self: start'
                        },
                        claseDisponible(dia, inicio, fin, aulaId = this.aulaActualId, excluirId = null) {
                            const seCruza = (clase) => Number(clase.dia) === Number(dia)
                                && Number(clase.aulaId) === Number(aulaId)
                                && Number(clase.id) !== Number(excluirId)
                                && this.minutos(inicio) < this.minutos(clase.fin)
                                && this.minutos(fin) > this.minutos(clase.inicio)
                            return ![...this.bloqueosAulas, ...this.pendientes.filter((clase) => Number(clase.id) !== Number(excluirId))].some(seCruza)
                        },
                        docenteOcupado(asignaturaId, dia, inicio, fin, excluirId = null) {
                            const materia = this.materias.find((item) => String(item.id) === String(asignaturaId))
                            if (!materia || !inicio || !fin) return false
                            const seCruza = (clase) => Number(clase.docenteId) === Number(materia.docenteId)
                                && Number(clase.dia) === Number(dia)
                                && this.minutos(inicio) < this.minutos(clase.fin)
                                && this.minutos(fin) > this.minutos(clase.inicio)
                            return this.bloqueosDocentes.some(seCruza)
                                || this.bloqueosAulas.some((clase) => Number(clase.id) !== Number(excluirId)
                                    && Number(clase.docenteId) === Number(materia.docenteId)
                                    && Number(clase.dia) === Number(dia)
                                    && this.minutos(inicio) < this.minutos(clase.fin)
                                    && this.minutos(fin) > this.minutos(clase.inicio))
                                || this.pendientes.some((clase) => Number(clase.id) !== Number(this.editor.editandoId)
                                    && Number(clase.docenteId) === Number(materia.docenteId)
                                    && Number(clase.dia) === Number(dia)
                                    && this.minutos(inicio) < this.minutos(clase.fin)
                                    && this.minutos(fin) > this.minutos(clase.inicio))
                        },
                        destinoDisponible(id, dia, inicio) {
                            const clase = this.existentes.find((item) => Number(item.id) === Number(id))
                            if (!clase) return false
                            const duracion = this.minutos(clase.fin) - this.minutos(clase.inicio)
                            const fin = this.hora(this.minutos(inicio) + duracion)
                            return this.minutos(fin) <= this.minutos(this.horaFin)
                                && this.claseDisponible(dia, inicio, fin, clase.aulaId, id)
                                && !this.docenteOcupado(clase.asignaturaId, dia, inicio, fin, id)
                        },
                        soltarEnCoordenadas(event) {
                            if (!this.moviendoId) return
                            const celda = document.elementFromPoint(event.clientX, event.clientY)?.closest('[data-dia][data-inicio]')
                            this.bloquearClickHasta = Date.now() + 400
                            if (celda && this.destinoDisponible(this.moviendoId, celda.dataset.dia, celda.dataset.inicio)) {
                                this.soltarClase(this.moviendoId, celda.dataset.dia, celda.dataset.inicio)
                            } else {
                                this.moviendoId = null
                            }
                        },
                        iniciarMovimiento(clase, event) {
                            if (event.button !== 0 || event.target.closest('button') || this.guardandoMovimiento) return
                            event.preventDefault()
                            this.moviendoId = Number(clase.id)
                            this.bloquearClickHasta = Date.now() + 400
                            this.errorMovimiento = ''
                        },
                        async soltarClase(id, dia, inicio) {
                            if (!this.destinoDisponible(id, dia, inicio) || this.guardandoMovimiento) return
                            const clase = this.existentes.find((item) => Number(item.id) === Number(id))
                            const fin = this.hora(this.minutos(inicio) + this.minutos(clase.fin) - this.minutos(clase.inicio))
                            this.guardandoMovimiento = true
                            this.errorMovimiento = ''
                            try {
                                const response = await fetch(this.moverUrlBase.replace('__HORARIO__', id), {
                                    method: 'PATCH',
                                    headers: { 'Accept': 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': this.csrfToken },
                                    body: JSON.stringify({ dia_semana: Number(dia), hora_inicio: inicio, hora_fin: fin }),
                                })
                                const resultado = await response.json()
                                if (!response.ok) throw new Error(resultado.errors?.hora_inicio?.[0] || resultado.errors?.hora_fin?.[0] || resultado.message || 'No se pudo mover la clase.')
                                Object.assign(clase, { dia: Number(dia), inicio, fin })
                                const bloqueoActualizado = this.bloqueosAulas.find((item) => Number(item.id) === Number(id))
                                if (bloqueoActualizado) Object.assign(bloqueoActualizado, { dia: Number(dia), inicio, fin })
                                this.moviendoId = null
                            } catch (error) {
                                this.errorMovimiento = error.message
                            } finally {
                                this.guardandoMovimiento = false
                            }
                        },
                        async eliminarClase(id) {
                            if (!window.confirm('¿Eliminar esta clase del horario?') || this.guardandoMovimiento) return
                            this.guardandoMovimiento = true
                            this.errorMovimiento = ''
                            try {
                                const response = await fetch(this.eliminarUrlBase.replace('__HORARIO__', id), {
                                    method: 'DELETE',
                                    headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': this.csrfToken },
                                })
                                const resultado = await response.json()
                                if (!response.ok) throw new Error(resultado.message || 'No se pudo eliminar la clase.')
                                this.existentes = this.existentes.filter((clase) => Number(clase.id) !== Number(id))
                                this.bloqueosAulas = this.bloqueosAulas.filter((clase) => Number(clase.id) !== Number(id))
                                this.moviendoId = null
                            } catch (error) {
                                this.errorMovimiento = error.message
                            } finally {
                                this.guardandoMovimiento = false
                            }
                        },
                        abrirEditor(dia, inicio) {
                            if (!this.claseDisponible(dia, inicio, this.hora(this.minutos(inicio) + 15), this.aulaActualId)) return
                            this.editor = { abierto: true, dia: Number(dia), inicio, fin: this.hora(this.minutos(inicio) + 60), asignaturaId: '', aulaId: this.aulaActualId, editandoId: null }
                        },
                        guardarPendiente() {
                            if (!this.editor.asignaturaId || !this.editor.inicio || !this.editor.fin || this.minutos(this.editor.fin) <= this.minutos(this.editor.inicio)) return
                            if (!this.claseDisponible(this.editor.dia, this.editor.inicio, this.editor.fin, this.editor.aulaId, this.editor.editandoId)) return
                            if (this.docenteOcupado(this.editor.asignaturaId, this.editor.dia, this.editor.inicio, this.editor.fin)) return
                            this.pendientes.push({
                                id: Date.now() + Math.random(),
                                dia: Number(this.editor.dia),
                                inicio: this.editor.inicio,
                                fin: this.editor.fin,
                                asignaturaId: String(this.editor.asignaturaId),
                                aulaId: Number(this.editor.aulaId),
                                docenteId: Number(this.materia(this.editor.asignaturaId)?.docenteId),
                            })
                            this.editor.abierto = false
                        },
                        quitar(id) {
                            this.pendientes = this.pendientes.filter((clase) => clase.id !== id)
                        },
                        materia(id) {
                            return this.materias.find((item) => String(item.id) === String(id))
                        },
                        nombreAula() {
                            return @js($aula->etiqueta())
                        },
                        descripcionClase(clase, asignatura, docente, aula) {
                            const dia = this.dias.find((item) => Number(item.numero) === Number(clase.dia))?.nombre || 'Día'

                            return [
                                'Asignatura: ' + (asignatura || 'Asignatura'),
                                'Docente: ' + (docente || 'Sin docente'),
                                'Aula: ' + (aula || 'Aula'),
                                'Día: ' + dia,
                                'Hora: ' + clase.inicio + '–' + clase.fin,
                            ].join(String.fromCharCode(10))
                        },
                        fueraDeRango(clase) {
                            return this.minutos(clase.inicio) < this.minutos(this.horaInicio)
                                || this.minutos(clase.fin) > this.minutos(this.horaFin)
                        },
                    }"
                >
                    @csrf

                    <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
                        <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                            <div>
                                <h3 class="text-lg font-semibold text-slate-900">Horario semanal del aula</h3>
                                <p class="mt-1 text-sm text-slate-500">Se muestran únicamente las clases, docentes y asignaturas asignados a esta aula. Haz clic en un espacio libre para agregar una clase.</p>
                            </div>
                            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                                <label class="text-xs font-semibold text-slate-600">
                                    Mostrar desde
                                    <input type="time" step="900" x-model="horaInicio" class="mt-1 block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                </label>
                                <label class="text-xs font-semibold text-slate-600">
                                    Mostrar hasta
                                    <input type="time" step="900" x-model="horaFin" class="mt-1 block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                </label>
                            </div>
                        </div>

                        <div class="mt-5 overflow-x-auto rounded-xl border border-slate-200">
                            <div class="min-w-[1060px] p-2">
                                <div class="grid" :style="estiloCuadricula()" x-on:mouseup.window="soltarEnCoordenadas($event)">
                                    <div class="sticky top-0 z-30 flex items-center justify-center border-b border-r border-slate-200 bg-slate-50 text-xs font-semibold text-slate-500" style="grid-column: 1; grid-row: 1">Hora</div>

                                    <template x-for="dia in dias" :key="'encabezado-' + dia.numero">
                                        <div class="sticky top-0 z-30 flex items-center justify-center border-b border-r border-slate-200 bg-slate-50 px-2 text-sm font-semibold text-slate-700" :style="'grid-column: ' + (dia.numero + 1) + '; grid-row: 1'" x-text="dia.nombre"></div>
                                    </template>

                                    <template x-for="(franja, indice) in filasHora()" :key="'hora-' + franja.minuto">
                                        <div class="z-10 flex items-start justify-center border-b border-r border-slate-200 bg-slate-50 pt-0.5 text-[10px] font-medium text-slate-500" :style="'grid-column: 1; grid-row: ' + (indice + 2)" x-text="franja.minuto % 60 === 0 ? franja.hora : ''"></div>
                                    </template>

                                    <template x-for="dia in dias" :key="'celdas-' + dia.numero">
                                        <template x-for="(franja, indice) in filasHora()" :key="dia.numero + '-' + franja.minuto">
                                            <button
                                                type="button"
                                                class="group z-0 flex items-center justify-center border-b border-r border-slate-100 bg-white text-xs text-indigo-500 hover:bg-indigo-50 disabled:cursor-not-allowed disabled:bg-slate-50 disabled:text-transparent"
                                                :class="moviendoId && destinoDisponible(moviendoId, dia.numero, franja.hora) ? '!bg-emerald-100 !text-emerald-700 hover:!bg-emerald-200' : ''"
                                                :style="'grid-column: ' + (dia.numero + 1) + '; grid-row: ' + (indice + 2)"
                                                :data-dia="dia.numero"
                                                :data-inicio="franja.hora"
                                                :disabled="guardandoMovimiento || (moviendoId ? !destinoDisponible(moviendoId, dia.numero, franja.hora) : (!claseDisponible(dia.numero, franja.hora, hora(minutos(franja.hora) + 15), aulaActualId) || minutos(franja.hora) + 15 > minutos(horaFin)))"
                                                :aria-label="moviendoId ? 'Mover clase a ' + dia.nombre + ' ' + franja.hora : 'Agregar clase ' + dia.nombre + ' ' + franja.hora"
                                                x-on:click="if (!moviendoId && !guardandoMovimiento && Date.now() > bloquearClickHasta) abrirEditor(dia.numero, franja.hora)"
                                                x-text="moviendoId ? (destinoDisponible(moviendoId, dia.numero, franja.hora) ? '↔' : '') : (claseDisponible(dia.numero, franja.hora, hora(minutos(franja.hora) + 15), aulaActualId) ? '+' : '')"
                                            ></button>
                                        </template>
                                    </template>

                                    <template x-for="clase in existentes" :key="'existente-' + clase.id">
                                        <div
                                            class="z-20 m-0.5 flex min-h-0 flex-col overflow-hidden rounded-md border border-indigo-200 bg-indigo-100 px-1.5 py-1 text-indigo-950 shadow-sm cursor-grab active:cursor-grabbing"
                                            :class="moviendoId ? 'pointer-events-none' : ''"
                                            :style="estiloClase(clase)"
                                            x-show="!fueraDeRango(clase)"
                                            x-on:mousedown="iniciarMovimiento(clase, $event)"
                                            :title="descripcionClase(clase, clase.asignatura, clase.docente, clase.aula)"
                                        >
                                            <div class="flex items-center justify-between gap-1">
                                                <span class="truncate text-[11px] font-bold" x-text="clase.asignatura"></span>
                                                <button type="button" class="shrink-0 rounded px-1 text-[10px] font-bold text-red-700 hover:bg-red-100" aria-label="Eliminar clase" title="Eliminar clase" x-on:click.stop="eliminarClase(clase.id)">×</button>
                                            </div>
                                            <span class="truncate text-[10px]" x-text="clase.inicio + '–' + clase.fin"></span>
                                            <span class="truncate text-[10px]" x-text="'Docente: ' + clase.docente"></span>
                                            <span class="break-words text-[10px] leading-tight" x-text="'Aula: ' + clase.aula"></span>
                                        </div>
                                    </template>

                                    <template x-for="clase in pendientes" :key="'pendiente-' + clase.id">
                                        <div
                                            class="z-20 m-0.5 flex min-h-0 flex-col overflow-hidden rounded-md border border-dashed border-emerald-300 bg-emerald-100 px-1.5 py-1 text-emerald-950 opacity-90 shadow-sm"
                                            :style="estiloClase(clase)"
                                            x-show="!fueraDeRango(clase)"
                                            :title="descripcionClase(clase, materia(clase.asignaturaId)?.nombre, materia(clase.asignaturaId)?.docente, nombreAula(clase.aulaId))"
                                        >
                                            <span class="truncate text-[11px] font-bold" x-text="materia(clase.asignaturaId)?.nombre || 'Asignatura'"></span>
                                            <span class="truncate text-[10px]" x-text="clase.inicio + '–' + clase.fin"></span>
                                            <span class="truncate text-[10px]" x-text="'Docente: ' + (materia(clase.asignaturaId)?.docente || '')"></span>
                                            <span class="break-words text-[10px] leading-tight" x-text="'Aula: ' + nombreAula()"></span>
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </div>

                        <p x-cloak x-show="pendientes.some((clase) => fueraDeRango(clase))" class="mt-3 rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-sm text-amber-900">Hay clases pendientes fuera del rango mostrado. Ajusta “Mostrar desde” o “Mostrar hasta” para verlas.</p>
                        <p x-cloak x-show="moviendoId" class="mt-3 rounded-lg border border-emerald-200 bg-emerald-50 px-3 py-2 text-sm text-emerald-900">Mantén presionada la clase, arrástrala y suéltala sobre una celda verde disponible. Su duración se conservará.</p>
                        <p x-cloak x-show="errorMovimiento" x-text="errorMovimiento" class="mt-3 rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-800" role="alert"></p>

                        <div x-cloak x-show="pendientes.length > 0" class="mt-5 flex flex-col gap-3">
                            <h4 class="text-sm font-semibold text-slate-800">Clases por guardar</h4>
                            <template x-for="(clase, indice) in pendientes" :key="'detalle-' + clase.id">
                                <div class="flex flex-col gap-3 rounded-xl border border-emerald-200 bg-emerald-50 p-3 sm:flex-row sm:items-center sm:justify-between">
                                    <div>
                                        <p class="text-sm font-semibold text-emerald-950" x-text="materia(clase.asignaturaId)?.nombre + ' · ' + dias.find((dia) => dia.numero === Number(clase.dia))?.nombre"></p>
                                        <p class="text-xs text-emerald-800" x-text="clase.inicio + '–' + clase.fin + ' · ' + (materia(clase.asignaturaId)?.docente || '') + ' · Aula ' + nombreAula()"></p>
                                    </div>
                                    <div class="flex items-center gap-3">
                                        <input type="hidden" :name="'clases[' + indice + '][establecimiento_asignatura_id]'" :value="clase.asignaturaId">
                                        <input type="hidden" :name="'clases[' + indice + '][dia_semana]'" :value="clase.dia">
                                        <input type="hidden" :name="'clases[' + indice + '][hora_inicio]'" :value="clase.inicio">
                                        <input type="hidden" :name="'clases[' + indice + '][hora_fin]'" :value="clase.fin">
                                        <button type="button" class="rounded-lg px-3 py-2 text-sm font-medium text-slate-500 hover:bg-white hover:text-red-700" x-on:click="quitar(clase.id)">Quitar</button>
                                    </div>
                                </div>
                            </template>
                        </div>

                        <div class="mt-4 flex flex-col gap-3 border-t border-slate-100 pt-4 sm:flex-row sm:items-center sm:justify-between">
                            <p class="text-sm text-slate-500">Las horas se configuran para cada clase. No se permiten cruces de aula ni de docente.</p>
                            <x-primary-button x-bind:disabled="pendientes.length === 0">Guardar horario</x-primary-button>
                        </div>
                    </section>

                    <div x-cloak x-show="editor.abierto" x-on:keydown.escape.window="editor.abierto = false" class="fixed inset-0 z-[100] flex items-center justify-center overflow-y-auto bg-slate-950/50 p-4" role="dialog" aria-modal="true" aria-labelledby="editor-horario-titulo">
                        <div class="w-full max-w-xl rounded-2xl bg-white p-5 shadow-2xl sm:p-6" x-on:click.stop>
                            <div class="mb-5 flex items-start justify-between gap-4">
                                <div>
                                    <h3 id="editor-horario-titulo" class="text-lg font-semibold text-slate-900">Agregar clase</h3>
                                    <p class="mt-1 text-sm text-slate-500">Aula: {{ $aula->etiqueta() }}. Selecciona una asignatura y define el horario.</p>
                                </div>
                                <button type="button" class="rounded-lg px-2 py-1 text-slate-400 hover:bg-slate-100 hover:text-slate-700" aria-label="Cerrar" x-on:click="editor.abierto = false">✕</button>
                            </div>

                            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                <label class="block text-sm font-medium text-slate-700 sm:col-span-2">
                                    Asignatura y docente
                                    <select x-model="editor.asignaturaId" class="mt-1 block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                        <option value="">Selecciona una asignatura</option>
                                        @foreach ($materias as $materia)
                                            <option value="{{ $materia['id'] }}">{{ $materia['nombre'] }} · {{ $materia['docente'] }}</option>
                                        @endforeach
                                    </select>
                                </label>
                                <label class="block text-sm font-medium text-slate-700">
                                    Día
                                    <select x-model.number="editor.dia" class="mt-1 block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                        @foreach ($dias as $dia)
                                            <option value="{{ $dia['numero'] }}">{{ $dia['nombre'] }}</option>
                                        @endforeach
                                    </select>
                                </label>
                                <label class="block text-sm font-medium text-slate-700">
                                    Hora de inicio
                                    <input type="time" step="900" x-model="editor.inicio" class="mt-1 block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                </label>
                                <label class="block text-sm font-medium text-slate-700">
                                    Hora de fin
                                    <input type="time" step="900" x-model="editor.fin" :min="editor.inicio" class="mt-1 block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                </label>
                            </div>

                            <p x-cloak x-show="editor.inicio && editor.fin && minutos(editor.fin) <= minutos(editor.inicio)" class="mt-3 text-sm font-medium text-red-700">La hora de fin debe ser posterior a la hora de inicio.</p>
                            <p x-cloak x-show="editor.inicio && editor.fin && minutos(editor.fin) > minutos(editor.inicio) && !claseDisponible(editor.dia, editor.inicio, editor.fin, editor.aulaId, editor.editandoId)" class="mt-3 text-sm font-medium text-red-700">Ese horario se cruza con otra clase de esta aula.</p>
                            <p x-cloak x-show="editor.asignaturaId && editor.inicio && editor.fin && docenteOcupado(editor.asignaturaId, editor.dia, editor.inicio, editor.fin)" class="mt-3 text-sm font-medium text-red-700">El docente asignado ya tiene una clase en ese horario.</p>

                            <div class="mt-6 flex justify-end gap-3">
                                <button type="button" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50" x-on:click="editor.abierto = false">Cancelar</button>
                                <button type="button" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700 disabled:cursor-not-allowed disabled:opacity-50" x-on:click="guardarPendiente()" x-bind:disabled="!editor.asignaturaId || !editor.aulaId || !editor.inicio || !editor.fin || minutos(editor.fin) <= minutos(editor.inicio) || !claseDisponible(editor.dia, editor.inicio, editor.fin, editor.aulaId, editor.editandoId) || docenteOcupado(editor.asignaturaId, editor.dia, editor.inicio, editor.fin)">Agregar al horario</button>
                            </div>
                        </div>
                    </div>
                </form>
            @endif

        </div>
    </div>
</x-app-layout>
