@props([
    'aulas',
    'seleccion',
    'storeRoute',
    'destino' => 'crear',
    'requiereParcial' => true,
    'requiereCiclo' => true,
])

<div
    x-data="{
        aulas: @js($aulas),
        aulaId: '{{ $seleccion['aula'] ?? '' }}',
        asignaturaId: '{{ $seleccion['asignatura'] ?? '' }}',
        cicloId: '{{ $seleccion['ciclo'] ?? '' }}',
        parcialId: '{{ $seleccion['parcial'] ?? '' }}',
        guardado: {
            aula: '{{ $seleccion['aula'] ?? '' }}',
            asignatura: '{{ $seleccion['asignatura'] ?? '' }}',
            ciclo: '{{ $seleccion['ciclo'] ?? '' }}',
            parcial: '{{ $seleccion['parcial'] ?? '' }}',
        },
        get aula() {
            return this.aulas.find((aula) => String(aula.id) === String(this.aulaId)) ?? null;
        },
        get materias() {
            return this.aula?.materias ?? [];
        },
        get materia() {
            return this.materias.find((materia) => String(materia.id) === String(this.asignaturaId)) ?? null;
        },
        get cicloLabel() {
            return this.aula?.ciclo_label ?? 'Ciclos';
        },
        get ciclos() {
            return this.aula?.ciclos ?? [];
        },
        get parciales() {
            return this.aula?.parciales ?? [];
        },
        get insumos() {
            return this.materia?.insumos ?? [];
        },
        requiereParcial: @js((bool) $requiereParcial),
        requiereCiclo: @js((bool) $requiereCiclo),
        get seleccionCompleta() {
            return Boolean(this.aulaId && this.asignaturaId && (! this.requiereCiclo || this.cicloId) && (! this.requiereParcial || this.parcialId));
        },
        get coincideConGuardado() {
            return String(this.aulaId) === String(this.guardado.aula)
                && String(this.asignaturaId) === String(this.guardado.asignatura)
                && (! this.requiereCiclo || String(this.cicloId) === String(this.guardado.ciclo))
                && (! this.requiereParcial || String(this.parcialId) === String(this.guardado.parcial))
                && this.seleccionCompleta;
        },
        aplicarSiCompleto() {
            if (! this.seleccionCompleta || this.coincideConGuardado) {
                return;
            }

            this.$refs.contexto.submit();
        },
    }"
>
    <div class="w-fit max-w-full overflow-x-auto rounded-lg bg-slate-200 px-3 py-2">
        <form
            x-ref="contexto"
            method="POST"
            action="{{ $storeRoute }}"
            class="flex flex-nowrap items-end gap-2"
        >
            @csrf
            <input type="hidden" name="destino" value="{{ $destino }}">

            <div class="w-44 shrink-0">
                <label for="establecimiento_aula_id" class="block text-xs font-medium text-slate-700">Aulas:</label>
                <select
                    id="establecimiento_aula_id"
                    name="establecimiento_aula_id"
                    x-model="aulaId"
                    @change="asignaturaId = ''; cicloId = ''; parcialId = ''"
                    class="mt-0.5 block w-full rounded-md border-slate-300 bg-slate-100 py-1.5 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                    required
                >
                    <option value="">Aula</option>
                    <template x-for="aula in aulas" :key="aula.id">
                        <option :value="aula.id" x-text="aula.etiqueta"></option>
                    </template>
                </select>
                <x-input-error class="mt-1" :messages="$errors->get('establecimiento_aula_id')" />
            </div>

            <div class="w-52 shrink-0">
                <label for="establecimiento_asignatura_id" class="block text-xs font-medium text-slate-700">Asignaturas:</label>
                <select
                    id="establecimiento_asignatura_id"
                    name="establecimiento_asignatura_id"
                    x-model="asignaturaId"
                    @change="aplicarSiCompleto()"
                    class="mt-0.5 block w-full rounded-md border-slate-300 bg-slate-100 py-1.5 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                    :disabled="!aulaId"
                    required
                >
                    <option value="">Asignatura</option>
                    <template x-for="materia in materias" :key="materia.id">
                        <option :value="materia.id" x-text="materia.nombre"></option>
                    </template>
                </select>
                <x-input-error class="mt-1" :messages="$errors->get('establecimiento_asignatura_id')" />
            </div>

            <div class="w-40 shrink-0" @if (! $requiereCiclo) hidden @endif>
                <label for="establecimiento_periodo_ciclo_id" class="block text-xs font-medium text-slate-700" x-text="cicloLabel + ':'">Ciclos:</label>
                <select
                    id="establecimiento_periodo_ciclo_id"
                    name="establecimiento_periodo_ciclo_id"
                    x-model="cicloId"
                    @change="aplicarSiCompleto()"
                    class="mt-0.5 block w-full rounded-md border-slate-300 bg-slate-100 py-1.5 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                    :disabled="!aulaId"
                    @if ($requiereCiclo) required @endif
                >
                    <option value="">Ciclo</option>
                    <template x-for="ciclo in ciclos" :key="ciclo.id">
                        <option :value="ciclo.id" x-text="ciclo.nombre"></option>
                    </template>
                </select>
                <x-input-error class="mt-1" :messages="$errors->get('establecimiento_periodo_ciclo_id')" />
            </div>

            <div class="w-36 shrink-0" @if (! $requiereParcial) hidden @endif>
                <label for="parcial_numero" class="block text-xs font-medium text-slate-700">Parciales:</label>
                <select
                    id="parcial_numero"
                    name="parcial_numero"
                    x-model="parcialId"
                    @change="aplicarSiCompleto()"
                    class="mt-0.5 block w-full rounded-md border-slate-300 bg-slate-100 py-1.5 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                    :disabled="!aulaId"
                    @if ($requiereParcial) required @endif
                >
                    <option value="">Parcial</option>
                    <template x-for="parcial in parciales" :key="parcial.numero">
                        <option :value="parcial.numero" x-text="parcial.nombre"></option>
                    </template>
                </select>
                <x-input-error class="mt-1" :messages="$errors->get('parcial_numero')" />
            </div>
        </form>
    </div>

    {{ $slot }}
</div>
