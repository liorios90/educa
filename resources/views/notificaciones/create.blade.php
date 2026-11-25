<x-app-layout>
    <x-slot name="header">
        <div>
            <a href="{{ route('notificaciones.index') }}" class="text-sm font-medium text-indigo-700 hover:text-indigo-900">← Bandeja</a>
            <h2 class="mt-1 text-xl font-semibold leading-tight text-slate-800">Redactar notificación</h2>
            <p class="mt-1 text-sm text-slate-500">Solo puedes escribir a las personas permitidas para tu rol.</p>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
            @if ($destinatarios->isEmpty())
                <p class="rounded-2xl border border-slate-200 bg-white px-6 py-8 text-center text-slate-500">
                    No hay destinatarios disponibles para tu rol.
                </p>
            @else
                <form method="POST" action="{{ route('notificaciones.store') }}" enctype="multipart/form-data" class="flex flex-col gap-6 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                    @csrf

                    @php
                        $ordenRoles = [
                            \App\Enums\Role::Admin->value,
                            \App\Enums\Role::Secretaria->value,
                            \App\Enums\Role::Empleado->value,
                            \App\Enums\Role::Docente->value,
                            \App\Enums\Role::Padre->value,
                            \App\Enums\Role::Alumno->value,
                            \App\Enums\Role::Sistemas->value,
                        ];
                        $opcionesDestinatarios = $destinatarios
                            ->map(fn (array $destinatario): array => [
                                'id' => (int) $destinatario['id'],
                                'nombre' => $destinatario['nombre'],
                                'rol' => $destinatario['rol'],
                                'roles' => array_values($destinatario['roles']),
                                'aulas' => array_values(array_map(intval(...), $destinatario['aulas'] ?? [])),
                                'etiqueta' => $destinatario['nombre'].' ('.$destinatario['rol'].')',
                            ])
                            ->values()
                            ->all();
                        $rolesFiltro = collect($opcionesDestinatarios)
                            ->flatMap(fn (array $opcion): array => $opcion['roles'])
                            ->unique()
                            ->sortBy(function (string $rol) use ($ordenRoles): int {
                                $indice = array_search($rol, $ordenRoles, true);

                                return $indice === false ? PHP_INT_MAX : $indice;
                            })
                            ->map(fn (string $rol): array => [
                                'valor' => $rol,
                                'etiqueta' => \App\Enums\Role::from($rol)->label(),
                            ])
                            ->values()
                            ->all();
                        $destinatariosSeleccionados = array_map(
                            fn (mixed $id): int => (int) $id,
                            (array) old('destinatarios', []),
                        );
                        $rolesSeleccionados = collect((array) old('roles', []))
                            ->map(fn (mixed $rol): string => (string) $rol)
                            ->filter(fn (string $rol): bool => \App\Enums\Role::tryFrom($rol) !== null)
                            ->values()
                            ->all();
                        $aulasFiltro = collect($aulas)
                            ->map(fn (array $aula): array => [
                                'id' => (int) $aula['id'],
                                'etiqueta' => $aula['etiqueta'],
                            ])
                            ->values()
                            ->all();
                        $aulaSeleccionada = (string) old('aula', '');

                        if (! collect($aulasFiltro)->contains(fn (array $aula): bool => (string) $aula['id'] === $aulaSeleccionada)) {
                            $aulaSeleccionada = '';
                        }
                    @endphp

                    <div
                        class="flex flex-col gap-1"
                        x-on:click.outside="abierto = false"
                        x-data="{
                            busqueda: '',
                            abierto: false,
                            roles: @js($rolesSeleccionados),
                            rolesDisponibles: @js($rolesFiltro),
                            filtraFamiliares: @js($aulas->isNotEmpty()),
                            aula: @js($aulaSeleccionada),
                            seleccionados: @js($destinatariosSeleccionados),
                            opciones: @js($opcionesDestinatarios),
                            coincide(texto) {
                                const consulta = String(this.busqueda || '').toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '');

                                return consulta === '' || String(texto || '').toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '').includes(consulta);
                            },
                            rolActivo(opcion) {
                                const coinciden = opcion.roles.filter((rol) => this.roles.includes(rol));

                                if (coinciden.length === 0) {
                                    return false;
                                }

                                const soloFamilia = coinciden.every((rol) => rol === 'Padre' || rol === 'Alumno');

                                if (! this.filtraFamiliares || ! soloFamilia) {
                                    return true;
                                }

                                const aula = Number(this.aula);

                                return aula > 0 && (opcion.aulas || []).map(Number).includes(aula);
                            },
                            pideAula() {
                                return this.filtraFamiliares
                                    && (this.roles.includes('Padre') || this.roles.includes('Alumno'))
                                    && this.aula === ''
                                    && this.roles.every((rol) => rol === 'Padre' || rol === 'Alumno');
                            },
                            agregarTodos(rol) {
                                const aula = Number(this.aula);

                                if (! aula || ! this.roles.includes(rol)) {
                                    return;
                                }

                                const ids = this.opciones
                                    .filter((opcion) => opcion.roles.includes(rol) && (opcion.aulas || []).map(Number).includes(aula))
                                    .map((opcion) => opcion.id);

                                this.seleccionados = [...new Set([...this.seleccionados, ...ids])];
                                this.abierto = true;
                            },
                            elegidos() {
                                return this.opciones.filter((opcion) => this.seleccionados.includes(opcion.id));
                            },
                            disponibles() {
                                return this.opciones.filter((opcion) => ! this.seleccionados.includes(opcion.id) && this.rolActivo(opcion) && this.coincide(opcion.etiqueta));
                            },
                            agregar(id) {
                                const valor = Number(id);

                                if (! this.seleccionados.includes(valor)) {
                                    this.seleccionados = [...this.seleccionados, valor];
                                }

                                this.busqueda = '';
                                this.abierto = true;
                            },
                            quitar(id) {
                                this.seleccionados = this.seleccionados.filter((actual) => Number(actual) !== Number(id));
                            },
                            elegirPrimero() {
                                const primero = this.disponibles()[0];

                                if (primero) {
                                    this.agregar(primero.id);
                                }
                            },
                        }"
                    >
                        <fieldset class="flex flex-col gap-2">
                            <legend class="text-sm font-medium text-slate-700">Enviar a</legend>
                            <div class="flex flex-wrap gap-2">
                                <template x-for="rol in rolesDisponibles" :key="rol.valor">
                                    <label
                                        class="inline-flex cursor-pointer items-center gap-2 rounded-lg border px-3 py-1.5 text-sm"
                                        :class="roles.includes(rol.valor) ? 'border-indigo-300 bg-indigo-50 text-indigo-800' : 'border-slate-300 bg-white text-slate-700'"
                                    >
                                        <input
                                            type="checkbox"
                                            name="roles[]"
                                            class="rounded border-slate-300 text-indigo-600 shadow-sm focus:ring-indigo-500"
                                            :value="rol.valor"
                                            x-model="roles"
                                            x-on:change="if ($event.target.checked) abierto = true"
                                        >
                                        <span x-text="rol.etiqueta"></span>
                                    </label>
                                </template>
                            </div>
                            <p class="text-xs text-slate-500">Elige uno o varios roles. La lista solo muestra a las personas de esos roles.</p>
                        </fieldset>

                        @if ($aulas->isNotEmpty())
                            <div class="mt-3 flex flex-col gap-2" x-show="roles.includes('Padre') || roles.includes('Alumno')" x-cloak>
                                <x-input-label for="aula-destinatarios" value="Aula" />
                                <select
                                    id="aula-destinatarios"
                                    name="aula"
                                    class="rounded-md border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                    x-model="aula"
                                    x-on:change="if (aula !== '') abierto = true"
                                >
                                    <option value="">Selecciona un aula</option>
                                    @foreach ($aulasFiltro as $aulaOpcion)
                                        <option value="{{ $aulaOpcion['id'] }}">{{ $aulaOpcion['etiqueta'] }}</option>
                                    @endforeach
                                </select>
                                <div class="flex flex-wrap gap-2">
                                    <button type="button" class="rounded-lg border border-indigo-200 bg-indigo-50 px-3 py-1.5 text-sm font-semibold text-indigo-800 hover:bg-indigo-100" x-show="roles.includes('Padre') && aula !== ''" x-cloak x-on:click="agregarTodos('Padre')">Todos los padres</button>
                                    <button type="button" class="rounded-lg border border-indigo-200 bg-indigo-50 px-3 py-1.5 text-sm font-semibold text-indigo-800 hover:bg-indigo-100" x-show="roles.includes('Alumno') && aula !== ''" x-cloak x-on:click="agregarTodos('Alumno')">Todos los alumnos</button>
                                </div>
                                <p class="text-xs text-slate-500">Elige el aula para ver a sus padres y alumnos. Puedes añadirlos a todos.</p>
                            </div>
                        @endif

                        <x-input-label class="mt-3" for="busqueda-destinatarios" value="Destinatarios" />
                        <div class="rounded-lg border border-slate-300 bg-white px-2 py-1 shadow-sm" x-on:click="abierto = true">
                            <div class="flex flex-wrap items-center gap-1">
                                <template x-for="opcion in elegidos()" :key="'elegido-'+opcion.id">
                                    <span class="inline-flex items-center gap-1 rounded-md bg-slate-100 px-2 py-1 text-xs text-slate-700">
                                        <span>
                                            <span class="font-semibold text-slate-800" x-text="opcion.nombre"></span>
                                            <span class="font-normal text-slate-500" x-text="' (' + opcion.rol + ')'"></span>
                                        </span>
                                        <button type="button" class="text-slate-500 hover:text-slate-800" x-on:click="quitar(opcion.id)" aria-label="Quitar">&times;</button>
                                        <input type="hidden" name="destinatarios[]" :value="opcion.id">
                                    </span>
                                </template>
                                <input
                                    id="busqueda-destinatarios"
                                    type="search"
                                    class="min-w-[12rem] flex-1 cursor-pointer border-0 bg-transparent py-1.5 text-sm focus:ring-0"
                                    placeholder="Buscar y seleccionar varios"
                                    autocomplete="off"
                                    x-model="busqueda"
                                    x-on:focus="abierto = true"
                                    x-on:keydown.enter.prevent="elegirPrimero()"
                                    x-on:keydown.escape.stop="abierto = false"
                                >
                            </div>
                            <div
                                class="max-h-56 overflow-y-auto border-t border-slate-200 py-1"
                                role="listbox"
                                aria-multiselectable="true"
                                aria-label="Destinatarios"
                                x-show="abierto"
                                x-cloak
                            >
                                <template x-for="opcion in disponibles()" :key="opcion.id">
                                    <button
                                        type="button"
                                        class="block w-full px-2 py-1.5 text-left text-sm hover:bg-slate-50"
                                        role="option"
                                        x-on:click="agregar(opcion.id)"
                                    >
                                        <span class="font-semibold text-slate-800" x-text="opcion.nombre"></span>
                                        <span class="font-normal text-slate-500" x-text="' (' + opcion.rol + ')'"></span>
                                    </button>
                                </template>
                                <p class="px-2 py-2 text-sm text-slate-500" x-show="roles.length === 0">Selecciona al menos un rol para ver destinatarios.</p>
                                <p class="px-2 py-2 text-sm text-slate-500" x-show="pideAula() && disponibles().length === 0">Selecciona un aula para ver padres y alumnos.</p>
                                <p class="px-2 py-2 text-sm text-slate-500" x-show="roles.length > 0 && !pideAula() && disponibles().length === 0">No hay destinatarios para mostrar.</p>
                            </div>
                        </div>
                        <p class="text-xs text-slate-500">Puedes marcar varios. Dentro de los roles elegidos, busca por nombre, aula o alumno.</p>
                        <x-input-error class="mt-2" :messages="$errors->get('destinatarios')" />
                    </div>

                    <div>
                        <x-input-label for="asunto" value="Asunto" />
                        <x-text-input id="asunto" name="asunto" type="text" class="mt-1 block w-full" :value="old('asunto')" required maxlength="160" />
                        <x-input-error class="mt-2" :messages="$errors->get('asunto')" />
                    </div>

                    <div>
                        <x-input-label for="cuerpo" value="Mensaje" />
                        <textarea
                            id="cuerpo"
                            name="cuerpo"
                            rows="8"
                            required
                            maxlength="5000"
                            class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        >{{ old('cuerpo') }}</textarea>
                        <x-input-error class="mt-2" :messages="$errors->get('cuerpo')" />
                    </div>

                    <div>
                        <x-input-label for="archivos" value="Archivos adjuntos" />
                        <input
                            id="archivos"
                            name="archivos[]"
                            type="file"
                            multiple
                            accept=".pdf,.jpg,.jpeg,.png,.webp,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.odt,.ods,.txt,application/pdf,image/jpeg,image/png,image/webp"
                            class="mt-1 block w-full text-sm text-slate-700 file:mr-3 file:rounded-lg file:border-0 file:bg-indigo-50 file:px-3 file:py-2 file:text-sm file:font-semibold file:text-indigo-700 hover:file:bg-indigo-100"
                        >
                        <p class="mt-1 text-xs text-slate-500">Opcional. Hasta 5 archivos, de 10 MB cada uno. PDF, Word, Excel, PowerPoint, imagen o texto.</p>
                        <x-input-error class="mt-2" :messages="$errors->get('archivos')" />
                        <x-input-error class="mt-2" :messages="$errors->get('archivos.*')" />
                    </div>

                    <div class="flex justify-end">
                        <button type="submit" class="rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-indigo-500">
                            Enviar
                        </button>
                    </div>
                </form>
            @endif
        </div>
    </div>
</x-app-layout>
